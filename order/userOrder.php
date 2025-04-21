<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /home.php");
    exit();
}

$userID = $_SESSION['user_id'];

$statusFilter = $_GET['status'] ?? 'pending';

$stmt = $_db->prepare("SELECT * FROM `order` WHERE userID = ? AND orderStatus = ?");
$stmt->execute([$userID, $statusFilter]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatDate($dateStr) {
    return date("Y-m-d", strtotime($dateStr));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Orders</title>
    <link rel="stylesheet" href="/css/order.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .topbar {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f4f4f4;
        }
        .topbar img {
            margin-right: 10px;
        }
        .button-group {
            margin: 20px;
            display: flex;
            gap: 10px;
        }
        .button-group button {
            padding: 10px 20px;
            border: none;
            background-color: #444;
            color: white;
            cursor: pointer;
        }
        .order-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .order-card img {
            width: 100px;
            height: 100px;
        }
        .order-info {
            flex: 1;
        }
        .order-actions button {
            margin-right: 10px;
            padding: 5px 10px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="/home.php"><img src="/images/goBackIcon.png" alt="Back" width="40px" height="40px"></a>
    <h2>My Orders</h2>
</div>

<div class="button-group">
    <a href="?status=pending"><button <?= $statusFilter == 'pending' ? 'style="background-color: #222;"' : '' ?>>Pending Payment</button></a>
    <a href="?status=completed"><button <?= $statusFilter == 'completed' ? 'style="background-color: #222;"' : '' ?>>Order History</button></a>
    <a href="?status=cancelled"><button <?= $statusFilter == 'cancelled' ? 'style="background-color: #222;"' : '' ?>>Cancelled</button></a>
</div>

<?php if (empty($orders)): ?>
    <p style="margin-left: 20px;">No <?= htmlspecialchars($statusFilter) ?> orders found.</p>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <img src="/images/Luffy.jpg">
            <div class="order-info">
                <p><strong>Order ID:</strong> <?= $order['orderID'] ?></p>
                <p><strong>Total Price:</strong> RM<?= number_format($order['orderTotal'], 2) ?></p>
                <p><strong>Date:</strong> <?= formatDate($order['orderDate']) ?></p>
                <p><strong>Item qunatity:</strong> <?= formatDate($order['orderDate']) ?></p>
            </div>
            <div class="order-actions">
                <?php if ($order['orderStatus'] === 'pending'): ?>
                    <form method="post" action="payNow.php" style="display:inline;">
                        <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                        <button type="submit">Pay Now</button>
                    </form>
                    <form method="post" action="cancelOrder.php" style="display:inline;">
                        <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                        <button type="submit">Cancel Order</button>
                    </form>
                <?php elseif ($order['orderStatus'] === 'completed'): ?>
                    <form method="get" action="receipt.php" style="display:inline;">
                        <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                        <button type="submit">View Receipt</button>
                    </form>
                <?php elseif ($order['orderStatus'] === 'cancelled'): ?>
                    <form method="post" action="recoverOrder.php" style="display:inline;">
                        <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                        <button type="submit">Recover</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
