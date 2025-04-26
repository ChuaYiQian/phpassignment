<?php
include '../base.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}

if (isset($_POST['productID'], $_POST['cartID'], $_POST['newQuantity'])) {
    $stmt = $_db->prepare("UPDATE cartItem SET cartQuantity = ? WHERE productID = ? AND cartID = ?");
    $stmt->execute([$_POST['newQuantity'], $_POST['productID'], $_POST['cartID']]);
    
    header("Location: /cart/cart.php");
    exit;
}