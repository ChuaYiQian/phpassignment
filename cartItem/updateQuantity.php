<?php
include '../base.php';

if (isset($_POST['productID'], $_POST['cartID'], $_POST['newQuantity'])) {
    $stmt = $_db->prepare("UPDATE cartItem SET cartQuantity = ? WHERE productID = ? AND cartID = ?");
    $stmt->execute([$_POST['newQuantity'], $_POST['productID'], $_POST['cartID']]);
    
    header("Location: /cart/cart.php");
    exit;
}