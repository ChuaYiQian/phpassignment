<?php include 'header.php'; ?>
<?php include 'base.php';

$id = req('id');
$stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product || $product->productStatus !== 'available') {
    echo "<p>Product not found or unavailable.</p>";
    include 'footer.php';
    exit;
}

$images = explode(',', $product->productPicture);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $product->productName ?> - Details</title>
    <link rel="stylesheet" href="/css/productDetails.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/productDetails.js" defer></script>
</head>

<body>
    <div class="product-detail-container">
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
            <h1><?= $product->productName ?></h1>
            <p class="detail-price">RM<?= number_format($product->productPrice, 2) ?></p>
            <p class="detail-description"><?= $product->productDescription ?></p>
            <form action="/cartItem/addCartItem.php" method="post">
                <input type="number" name="newQuantity" value="1" min="1" max="99">
                <input type="hidden" name="productID" value="<?= $product->productID ?>">
                <input type="hidden" name="userID" value="<?= $_SESSION['user_id'] ?>">
                <button class="buy-button">Add To Cart</button>
            </form>
        </div>
    </div>
</body>

</html>

<?php include 'footer.php'; ?>