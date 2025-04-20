<?php
include '../base.php';

$arr = $_db->query('SELECT * FROM category')->fetchAll();

?>

<p>
    <a href="/category/insertCategory.php">Insert</a>
</p>

<p><?= count($arr) ?> record(s)</p>
<link rel="stylesheet" href="/css/productMaintenance.css">
<table class="table">
    <tr>
        <th colspan="4" style="font-size: 25px;background-color:rgba(0, 0, 0, 0.11);">Category Table</th>
    </tr>
    <tr>
        <th>Category</th>
        <th>Name</th>
        <th>Status</th>
        <th></th>
    </tr>

    <?php foreach ($arr as $p): ?>
    <tr>
        <td><?= $p->categoryID ?></td>
        <td><?= $p->categoryName ?></td>
        <td><?= $p->categoryStatus ?></td>
        <td>
            <a href="/category/updateCategory.php?id=<?= $p->categoryID ?>">Update</a>
            <a href="/category/deleteCategory.php?id=<?= $p->categoryID ?>" onclick="return confirm('Delete this category?')">Delete</a>
        </td>
    </tr>
    <?php endforeach ?>
</table>
