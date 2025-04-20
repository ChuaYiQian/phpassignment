<?php
include '../base.php';

if (isset($_POST['productID'], $_POST['cartID'], $_POST['userID'])) {
    $stmt = $_db->prepare("DELETE FROM cartItem WHERE productID = ? AND cartID = ?");
    $stmt->execute([$_POST['productID'], $_POST['cartID']]);

    header("Location: /cart/cart.php");
    exit;
}

else if(isset($_POST['productID'], $_POST['cartID'], $_POST['staffID'])) {
    $stmt = $_db->prepare("DELETE FROM cartItem WHERE productID = ? AND cartID = ?");
    $stmt->execute([$_POST['productID'], $_POST['cartID']]);

    header("Location: /cart/maintenanceCart.php");
    exit;
}