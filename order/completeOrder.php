<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];
    $userID = $_SESSION['user_id'];

    $stmt = $_db->prepare("UPDATE `order` SET orderStatus = 'completed' WHERE orderID = ? AND userID = ?");
    $success = $stmt->execute([$orderID, $userID]);

    if ($success) {
        header("Location: /order/userOrder.php");
        exit();
    } else {
        echo "Failed to recover the order.";
    }
} else {
    header("Location: /order/userOrder.php");
    exit();
}
