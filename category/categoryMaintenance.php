<?php
include '../base.php';
session_start();

if ($msg = temp('info')) {
    echo "<div class='success-message'>$msg</div>";
} elseif ($msg = temp('error')) {
    echo "<div class='error-message'>$msg</div>";
}


$id = req('categoryID');
$name = req('categoryName');
$status = req('categoryStatus');
$arr = $_db->query('SELECT * FROM category')->fetchAll();
$fields = [
    'categoryID' => 'Id',
    'categoryName' => 'Name',
    'categoryStatus' => 'Status',
    'createdDate' => 'Created Date'
];

//Only need to sorting
$sortable = ['categoryID', 'categoryName', 'createdDate'];
$sort = req('sort');
in_array($sort, $sortable) || $sort = 'categoryID';

$dir = req('dir');
in_array($dir, ['asc', 'desc']) || $dir = 'asc';
$where = [];
$params = [];

if ($id !== '') {
    $where[] = 'categoryID LIKE ?';
    $params[] = "%$id%";
}

if ($name !== '') {
    $where[] = 'categoryName LIKE ?';
    $params[] = "%$name%";
}

if ($status !== '') {
    $where[] = 'categoryStatus LIKE ?';
    $params[] = "%$status%";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$page = req('page', 1);
require_once '../lib/SimplePager.php';

$p = new SimplePager("SELECT * FROM category $where_sql ORDER BY $sort $dir", $params, 10, $page);
$arr = $p->result;
?>
<?php include '../adminheader.php'; ?>
<br />
<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?>
    <a href="/category/insertCategory.php">Insert New Category</a>
</p>
<link rel="stylesheet" href="/css/productMaintenance.css">
<br />
<form>
    Product ID: <?= html_search('categoryID') ?>
    Product Name: <?= html_search('categoryName') ?>
    Status:
    <select name="categoryStatus">
        <option value="">All</option>
        <option value="available" <?= req('categoryStatus') === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="unavailable" <?= req('categoryStatus') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
    </select>
    <button>Search</button>
</form>
<br />
<table class="table">
    <tr>
        <th colspan="5" style="font-size: 25px;background-color:rgba(0, 0, 0, 0.11);">Category Table</th>
    </tr>
    <tr>
        <?= table_headers($fields, $sort, $dir, "page=$page", $sortable) ?>
        <th>Action</th>
    </tr>

    <?php foreach ($arr as $prod): ?>
        <tr>
            <td><?= $prod->categoryID ?></td>
            <td><?= $prod->categoryName ?></td>
            <td><?= $prod->categoryStatus ?></td>
            <td><?= $prod->createdDate ?></td>
            <td>
                <a href="/category/updateCategory.php?id=<?= $prod->categoryID ?>">Update</a>
            </td>
        </tr>
    <?php endforeach ?>
    <?= $p->html("sort=$sort&dir=$dir") ?>
</table>