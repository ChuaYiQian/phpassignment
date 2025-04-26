<?php
session_start();
include 'base.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

$orderID = $_POST['orderID'];
$paymentMethod = $_POST['payment_method'];

try {
    $_db->prepare("UPDATE `order` SET orderStatus = 'payed' WHERE orderID = ?")->execute([$orderID]);

    $_db->prepare("INSERT INTO payment (orderID, paymentMethod, paymentDate) VALUES (?, ?, NOW())")
        ->execute([$orderID, $paymentMethod]);

    header("Location: paymentSuccess.php?orderID=" . urlencode($orderID));
    exit;
} catch (PDOException $e) {
    error_log("Payment Error: " . $e->getMessage());
    $_SESSION['error'] = "Payment failed.";
    header("Location: paymentFailed.php?orderID=" . urlencode($orderID));
    exit;
}
