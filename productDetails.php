<?php

include 'header.php';
include 'base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p>Invalid product ID.</p>";
    include 'footer.php';
    exit;
}

$stm = $_db->prepare('
    SELECT p.* FROM product p
    JOIN category c ON p.categoryID = c.categoryID
    WHERE p.productID = ? 
      AND p.productStatus = "available" 
      AND c.categoryStatus = "available"
      AND p.productQuantity > 0
');

$stm->execute([$id]);
$product = $stm->fetch(PDO::FETCH_OBJ);

if (!$product) {
    echo "<p>Product not found or unavailable.</p>";
    include 'footer.php';
    exit;
}


$images = explode(',', $product->productPicture);

$reviewStmt = $_db->prepare("
    SELECT 
        u.userName,
        u.userProfilePicture,
        r.starQuantity,
        r.reviewDescription,
        r.reviewDate
    FROM orderInformation oi
    JOIN Review r ON oi.reviewID = r.reviewID
    JOIN `Order` o ON oi.orderID = o.orderID
    JOIN User u ON o.userID = u.userID
    WHERE oi.productID = ?
    AND r.reviewDescription IS NOT NULL
    AND r.reviewDescription != ''
    ORDER BY r.reviewDate DESC
");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_OBJ);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product->productName) ?> - Details</title>
    <link rel="stylesheet" href="/css/productDetails.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/productDetails.js"></script>
</head>

<body>
    <div class="product-detail-container">
        <div class="product-main">
            <?php if (count($images) > 1): ?>
                <div class="detail-slider">
                    <button class="slider-btn prev" onclick="moveDetailSlide(-1)">&#10094;</button>
                    <div class="detail-slides-wrapper">
                        <div class="detail-slides" id="detailSlider">
                            <?php foreach ($images as $img): ?>
                                <img src="/images/<?= trim($img) ?>" class="detail-slide-image">
                            <?php endforeach ?>
                        </div>
                    </div>
                    <button class="slider-btn next" onclick="moveDetailSlide(1)">&#10095;</button>
                </div>
            <?php else: ?>
                <img src="/images/<?= trim($images[0]) ?>" class="detail-image">
            <?php endif; ?>

            <div class="product-info">
                <h1 class="product-title"><?= htmlspecialchars($product->productName) ?></h1>
                <p class="detail-price">RM<?= number_format($product->productPrice, 2) ?></p>
                <p class="detail-description"><?= htmlspecialchars($product->productDescription) ?></p>
                <form action="/cartItem/addCartItem.php" method="post">
                    <label for="newQuantity">Quantity:</label>
                    <input type="number" name="newQuantity" id="newQuantity" value="1" min="1"max="<?= $product->productQuantity ?>">
                    <input type="hidden" name="productID" value="<?= $product->productID ?>">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <input type="hidden" name="userID" value="<?= $_SESSION['user_id'] ?>">
                    <?php endif; ?>
                    <button class="buy-button">Add To Cart</button>
                </form>
            </div>
        </div>

        <div class="product-reviews">
            <h2>Customer Reviews</h2>
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">

                        <div class="review-header">
                        <img src="<?= htmlspecialchars($review->userProfilePicture) ?>" width="50" height="50" style="border-radius:50%;">
                            <span class="review-user"> <?= htmlspecialchars($review->userName) ?></span>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="star <?= $i < $review->starQuantity ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-content"><?= htmlspecialchars($review->reviewDescription) ?></p>
                        <p class="review-date">Reviewed on <?= date('M d, Y', strtotime($review->reviewDate)) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-reviews">Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php include 'footer.php'; ?>