<?php
include '../base.php';

$arr = $_db->query('SELECT * FROM product')->fetchAll();

?>

<style>
    .popup {
        width: 100px;
        height: 100px;
    }
</style>

<p>
    <button data-get="/product/insert.php">Insert</button>
    <a href="/product/insert.php">Home</a>
</p>

<p><?= count($arr) ?> record(s)</p>

<table class="table">
    <tr>
        <th>Id</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Picture</th>
        <th>Description</th>
        <th>Quantity</th>
        <th>Status</th>
        <th>SalesCount</th>
        <th></th>
    </tr>

    <?php foreach ($arr as $p): ?>
    <tr>
        <td><?= $p->productID ?></td>
        <td><?= $p->productName ?></td>
        <td><?= $p->categoryID ?></td>
        <td>RM <?= $p->productPrice ?></td>
        <td><img src="/images/<?= $p->productPicture ?>" class="popup"></td>
        <td><?= $p->productDescription ?></td>
        <td><?= $p->productQuantity ?></td>
        <td><?= $p->productStatus ?></td>
        <td><?= $p->salesCount ?></td>
        <td>
            <button data-get="update.php?id=<?= $p->id ?>">Update</button>
            <button data-post="delete.php?id=<?= $p->id ?>">Delete</button>
        </td>
    </tr>
    <?php endforeach ?>
</table>
