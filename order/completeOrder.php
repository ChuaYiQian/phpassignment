<?php

session_start();
require '../base.php';

// if (!isset($_GET['orderID']) {
//     $_SESSION['error'] = 'Missing order identifier';
//     header("Location: /payment_error.php");
//     exit;
// }

$orderID = $_GET['orderID'];

try {
    $stmt = $_db->prepare("
        SELECT orderID 
        FROM `Order`
        WHERE orderID = ? 
        AND userID = ? 
        AND orderStatus = 'pending'
    ");
    $stmt->execute([
        $orderID,
        $_SESSION['user_id']
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Invalid order or unauthorized access');
    }

    $updateStmt = $_db->prepare("
        UPDATE `Order`
        SET orderStatus = 'completed'
        WHERE orderID = ?
    ");
    $updateStmt->execute([$orderID]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Order status update failed');
    }

    header("Location: /payment_success.php?orderID=" . urlencode($orderID));
    exit;

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = 'Database operation failed';
    header("Location: /payment_error.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: /payment_error.php");
    exit;
}