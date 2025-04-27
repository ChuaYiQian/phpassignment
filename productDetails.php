<?php
include 'base.php';
include 'header.php';

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
        r.reviewDate,
        r.reviewImage
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

$goodCount = $normalCount = $badCount = 0;
foreach ($reviews as $review) {
    $stars = $review->starQuantity;
    if ($stars >= 4) {
        $goodCount++;
    } elseif ($stars >= 2) {
        $normalCount++;
    } else {
        $badCount++;
    }
}

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
            <div class="filter-buttons">
                <button class="filter-btn good" data-filter="4-5">Good (<?= $goodCount ?>)</button>
                <button class="filter-btn normal" data-filter="2-3">Normal (<?= $normalCount ?>)</button>
                <button class="filter-btn bad" data-filter="0-1">Bad (<?= $badCount ?>)</button>
            </div>
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card" data-stars="<?= $review->starQuantity ?>">
                        <div class="review-header">
                            <img src="<?= htmlspecialchars($review->userProfilePicture) ?>" class="user-avatar">
                            <span class="review-user"><?= htmlspecialchars($review->userName) ?></span>
                            <div class="review-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="star <?= $i < $review->starQuantity ? 'filled' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-content"><?= htmlspecialchars($review->reviewDescription) ?></p>
                        <?php if (!empty($review->reviewImage)): ?>
                            <div class="review-images-grid">
                                <?php foreach (explode(',', $review->reviewImage) as $image): ?>
                                    <img src="/images/<?= htmlspecialchars(trim($image)) ?>" class="review-thumbnail">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p class="review-date">Reviewed on <?= date('M d, Y', strtotime($review->reviewDate)) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-reviews">Be the first to review this product!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.filter-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const range = this.dataset.filter.split('-');
                    const minStar = parseInt(range[0]);
                    const maxStar = parseInt(range[1]);

                    document.querySelectorAll('.review-card').forEach(card => {
                        const stars = parseInt(card.dataset.stars);
                        card.classList.remove('good', 'normal', 'bad');
                        
                        if (stars >= minStar && stars <= maxStar) {
                            card.style.display = 'block';
                            if (minStar === 4) card.classList.add('good');
                            else if (minStar === 2) card.classList.add('normal');
                            else card.classList.add('bad');
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    document.querySelectorAll('.filter-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });

            const resetFilter = document.createElement('button');
            resetFilter.textContent = 'Show All';
            resetFilter.className = 'filter-btn';
            resetFilter.addEventListener('click', () => {
                document.querySelectorAll('.review-card').forEach(card => {
                    card.style.display = 'block';
                    card.classList.remove('good', 'normal', 'bad');
                });
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
            });
            document.querySelector('.filter-buttons').appendChild(resetFilter);
        });
    </script>

</body>
</html>
<?php include 'footer.php'; ?>