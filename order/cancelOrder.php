<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /home.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];
    $userID = $_SESSION['user_id'];

    $stmt = $_db->prepare("UPDATE `order` SET orderStatus = 'cancelled' WHERE orderID = ? AND userID = ?");
    $success = $stmt->execute([$orderID, $userID]);

    if ($success) {
        header("Location: /userOrder.php");
        exit();
    } else {
        echo "Failed to cancel the order.";
    }
} else {
    header("Location: /userOrder.php");
    exit();
}
