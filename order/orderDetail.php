<?php
session_start();
require '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if ($_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page');
    exit();
}

if (!isset($_POST['orderID'])) {
    die("Invalid order access");
}

$orderID = $_POST['orderID'];
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
        die("Invalid order access");
    }

    $itemsStmt = $_db->prepare("
        SELECT 
            oi.*, 
            p.productName, 
            p.productPicture,
            p.productPrice,
            r.reviewDescription,
            r.starQuantity,
            r.reviewDate,
            r.reviewImage
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
<div class="topbar">
        <a href="/order/userOrder.php"><img src="/images/goBackIcon.png" alt="Back" width="40px" height="40px"></a>
        <h2>My Orders Detail</h2>
    </div>
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
                                    <?= nl2br(htmlspecialchars($item['reviewDescription'])) ?>
                                </div>
                                <?php if (!empty($item['reviewImage'])): ?>
                                    <div class="review-images">
                                        <?php foreach (explode(',', $item['reviewImage']) as $img): ?>
                                            <img src="/images/<?= htmlspecialchars($img) ?>" 
                                                 class="review-thumbnail">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- 修复HTML结构保持原有DOM关系 -->
                            <div class="toggle-container">
                                <label class="toggle-review-label">
                                    <input type="checkbox" class="toggle-review"
                                        id="toggle-review-<?= $item['productID'] ?>">
                                    Write a Review
                                </label>
                                <form class="review-form" id="review-form-<?= $item['productID'] ?>" 
                                      method="post" 
                                      action="../review/submitReview.php"
                                      enctype="multipart/form-data"
                                      style="display:none;">
                                    <input type="hidden" name="orderID" value="<?= $orderID ?>">
                                    <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                    
                                    <div class="rating-input">
                                        <span class="star" data-value="1">★</span>
                                        <span class="star" data-value="2">★</span>
                                        <span class="star" data-value="3">★</span>
                                        <span class="star" data-value="4">★</span>
                                        <span class="star" data-value="5">★</span>
                                        <input type="hidden" name="starRating" required>
                                    </div>
                                    
                                    <textarea name="reviewText" 
                                              placeholder="Write your review..." 
                                              minlength="10"
                                              required></textarea>
                                    
                                    <div class="form-group">
                                        <label>Uploaded Image maximum 4</label>
                                        <input type="file" 
                                               name="reviewImg[]" 
                                               accept="image/*" 
                                               multiple
                                               class="file-input">
                                        <small class="file-hint">support JPG/PNG format，each file connot exceed 1MB</small>
                                    </div>

                                    <button type="submit" class="submit-btn">Submit Review</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="order-summary">
            <h2>Grand Total：RM<?= number_format($order['orderTotal'], 2) ?></h2>
        </div>
    </div>

    <!-- 保持原始JavaScript不变 -->
    <script>
    $(document).ready(function() {
        $('.toggle-review').change(function() {
            const $form = $(this).closest('.toggle-container').find('.review-form');
            $form.slideToggle(300);
            $form.find('.star').removeClass('selected');
            $form.find('[name="starRating"]').val('');
        });

        $('.rating-input .star').hover(
            function() {
                $(this).prevAll().addBack().addClass('hovered');
            },
            function() {
                $(this).parent().find('.star').removeClass('hovered');
            }
        ).click(function() {
            const value = $(this).data('value');
            $(this).parent().find('.star')
                .removeClass('selected')
                .slice(0, value).addClass('selected');
            $(this).siblings('[name="starRating"]').val(value);
        });

        $('.review-form').submit(function(e) {
            const rating = $(this).find('[name="starRating"]').val();
            if (!rating) {
                alert('Please select a rating');
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>