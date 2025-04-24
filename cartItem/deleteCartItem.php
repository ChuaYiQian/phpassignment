<?php
include '../base.php';
session_start();

// Check if required fields are set
if (!isset($_POST['productID'], $_POST['cartID'])) {
    die("Required fields missing");
}

$stmt = $_db->prepare("DELETE FROM cartItem WHERE productID = ? AND cartID = ?");
$stmt->execute([$_POST['productID'], $_POST['cartID']]);

if (isset($_POST['userID'])) {
    header("Location: /cart/cart.php");
} else if (isset($_POST['staffID'])) {
    header("Location: /cart/maintenanceCart.php");
} else {
    header("Location: /");
}
exit;
?>