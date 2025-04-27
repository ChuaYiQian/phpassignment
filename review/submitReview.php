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
        $_SESSION['error'] = 'value not found';
        header("Location: /orderDetail.php?orderID=" . urlencode($_POST['orderID'] ?? ''));
        exit;
    }
}

$orderID = $_POST['orderID'];
$productID = $_POST['productID'];
$userID = $_SESSION['user_id'];
$starRating = (int)$_POST['starRating'];
$reviewText = trim($_POST['reviewText']);
$validPhotos = [];
$uploadDir = '../images/';

if (!empty($_FILES['reviewImg']['name'][0])) {
    $maxFiles = 4;
    $fileCount = min(count($_FILES['reviewImg']['name']), $maxFiles);
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = basename($_FILES['reviewImg']['name'][$i]);
        $type = $_FILES['reviewImg']['type'][$i];
        $tmp_name = $_FILES['reviewImg']['tmp_name'][$i];
        $size = $_FILES['reviewImg']['size'][$i];
        $error = $_FILES['reviewImg']['error'][$i];

        if ($error !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'file upload error';
            header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
            exit;
        }

        if (!str_starts_with($type, 'image/')) {
            $_SESSION['error'] = 'only can upload image file';
            header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
            exit;
        }

        if ($size > 1 * 1024 * 1024) {
            $_SESSION['error'] = 'cannot exceed 1MB';
            header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
            exit;
        }

        $uniqueName = uniqid() . '_' . $name;
        if (move_uploaded_file($tmp_name, "$uploadDir/$uniqueName")) {
            $validPhotos[] = $uniqueName;
        }
    }
}
$reviewImg = !empty($validPhotos) ? implode(',', $validPhotos) : null;

if ($starRating < 1 || $starRating > 5) {
    $_SESSION['error'] = 'review star must be 1 to 5';
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
        throw new Exception('no this order');
    }

    $reviewStmt = $_db->prepare("
        SELECT oi.reviewID 
        FROM orderInformation oi
        LEFT JOIN Review r ON oi.reviewID = r.reviewID
        WHERE oi.orderID = ?
        AND oi.productID = ?
        AND (r.reviewDescription IS NULL OR r.reviewDescription = '')
        LIMIT 1
    ");
    $reviewStmt->execute([$orderID, $productID]);
    $reviewID = $reviewStmt->fetchColumn();

    if (!$reviewID) {
        throw new Exception('cannot submit this review');
    }

    $updateStmt = $_db->prepare("
        UPDATE Review
        SET reviewDescription = :desc,
            starQuantity = :stars,
            reviewDate = NOW(),
            reviewImage = :reviewImg
        WHERE reviewID = :id
    ");

    $_db->beginTransaction();
    $success = $updateStmt->execute([
        ':desc' => $reviewText,
        ':stars' => $starRating,
        ':reviewImg' => $reviewImg,
        ':id' => $reviewID
    ]);
    
    if (!$success || $updateStmt->rowCount() === 0) {
        throw new Exception('submit failures');
    }
    
    $_db->commit();

    $_SESSION['success'] = 'submit successful！';
    header("Location: /order/userOrder.php");
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    error_log("error: " . $e->getMessage());
    $_SESSION['error'] = 'try again';
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: /orderDetail.php?orderID=" . urlencode($orderID));
exit;
?>