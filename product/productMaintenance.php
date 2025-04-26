<?php
include '../base.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

if ($msg = temp('info')) {
    echo "<div class='success-message'>$msg</div>";
} elseif ($msg = temp('error')) {
    echo "<div class='error-message'>$msg</div>";
}

//For Searching and Sorting
$id = req('productID');
$name = req('productName');
$status = req('productStatus');
$categoryID = req('productCategory');

$categories = $_db->query("SELECT * FROM category")->fetchAll();

//Table name
$fields = [
    'productID' => 'Id',
    'productName' => 'Name',
    'categoryName' => 'Category',
    'productPrice' => 'Price',
    'productPicture' => 'Picture',
    'productDescription' => 'Description',
    'productQuantity' => 'Quantity',
    'productStatus' => 'Status',
    'salesCount' => 'Sales Count',
    'createdDate' => 'Created Date'
];

//Only need to sorting
$sortable = ['productID', 'productName', 'categoryID', 'productPrice', 'productQuantity', 'salesCount'];
$sort = req('sort');
in_array($sort, $sortable) || $sort = 'productID';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';

//For searching and sorting
$where = [];
$params = [];

if ($id !== '') {
    $where[] = 'productID LIKE ?';
    $params[] = "%$id%";
}

if ($name !== '') {
    $where[] = 'productName LIKE ?';
    $params[] = "%$name%";
}
if ($status !== '') {
    $where[] = 'productStatus = ?';
    $params[] = $status;
}
if ($categoryID !== '') {
    $where[] = 'p.categoryID = ?';
    $params[] = $categoryID;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Paging
$page = req('page', 1);
require_once '../lib/SimplePager.php';

$p = new SimplePager("SELECT p.*, c.categoryName FROM product p LEFT JOIN category c ON p.categoryID = c.categoryID $where_sql ORDER BY $sort $dir", $params, 10, $page);
$arr = $p->result;
?>
<?php include '../adminheader.php'; ?>
<br />
<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
    <a href="/product/insertProduct.php">Insert New Product</a>
</p>
<link rel="stylesheet" href="/css/productMaintenance.css">
<br />
<form>
    Product ID: <?= html_search('productID') ?>
    Product Name: <?= html_search('productName') ?>
    Category:
    <select name="productCategory">
        <option value="">All</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= $c->categoryID ?>" <?= req('productCategory') === $c->categoryID ? 'selected' : '' ?>>
                <?= $c->categoryName ?>
            </option>
        <?php endforeach ?>
    </select>
    Status:
    <select name="productStatus">
        <option value="">All</option>
        <option value="available" <?= req('productStatus') === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="unavailable" <?= req('productStatus') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
    </select>
    <button>Search</button>
</form>
<br />
<table class="table">
    <tr>
        <th colspan="11" style="font-size: 25px;background-color:rgba(0, 0, 0, 0.11);">Product Table</th>
    </tr>
    <tr>
        <?= table_headers($fields, $sort, $dir, "page=$page", $sortable) ?>
        <th>Action</th>
    </tr>
    <?php foreach ($arr as $prod): ?>
        <tr>
            <td><?= $prod->productID ?></td>
            <td><?= $prod->productName ?></td>
            <td><?= $prod->categoryName ?></td>
            <td>RM <?= $prod->productPrice ?></td>
            <td>
                <?php
                $photos = explode(',', $prod->productPicture);
                foreach ($photos as $photo):
                    ?>
                    <img src="/images/<?= trim($photo) ?>" class="popup" style="width: 60px; height: auto; margin: 3px;">
                <?php endforeach; ?>
            </td>
            <td><?= $prod->productDescription ?></td>
            <td>
                <?= $prod->productQuantity ?>
                <?php if ($prod->productQuantity < 15)://if quantity under 15, the alert message will show ?>
                    <span style="color: red; font-weight: bold;">(Low Stock)</span>
                <?php endif; ?>
            </td>

            <td><?= $prod->productStatus ?></td>
            <td><?= $prod->salesCount ?></td>
            <td><?= $prod->createdDate ?></td>
            <td>
                <a href="/product/updateProduct.php?id=<?= $prod->productID ?>">Update</a>
            </td>
        </tr>
    <?php endforeach ?>
    <?= $p->html("sort=$sort&dir=$dir") ?>
</table>