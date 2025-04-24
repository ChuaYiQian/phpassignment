<?php
session_start();
require '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$requiredParams = ['orderID', 'productID', 'starRating', 'reviewText'];
foreach ($requiredParams as $param) {
    if (!isset($_POST[$param]) || empty(trim($_POST[$param]))) {
        $_SESSION['error'] = 'must fill in';
        header("Location: /orderDetail.php?orderID=" . urlencode($_POST['orderID'] ?? ''));
        exit;
    }
}

$orderID = $_POST['orderID'];
$productID = $_POST['productID'];
$userID = $_SESSION['user_id'];
$starRating = (int)$_POST['starRating'];
$reviewText = trim($_POST['reviewText']);

if ($starRating < 1 || $starRating > 5) {
    $_SESSION['error'] = '1 to 5';
    header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
    exit;
}

try {
    $orderStmt = $_db->prepare("
        SELECT 1 
        FROM `Order` o
        JOIN orderInformation oi ON o.orderID = oi.orderID
        WHERE o.orderID = ?
        AND o.userID = ?
        AND oi.productID = ?
        AND o.orderStatus = 'completed'
    ");
    $orderStmt->execute([$orderID, $userID, $productID]);
    
    if (!$orderStmt->fetch()) {
        throw new Exception('order no exist');
    }

    $reviewStmt = $_db->prepare("
        SELECT oi.reviewID 
        FROM orderInformation oi
        JOIN Review r ON oi.reviewID = r.reviewID
        WHERE oi.orderID = ?
        AND oi.productID = ?
        AND r.reviewDescription = ''
        LIMIT 1
    ");
    $reviewStmt->execute([$orderID, $productID]);
    $reviewID = $reviewStmt->fetchColumn();

    if (!$reviewID) {
        throw new Exception('no this review');
    }

    $updateStmt = $_db->prepare("
        UPDATE Review
        SET reviewDescription = :desc,
            starQuantity = :stars,
            reviewDate = NOW()
        WHERE reviewID = :id
    ");

    $_db->beginTransaction();
    $success = $updateStmt->execute([
        ':desc' => $reviewText,
        ':stars' => $starRating,
        ':id' => $reviewID
    ]);
    
    if (!$success || $updateStmt->rowCount() === 0) {
        throw new Exception('review unsuccessful');
    }
    
    $_db->commit();

    $_SESSION['success'] = 'review successful！';
    header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    error_log("error: " . $e->getMessage());
    $_SESSION['error'] = 'error: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: ../order/orderDetail.php?orderID=" . urlencode($orderID));
exit;
?>