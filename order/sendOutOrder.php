<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'])) {
    $orderID = $_POST['orderID'];

    $stmt = $_db->prepare("UPDATE `order` SET orderStatus = 'sendOut' WHERE orderID = ?");
    $success = $stmt->execute([$orderID]);

    if ($success) {
        header("Location: maintenanceOrder.php");
        exit();
    } else {
        echo "Failed to recover the order.";
    }
} else {
    header("Location: maintenanceOrder.php");
    exit();
}
