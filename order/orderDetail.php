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
        SELECT 
            oi.*, 
            p.productName, 
            p.productPicture,
            p.productPrice,
            r.reviewDescription,
            r.starQuantity,
            r.reviewDate
        FROM orderInformation oi
        JOIN Product p ON oi.productID = p.productID
        LEFT JOIN Review r ON oi.reviewID = r.reviewID
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
    <title>Order Details - <?= htmlspecialchars($orderID) ?></title>
    <link rel="stylesheet" href="/css/orderDetail.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="order-container">
        <div class="order-header">
            <h1>Order Receipt</h1>
            <div class="order-meta">
                <p>Order ID: <?= htmlspecialchars($orderID) ?></p>
                <p>Date: <?= htmlspecialchars($order['orderDate']) ?></p>
                <p>Status: <span class="status-<?= htmlspecialchars($order['orderStatus']) ?>">
                    <?= htmlspecialchars($order['orderStatus']) ?>
                </span></p>
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
                        <?php if (!empty($item['reviewDescription'])): ?>
                           
                            <div class="existing-review">
                                <h4>Your Review:</h4>
                                <div class="rating-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?= $i <= $item['starQuantity'] ? 'filled' : '' ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <p class="review-date">
                                    Reviewed on <?= date('Y-m-d', strtotime($item['reviewDate'])) ?>
                                </p>
                                <div class="review-content">
                                    <?= htmlspecialchars($item['reviewDescription']) ?>
                                </div>
                            </div>
                        <?php else: ?>
                            
                            <label>
                                <input type="checkbox" class="toggle-review" 
                                       id="toggle-review-<?= $item['productID'] ?>">
                                Write a Review
                            </label>
                            <form class="review-form" id="review-form-<?= $item['productID'] ?>" 
                                  style="display:none;" method="post" action="../review/submitReview.php">
                                <input type="hidden" name="orderID" value="<?= $orderID ?>">
                                <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                <div class="rating">
                                    <span class="star" data-value="1">★</span>
                                    <span class="star" data-value="2">★</span>
                                    <span class="star" data-value="3">★</span>
                                    <span class="star" data-value="4">★</span>
                                    <span class="star" data-value="5">★</span>
                                    <input type="hidden" name="starRating" value="">
                                </div>
                                <textarea name="reviewText" placeholder="Write your review..." required></textarea>
                                <button type="submit">Submit Review</button>
                            </form>
                        <?php endif; ?>
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
    $(document).ready(function() {
        
        $('.toggle-review').change(function() {
            const $container = $(this).closest('.review-section');
            const $form = $container.find('.review-form');
            
            $form.toggle(this.checked);
            $form.find('.star').removeClass('selected hovered');
            $form.find('[name="starRating"]').val('');
        });

        $('.star:not(.filled)').hover(
            function() {
                const $stars = $(this).parent().find('.star');
                const index = $stars.index(this);
                $stars.removeClass('hovered');
                $stars.slice(0, index + 1).addClass('hovered');
            },
            function() {
                $(this).parent().find('.star').removeClass('hovered');
            }
        ).click(function() {
            const $stars = $(this).parent().find('.star');
            const index = $stars.index(this);
            const $form = $(this).closest('form');
            
            $stars.removeClass('selected');
            $stars.slice(0, index + 1).addClass('selected');
            $form.find('[name="starRating"]').val(index + 1);
        });

        $('.review-form').submit(function(e) {
            const $form = $(this);
            const rating = $form.find('[name="starRating"]').val();
            
            if (!rating) {
                alert('Please select a rating');
                e.preventDefault();
                return;
            }
            
            $form.find('button').prop('disabled', true)
                 .html('<span class="loading"></span> Submitting...');
        });
    });
    </script>
</body>
</html>
