<?php
include '../base.php';

if (!isset($_POST['userID'], $_POST['productID'], $_POST['quantity'])) {
    echo "Missing data.";
    exit;
}

$userID = $_POST['userID'];
$productID = $_POST['productID'];
$quantity = (int) $_POST['quantity'];


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

if ($quantity > $product->productQuantity) {
    echo "Not enough stock. Available: " . $product->productQuantity;
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
    $stmt = $_db->prepare("INSERT INTO cartItem (cartID, productID, cartQuantity) VALUES (?, ?, ?)");
    $stmt->execute([$cartID, $productID, $quantity]);
}

header("Location: cart.php?userID=" . $userID);
exit;
