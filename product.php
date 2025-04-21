<?php include 'header.php'; ?>
<?php include 'base.php';
$categories = $_db->query('SELECT categoryID, categoryName FROM category')->fetchAll();

$category = req('category');
$minPrice = req('minPrice');
$maxPrice = req('maxPrice');
$productName = req('productName');

$where = [];
$params = [];

if ($category !== '') {
    $where[] = 'categoryID = ?';
    $params[] = $category;
}
if ($minPrice !== '') {
    $where[] = 'productPrice >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== '') {
    $where[] = 'productPrice <= ?';
    $params[] = $maxPrice;
}
if ($productName !== '') {
    $where[] = 'productName LIKE ?';
    $params[] = "%$productName%";
}

$sql = 'SELECT * FROM product WHERE productStatus = "Y"';
if ($where) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

$stm = $_db->prepare($sql);
$stm->execute($params);
$arr = $stm->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Page</title>
    <link rel="stylesheet" href="/css/product.css">
</head>

<body>
    <div class="page-wrapper">
        <aside class="sidebar">
            <h2>Filter</h2>
            <form method="get">
                <label for="productName">Product Name:</label><br>
                <input type="search" name="productName" id="productName" value="<?= $productName ?>"><br><br>
                <label for="category">Category:</label><br>
                <select name="category" id="category">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->categoryID ?>" <?= $cat->categoryID == $category ? 'selected' : '' ?>>
                            <?= $cat->categoryName ?>
                        </option>
                    <?php endforeach ?>
                </select><br><br>

                <label for="minPrice">Min Price:</label><br>
                <input type="number" name="minPrice" id="minPrice" min="0"><br><br>

                <label for="maxPrice">Max Price:</label><br>
                <input type="number" name="maxPrice" id="maxPrice" min="0"><br><br>

                <button type="submit">Apply Filter</button>
            </form>
        </aside>
        <div class="product-grid">
            <?php foreach ($arr as $p): ?>
                <div class="product-container">
                    <a href="productDetails.php?id=<?= $p->productID ?>" class="overlay-link">
                        <img src="/images/<?= $p->productPicture ?>" class="product-image">
                        <h1 class="product-title"><?= $p->productName ?></h1>
                        <p cla0ss="product-price">RM<?= $p->productPrice ?></p>
                        <p class="product-description"><?= $p->productDescription ?></p>
                    </a>00
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="/cartItem/addCartItem.php" method="POST">
                            <input type="hidden" name="userID" value="<?= $_SESSION['user_id']?>">
                            <input type="hidden" name="productID" value="<?= $p->productID?>">
                            <button type="submit" class="buy-button">Add To Cart</button>
                        </form>
                    <?php else: ?>
                        <button class="buy-button" onclick="openLoginPopup()">Add To Cart</button>
                    <?php endif; ?>
                </div>
            <?php endforeach ?>
        </div>

    </div>

</body>

</html>

<?php include 'footer.php'; ?>