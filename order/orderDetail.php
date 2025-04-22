<?php
session_start();
require '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

if (!isset($_GET['orderID'])) {
    die("Invalid order access");
}

$orderID = $_GET['orderID'];
$userID = $_SESSION['user_id'];

try {

    $orderStmt = $_db->prepare("
        SELECT o.*, u.userName 
        FROM `Order` o
        JOIN User u ON o.userID = u.userID
        WHERE o.orderID = ? AND o.userID = ?
    ");
    $orderStmt->execute([$orderID, $userID]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Order not found or unauthorized access");
    }

    $itemsStmt = $_db->prepare("
        SELECT oi.*, p.productName, p.productPicture ,p.productPrice
        FROM orderInformation oi
        JOIN Product p ON oi.productID = p.productID
        WHERE oi.orderID = ?
    ");
    $itemsStmt->execute([$orderID]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details - <?= $orderID ?></title>
    <link rel="stylesheet" href="/css/orderDetail.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="order-container">
        <div class="order-header">
            <h1>Order Receipt</h1>
            <div class="order-meta">
                <p>Order ID: <?= htmlspecialchars($orderID) ?></p>
                <p>Date: <?= $order['orderDate'] ?></p>
                <p>Status: <span class="status-<?= $order['orderStatus'] ?>"><?= $order['orderStatus'] ?></span></p>
            </div>
        </div>

        <div class="items-list">
            <?php foreach ($orderItems as $item): ?>
            <div class="item-card">
                <img src="/images/<?= htmlspecialchars(explode(',', $item['productPicture'])[0]) ?>" 
                     alt="<?= htmlspecialchars($item['productName']) ?>" 
                     class="product-image">
                <div class="item-details">
                    <h3><?= htmlspecialchars($item['productName']) ?></h3>
                    <p>Price: RM<?= number_format($item['productPrice'], 2) ?></p>
                    <p>Quantity: <?= $item['orderQuantity'] ?></p>
                    <p class="item-total">Total: RM<?= number_format($item['productPrice'] * $item['orderQuantity'], 2) ?></p>
                </div>
            </div>
            <?php if ($order['orderStatus'] == 'completed'): ?>
        <div class="review-section">
            <label><input type="checkbox" id="toggle-review"> Write a Review</label>
            <form id="review-form" style="display:none;" method="post" action="/submitReview.php">
                <input type="hidden" name="orderID" value="<?= $orderID ?>">
                <div class="rating">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                    <input type="hidden" name="starRating" id="star-rating">
                </div>
                <textarea name="reviewText" placeholder="Write your review..." required></textarea>
                <button type="submit">Submit Review</button>
            </form>
        </div>
        <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <h2>Grand Total: RM<?= number_format($order['orderTotal'], 2) ?></h2>
        </div>

        

        <?php if ($order['orderStatus'] == 'pending'): ?>
        <div class="payment-actions">
            <form method="post" action="/payment.php">
                <input type="hidden" name="orderID" value="<?= $orderID ?>">
                <button type="submit" class="pay-now-btn">Pay Now</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        $('.star').hover(function() {
            $(this).prevAll().addBack().addClass('hovered');
        }, function() {
            $(this).prevAll().addBack().removeClass('hovered');
        }).click(function() {
            $('#star-rating').val($(this).data('value'));
            $(this).addClass('selected').prevAll().addClass('selected');
            $(this).nextAll().removeClass('selected');
        });

        $('#toggle-review').change(function() {
            $('#review-form').toggle(this.checked);
        });
    </script>
</body>
</html>