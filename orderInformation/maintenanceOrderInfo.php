<?php
include '../base.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

if (!isset($_GET['orderID'])) {
    header("Location: maintenanceOrder.php");
    exit;
}

$orderID = $_GET['orderID'];
$stmt = $_db->prepare("
    SELECT o.*, u.userName 
    FROM `order` o
    JOIN user u ON o.userID = u.userID
    WHERE o.orderID = ?
");
$stmt->execute([$orderID]);
$order = $stmt->fetch(PDO::FETCH_OBJ);

if (!$order) {
    echo "Order not found";
    exit;
}

$itemsStmt = $_db->prepare("
    SELECT p.productName, oi.orderQuantity, p.productPrice
    FROM orderInformation oi
    JOIN product p ON oi.productID = p.productID
    WHERE oi.orderID = ?
");
$itemsStmt->execute([$orderID]);
$items = $itemsStmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details</title>
    <link rel="stylesheet" href="/css/maintenanceOrderInfo.css">
</head>
<body>
<?php include '../adminheader.php'; ?>
    <div class="container">
        <div class="header">
            <h1>Order Details - <?= $order->orderID ?></h1>
            <a href="../order/maintenanceOrder.php" class="back-btn">Back to Orders</a>
        </div>

        <div class="order-card">
            <div class="order-summary">
                <div class="summary-item">
                    <span>Order Date:</span>
                    <span><?= date('d/m/Y H:i', strtotime($order->orderDate)) ?></span>
                </div>
                <div class="summary-item">
                    <span>Customer:</span>
                    <span><?= $order->userName ?></span>
                </div>
                <div class="summary-item">
                    <span>Total Price:</span>
                    <span>RM<?= number_format($order->orderTotal, 2) ?></span>
                </div>
                <div class="summary-item">
                    <span>Status:</span>
                    <span class="status-badge status-<?= strtolower($order->orderStatus) ?>">
                        <?= $order->orderStatus ?>
                    </span>
                </div>
            </div>

            <div class="items-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item->productName ?></td>
                            <td><?= $item->orderQuantity ?></td>
                            <td>RM<?= number_format($item->productPrice, 2) ?></td>
                            <td>RM<?= number_format($item->productPrice * $item->orderQuantity, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>