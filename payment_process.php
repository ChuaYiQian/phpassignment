<?php
session_start();
include 'base.php';

$orderID = $_POST['orderID'];
$paymentMethod = $_POST['payment_method'];

try {
    // Simulate payment success
    $_db->prepare("UPDATE `order` SET orderStatus = 'paid' WHERE orderID = ?")->execute([$orderID]);

    // Insert payment record
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
