<?php
include '../base.php'; 
session_start();

//roles validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $paymentID = $_GET['id'];

    $stmt = $conn->prepare("SELECT paymentIcon FROM paymentmethod WHERE paymentID = ?");
    $stmt->bind_param("s", $paymentID);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();

    if ($payment && !empty($payment['paymentIcon'])) {
        $iconPath = '../' .$payment['paymentIcon'];
        
        if (file_exists($iconPath)) {
            unlink($iconPath);
        }
    }

    $deleteStmt = $conn->prepare("DELETE FROM paymentmethod WHERE paymentID = ?");
    $deleteStmt->bind_param("s", $paymentID);
    $deleteStmt->execute();

    if ($deleteStmt->affected_rows > 0) {
        header("Location: ../payment_table.php");
        exit();
    } else {
        echo "No payment method found with the provided ID.";
    }

    $stmt->close();
    $deleteStmt->close();
} else {
    echo "Invalid ID provided.";
}

$conn->close();
?>
