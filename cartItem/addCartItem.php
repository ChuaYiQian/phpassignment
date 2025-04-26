<?php
include '../base.php';
session_start();

//roles validation
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}else if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}

if (!isset($_POST['userID'], $_POST['productID'])) {
    echo "Missing userID or productID.";
    exit;
}

$userID = $_POST['userID'];
$productID = $_POST['productID'];
$quantity = isset($_POST['newQuantity']) ? (int)$_POST['newQuantity'] : 1;
$hasNewQuantity = isset($_POST['newQuantity']);

if ($quantity < 1 || $quantity > 99) {
    echo "Quantity must be between 1 and 99.";
    exit;
}

$stmt = $_db->prepare("SELECT productQuantity FROM product WHERE productID = ?");
$stmt->execute([$productID]);
$product = $stmt->fetch(PDO::FETCH_OBJ);

if (!$product) {
    echo "Product not found.";
    exit;
}

$stmt = $_db->prepare("SELECT cartID FROM cart WHERE userID = ?");
$stmt->execute([$userID]);
$cart = $stmt->fetch(PDO::FETCH_OBJ);

if (!$cart) {
    $stmt = $_db->prepare("INSERT INTO cart (userID) VALUES (?)");
    $stmt->execute([$userID]);
    $cartID = $_db->lastInsertId();
} else {
    $cartID = $cart->cartID;
}

$stmt = $_db->prepare("SELECT * FROM cartItem WHERE cartID = ? AND productID = ?");
$stmt->execute([$cartID, $productID]);
$existingItem = $stmt->fetch(PDO::FETCH_OBJ);

if ($existingItem) {
    $newQuantity = $existingItem->cartQuantity + $quantity;
    
    if ($newQuantity > 99) {
        $newQuantity = 99;
    }
    
    if ($newQuantity > $product->productQuantity) {
        echo "Total quantity in cart exceeds available stock.";
        exit;
    }
    
    $stmt = $_db->prepare("UPDATE cartItem SET cartQuantity = ? WHERE cartID = ? AND productID = ?");
    $stmt->execute([$newQuantity, $cartID, $productID]);
} else {
    if ($quantity > $product->productQuantity) {
        echo "Not enough stock. Available: " . $product->productQuantity;
        exit;
    }
    
    $stmt = $_db->prepare("INSERT INTO cartItem (cartID, productID, cartQuantity) VALUES (?, ?, ?)");
    $stmt->execute([$cartID, $productID, $quantity]);
}

if ($hasNewQuantity) {
    header("Location: /productDetails.php?id=" . $productID);
} else {
    header("Location: /product.php");
}
exit;