<?php include 'header.php'; ?>
<?php include 'base.php';

$id = req('id');
$stm = $_db->prepare('
                SELECT * FROM product WHERE productID = ?
            ');
$stm->execute([$id]);
$product = $stm->fetch();

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $product->productName ?> - Details</title>
    <link rel="stylesheet" href="/css/productDetails.css">
</head>
<body>
    <div class="product-detail-container">
    <img src="/images/<?= $product->productPicture ?>" class="detail-image">
    <div class="product-info">
        <h1><?= $product->productName ?></h1>
        <p class="detail-price">RM<?= $product->productPrice ?></p>
        <p class="detail-description"><?= $product->productDescription ?></p>
        <button class="buy-button">Add To Cart</button>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>
