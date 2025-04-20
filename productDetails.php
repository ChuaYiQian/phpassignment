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