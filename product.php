<?php include 'header.php';?>
<?php include 'base.php';

$arr = $_db->query('SELECT * FROM product')->fetchAll(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <link rel="stylesheet" href="/css/product.css">
</head>
<body>
<div class="product-grid">
<?php foreach ($arr as $p): ?>
    <div class="product-container">
        <img src="/images/<?= $p->productPicture ?>" class="product-image">
        <h1 class="product-title"><?= $p->productName ?></h1>
        <p class="product-price">RM<?= $p->productPrice ?></p>
        <p class="product-description"><?= $p->productDescription ?></p>
        <button class="buy-button">Buy Now</button>        
    </div>
    <?php endforeach ?>
</div>

</body>
</html>

<?php include 'footer.php';?>