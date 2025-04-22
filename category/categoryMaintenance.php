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
$arr = $_db->query('SELECT * FROM category')->fetchAll();
$fields = [
    'categoryID' => 'Id',
    'categoryName' => 'Name',
    'createdDate' => 'Created Date'
];

//Only need to sorting
$sortable = ['categoryID', 'categoryName','createdDate'];
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

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$page = req('page', 1);
require_once '../lib/SimplePager.php';

$p = new SimplePager("SELECT * FROM category $where_sql ORDER BY $sort $dir", $params, 10, $page);
$arr = $p->result;
?>

<p>
    <a href="/category/insertCategory.php">Insert</a>
</p>

<p><?= count($arr) ?> record(s)</p>
<link rel="stylesheet" href="/css/productMaintenance.css">
<form>
    Product ID: <?= html_search('categoryID') ?>
    Product Name: <?= html_search('categoryName') ?>
    <button>Search</button>
</form>
<table class="table">
    <tr>
        <th colspan="4" style="font-size: 25px;background-color:rgba(0, 0, 0, 0.11);">Category Table</th>
    </tr>
    <tr>
        <?= table_headers($fields, $sort, $dir, "page=$page", $sortable) ?>
        <th></th>
    </tr>

    <?php foreach ($arr as $prod): ?>
        <tr>
            <td><?= $prod->categoryID ?></td>
            <td><?= $prod->categoryName ?></td>
            <td><?= $prod->createdDate ?></td>
            <td>
                <a href="/category/updateCategory.php?id=<?= $prod->categoryID ?>">Update</a>
                <a href="/category/deleteCategory.php?id=<?= $prod->categoryID ?>"
                    onclick="return confirm('Delete this category?')">Delete</a>
            </td>
        </tr>
    <?php endforeach ?>
    <?= $p->html("sort=$sort&dir=$dir") ?>
</table>