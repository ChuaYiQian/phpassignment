<?php
session_start();
require '../base.php';

if (!isset($_POST['orderID'])) {
    $_SESSION['error'] = 'no order id';
    header("Location: /payment_error.php");
    exit;
}

$orderID = $_POST['orderID'];
$userID = $_SESSION['user_id'];

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
        SET orderStatus = 'completed',
            orderDate = NOW()
        WHERE orderID = ?
    ");
    $updateStmt->execute([$orderID]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('updating order status unsuccessful');
    }

    $_db->commit();
    header("Location: /payment_success.php?orderID=" . urlencode($orderID));
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    error_log("PDO error: " . $e->getMessage()); // Log it
    $_SESSION['error'] = "Database error occurred. Please try again.";
    header("Location: /payment_error.php");
    exit;
} catch (Exception $e) {
    $_db->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: /payment_error.php");
    exit;
}