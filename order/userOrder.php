<?php
session_start();
require_once '../base.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

$userID = $_SESSION['user_id'];

$statusFilter = $_GET['status'] ?? 'pending';

$stmt = $_db->prepare("SELECT * FROM `order` WHERE userID = ? AND orderStatus = ?");
$stmt->execute([$userID, $statusFilter]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatDate($dateStr)
{
    return date("Y-m-d", strtotime($dateStr));
}

function getTopProductImage($orderID, $db)
{
    $stmt = $db->prepare("SELECT oi.productID, oi.orderQuantity, p.productPicture
                          FROM orderInformation oi
                          JOIN product p ON oi.productID = p.productID
                          WHERE oi.orderID = ?
                          ORDER BY oi.orderQuantity DESC
                          LIMIT 1");
    $stmt->execute([$orderID]);
    $topProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($topProduct && !empty($topProduct['productPicture'])) {
        $pictures = explode(',', $topProduct['productPicture']);
        $firstPicture = trim($pictures[0]);

        if (strpos($firstPicture, '/images/') !== 0) {
            $firstPicture = '/images/' . $firstPicture;
        }

        return $firstPicture;
    } else {
        return '/images/default.png';
    }
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
            border-radius: 10px;
            cursor: pointer;
        }

        .order-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        .animate {
    animation: slideIn 0.3s ease-in-out;
  }

  @keyframes slideIn {
    0% { transform: translateX(0); }
    25% { transform: translateX(-12px); }
    50% { transform: translateX(10px); }
    75% { transform: translateX(-7px); }
    100% { transform: translateX(0); }
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
        <a href="?status=payed"><button <?= $statusFilter == 'payed' ? 'style="background-color: #222;"' : '' ?>>Payed</button></a>
        <a href="?status=completed"><button <?= $statusFilter == 'completed' ? 'style="background-color: #222;"' : '' ?>>Order History</button></a>
        <a href="?status=cancelled"><button <?= $statusFilter == 'cancelled' ? 'style="background-color: #222;"' : '' ?>>Cancelled</button></a>
        <a href="?status=sendout"><button <?= $statusFilter == 'sendout' ? 'style="background-color: #222;"' : '' ?>>To Ship</button></a>
    </div>
    <?php if (empty($orders)): ?>
        <div style="width: 100%; display:flex; flex-direction:column; justify-content:center; height:60vh;">
            <img id="emptyCart" src="/images/emptyOrder.png" height="100px" width="100px" style="margin-top:auto; margin-left:auto; margin-right:auto;">
            <p style="color:black;margin: 20px; font-size: 1.6em; text-align:center; margin-bottom:auto;
                font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; font-weight:bold;">Order Not Found</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            
            <?php $productImage = getTopProductImage($order['orderID'], $_db); ?>
            <div class="order-card">
            <img src="<?= htmlspecialchars($productImage) ?>" alt="Product Image">
                <div class="order-info">
                    <p><strong>Order ID:</strong> <?= $order['orderID'] ?></p>
                    <p><strong>Total Price:</strong> RM<?= number_format($order['orderTotal'], 2) ?></p>
                    <p><strong>Date:</strong> <?= formatDate($order['orderDate']) ?></p>
                    <p><strong>Item quantity:</strong> <?=$totalQuantity = getOrderTotalQuantity($order['orderID'], $_db);?></p>
                </div>
                <div class="order-actions">
                    <?php if ($order['orderStatus'] === 'pending'): ?>
                        <form method="get" action="../payment.php" style="display:inline;">
                            <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                            <button type="submit">Pay Now</button>
                        </form>
                        <form method="post" action="cancelOrder.php" style="display:inline;">
                            <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                            <button type="submit">Cancel Order</button>
                        </form>
                    <?php elseif ($order['orderStatus'] === 'completed'): ?>
                        <form method="post" action="/order/orderDetail.php" style="display:inline;">
                            <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                            <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                            <button type="submit">View Receipt</button>
                        </form>
                    <?php elseif ($order['orderStatus'] === 'cancelled'): ?>
                        <form method="post" action="recoverOrder.php" style="display:inline;">
                            <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                            <button type="submit">Recover</button>
                        </form>
                    <?php elseif ($order['orderStatus'] === 'payed'): ?>
                        <p>Waiting Packing and Shipping</p>
                    <?php elseif ($order['orderStatus'] === 'sendOut'): ?>
                        <form method="post" action="completeOrder.php" style="display:inline;">
                            <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                            <button type="submit">Order Receive</button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('emptyCart').classList.add('animate');
        });
    </script>
</body>

</html>