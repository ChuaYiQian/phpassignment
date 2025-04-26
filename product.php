<?php include 'header.php'; ?>
<?php include 'base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

$categories = $_db->query('SELECT categoryID, categoryName FROM category WHERE categoryStatus = "Available"')->fetchAll();

$category = req('category');
$minPrice = req('minPrice');
$maxPrice = req('maxPrice');
$productName = req('productName');
$sort = req('sort');

$where = [];
$params = [];

// Filter conditions
if ($category !== '') {
    $where[] = 'p.categoryID = ?';
    $params[] = $category;
}
if ($minPrice !== '') {
    $where[] = 'p.productPrice >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== '') {
    $where[] = 'p.productPrice <= ?';
    $params[] = $maxPrice;
}
if ($productName !== '') {
    $where[] = 'p.productName LIKE ?';
    $params[] = "%$productName%";
}

$sql = 'SELECT p.* FROM product p 
        JOIN category c ON p.categoryID = c.categoryID 
        WHERE p.productStatus = "Available" 
        AND c.categoryStatus = "Available" 
        AND p.productQuantity > 0';

if ($where) {
    $sql .= ' AND ' . implode(' AND ', $where);
}


switch ($sort) {
    case 'price_asc':
        $sql .= ' ORDER BY p.productPrice ASC';
        break;
    case 'price_desc':
        $sql .= ' ORDER BY p.productPrice DESC';
        break;
    case 'bestseller':
        $sql .= ' ORDER BY p.salesCount DESC';
        break;
    default:
        $sql .= ' ORDER BY p.productID DESC'; // or any default sorting you prefer
        break;
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

                <label for="sort">Sort By:</label><br>
                <select name="sort" id="sort">
                    <option value="">Default</option>
                    <option value="price_asc" <?= req('sort') == 'price_asc' ? 'selected' : '' ?>>Price: Low to High
                    </option>
                    <option value="price_desc" <?= req('sort') == 'price_desc' ? 'selected' : '' ?>>Price: High to Low
                    </option>
                    <option value="bestseller" <?= req('sort') == 'bestseller' ? 'selected' : '' ?>>Best Seller</option>
                </select><br><br>


                <label for="minPrice">Min Price:</label><br>
                <input type="number" name="minPrice" id="minPrice" min="0" value="<?= $minPrice ?>"><br><br>

                <label for="maxPrice">Max Price:</label><br>
                <input type="number" name="maxPrice" id="maxPrice" min="0" value="<?= $maxPrice ?>"><br><br>

                <button type="submit">Apply Filter</button>
            </form>
        </aside>
        <div class="product-grid">
            <?php foreach ($arr as $p): ?>
                <div class="product-container">
                    <a href="productDetails.php?id=<?= $p->productID ?>" class="overlay-link">
                        <?php $firstImage = explode(',', $p->productPicture)[0]; ?>
                        <img src="/images/<?= trim($firstImage) ?>" class="product-image">
                        <h1 class="product-title"><?= $p->productName ?></h1>
                        <p cla0ss="product-price">RM<?= $p->productPrice ?></p>
                        <p class="product-description"><?= $p->productDescription ?></p>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form action="/cartItem/addCartItem.php" method="POST">
                            <input type="hidden" name="userID" value="<?= $_SESSION['user_id'] ?>">
                            <input type="hidden" name="productID" value="<?= $p->productID ?>">
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