<?php
include 'base.php';
$arr = $_db->query('SELECT * FROM product ORDER BY salesCount DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PopZone Collectibles</title>
    <link rel="stylesheet" href="/css/home.css">
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="hero">
        <div class="hero-overlay">
            <h2>Discover the Magic of Collectibles</h2>
            <p>Limited edition art toys and designer figurines.</p>
            <a href="product.php" class="btn">ShopNow</a>
        </div>
    </div>

    <h2 style="text-align: center;">Top 5 Product</h2>
    <div class="slider-container">
        <button class="prev" onclick="moveSlide(-1)">&#10094;</button>
        <div class="slider">
            <?php foreach ($arr as $p): ?>
                <?php
                $images = explode(',', $p->productPicture);
                $firstImage = $images[0];
                ?>
                    <div class="slide">
                        <a href="productDetails.php?id=<?= $p->productID ?>" class="slide-link">
                        <img src="/images/<?= $firstImage ?>" alt="<?= htmlspecialchars($p->productName) ?>">
                        <p><?= htmlspecialchars($p->productName) ?></p>
                        <p>RM <?= number_format($p->productPrice, 2) ?></p>
                        <a href="productDetails.php?id=<?= $p->productID ?>"></a>
                    </div>
                
            <?php endforeach ?>
        </div>
        <button class="next" onclick="moveSlide(1)">&#10095;</button>
    </div>
    <script src="/js/home.js"></script>
    <?php include 'footer.php'; ?>
</body>

</html>