<?php
session_start();
require '../base.php';

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

if (isset($_POST['simulate']) && $_POST['simulate'] === 'fail') {
    $orderID = $_POST['orderID'];
    $_SESSION['error'] = "Simulated payment failure.";
    $_SESSION['order_id'] = $orderID; 
    header("Location: /payment_error.php");
    exit;
}

$orderID = $_POST['orderID'];
$userID = $_SESSION['user_id'];
$paymentID = $_POST['payment_method'];
$amount = $_POST['amount'] ?? 0; 

try {
    $_db->beginTransaction();

    $orderStmt = $_db->prepare("
        SELECT orderID 
        FROM `Order`
        WHERE orderID = ? 
        AND userID = ? 
        AND orderStatus = 'pending'
        FOR UPDATE
    ");
    $orderStmt->execute([$orderID, $userID]);
    
    if (!$orderStmt->fetch()) {
        throw new Exception('cannot fetch order');
    }

    $itemsStmt = $_db->prepare("
        SELECT productID, orderQuantity 
        FROM orderInformation 
        WHERE orderID = ?
    ");
    $itemsStmt->execute([$orderID]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $productUpdateStmt = $_db->prepare("
        UPDATE Product 
        SET productQuantity = productQuantity - :qty,
            salesCount = salesCount + :qty
        WHERE productID = :pid
        AND productQuantity >= :qty
    ");

    foreach ($orderItems as $item) {
        $productUpdateStmt->execute([
            ':qty' => $item['orderQuantity'],
            ':pid' => $item['productID']
        ]);

        if ($productUpdateStmt->rowCount() === 0) {
            throw new Exception("Stock too less: PID-{$item['productID']}");
        }
    }

    $updateStmt = $_db->prepare("
        UPDATE `Order`
        SET orderStatus = 'payed',
            orderDate = NOW()
        WHERE orderID = ?
    ");
    $updateStmt->execute([$orderID]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('updating order status unsuccessful');
    }

    $_db->commit();
    header("Location: /payment_success.php?orderID=" . urlencode($orderID) . "&paymentID=" . urlencode($paymentID) . "&amount=" . urlencode($amount));
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    error_log("PDO error: " . $e->getMessage()); // Log it
    $_SESSION['error'] = "Database error occurred. Please try again.";
    $_SESSION['order_id'] = $orderID; 
    header("Location: /payment_error.php?orderID=" . urlencode($orderID));
    exit;
} catch (Exception $e) {
    $_db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    $_SESSION['order_id'] = $orderID; 
    header("Location: /payment_error.php?orderID=" . urlencode($orderID));
    exit;
}