<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: /home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];
    $userID = $_SESSION['user_id'];

    $stmt = $_db->prepare("UPDATE `order` SET orderStatus = 'pending' WHERE orderID = ? AND userID = ?");
    $success = $stmt->execute([$orderID, $userID]);

    if ($success) {
        header("Location: /order/userOrder.php");
        exit();
    } else {
        echo "Failed to recover the order.";
    }
} else {
    header("Location: /userOrder.php");
    exit();
}
