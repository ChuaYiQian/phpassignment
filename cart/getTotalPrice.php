<?php
include '../base.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "0.00";
    exit;
}

$userID = $_SESSION['user_id'];

$stmt = $_db->prepare("SELECT cartID FROM cart WHERE userID = ?");
$stmt->execute([$userID]);
$cart = $stmt->fetch(PDO::FETCH_OBJ);

if (!$cart) {
    echo "0.00";
    exit;
}

$cartID = $cart->cartID;

if (!isset($_POST['items']) || !is_array($_POST['items'])) {
    echo "0.00";
    exit;
}

$productIDs = $_POST['items'];
$placeholders = rtrim(str_repeat('?,', count($productIDs)), ',');

$sql = "
    SELECT c.cartQuantity, p.productPrice 
    FROM cartItem c 
    JOIN product p ON c.productID = p.productID 
    WHERE c.cartID = ? AND c.productID IN ($placeholders)
";

$params = array_merge([$cartID], $productIDs);

$stmt = $_db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['productPrice'] * $item['cartQuantity'];
}

echo number_format($total, 2);
exit;