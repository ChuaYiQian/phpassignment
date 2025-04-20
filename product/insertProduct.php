<?php
include '../base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $productID = req('productID');
    $categoryID = req("categoryID");
    $productName = req('productName');
    $productDescription = req('productDescription');
    $productPrice = req('productPrice');
    $productQuantity = req('productQuantity');
    $productStatus = req('productStatus');
    $salesCount = req('salesCount');
    $f = get_file('photo');

    // Validate: product id
    if ($productID == '') {
        $_err['productID'] = 'Required';
    } else if (!preg_match('/^P\d{3}$/', $productID)) {
        $_err['productID'] = 'Invalid format';
    } else if (!is_unique($productID, 'product', 'productID')) {
        $_err['productID'] = 'Duplicated';
    }

    //Validate: category id
    if ($categoryID == '') {
        $_err['categoryID'] = 'Required';
    } else if (!preg_match('/^C\d{3}$/', $categoryID)) {
        $_err['categoryID'] = 'Invalid format';
    } else if (!is_exists($categoryID, 'category', 'categoryID')) {
        $_err['categoryID'] = 'CategoryID is not exists.';
    }

    // Validate: name
    if ($productName == '') {
        $_err['productName'] = 'Required';
    } else if (strlen($productName) > 100) {
        $_err['productName'] = 'Maximum 100 characters';
    }

    // Validate: description
    if ($productDescription == '') {
        $_err['productDescription'] = 'Required';
    } else if (strlen($productDescription) > 255) {
        $_err['productDescription'] = 'Maximum 255 characters';
    }

    // Validate: price
    if ($productPrice == '') {
        $_err['productPrice'] = 'Required';
    } else if (!is_money($productPrice)) {
        $_err['productPrice'] = 'Must be money';
    } else if ($productPrice < 0.01 || $productPrice > 99.99) {
        $_err['productPrice'] = 'Must between 0.01 - 99.99';
    }

    // Validate: quantity
    if ($productQuantity == '') {
        $_err['productQuantity'] = 'Required';
    } else if (!is_money($productQuantity)) {
        $_err['productQuantity'] = 'Must be money';
    } else if ($productQuantity < 1 || $productQuantity > 99) {
        $_err['productQuantity'] = 'Must between 1 - 99';
    }

    // Validate: status
    if (!$productStatus) {
        $_err['productStatus'] = 'Required';
    } else if (!in_array($productStatus, ['available', 'unavailable'])) {
        $_err['productStatus'] = 'Invalid selection';
    }

    // Validate: sales count
    if ($salesCount == '') {
        $_err['salesCount'] = 'Required';
    } else if (!is_money($salesCount)) {
        $_err['salesCount'] = 'Must be money';
    } else if ($salesCount < 1 || $salesCount > 99) {
        $_err['salesCount'] = 'Must between 1 - 99';
    }

    // Validate: photo (file)
    if (!$f) {
        $_err['photo'] = 'Required';
    } else if (!str_starts_with($f->type, 'image/')) {
        $_err['photo'] = 'Must be image';
    } else if ($f->size > 1 * 1024 * 1024) {
        $_err['photo'] = 'Maximum 1MB';
    }

    if (empty($_err)) {
        try {
            move_uploaded_file($f->tmp_name, "uploads/$f->name");
            $photo = save_photo($f, '../images');
            $stm = $_db->prepare('
                INSERT INTO product (productID, productName, productDescription, productPrice, productPicture, productQuantity, productStatus, salesCount, categoryID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $stm->execute([$productID, $productName, $productDescription, $productPrice, $photo, $productQuantity, $productStatus, $salesCount, $categoryID]);

            temp('info', 'Record inserted successfully');
            header('Location: /product/productMaintenance.php');

        } catch (PDOException $e) {
            die("Error inserting data: " . $e->getMessage());
        }
    }
}

// ----------------------------------------------------------------------------

$_title = 'Product | Insert';
include '../header.php';
?>
<link rel="stylesheet" href="/css/insertproduct.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/js/insertproduct.js"></script>


<form method="post" class="form" enctype="multipart/form-data" novalidate>
    <h1 class="title">Insert Product</h1>
    <label for="id">Product ID</label>
    <?= html_text('productID', 'maxlength="4" placeholder="P999" data-upper') ?>
    <?= err('productID') ?>

    <label for="id">Category ID</label>
    <?= html_text('categoryID', 'maxlength="4" placeholder="C999" data-upper') ?>
    <?= err('categoryID') ?>

    <label for="name">Name</label>
    <?= html_text('productName', 'maxlength="100"') ?>
    <?= err('productName') ?>

    <label for="description">Description</label>
    <?= html_text('productDescription', 'maxlength="100"') ?>
    <?= err('productDescription') ?>

    <label for="price">Price (RM)</label>
    <?= html_number('productPrice', 0.01, 99.99, 0.01) ?>
    <?= err('productPrice') ?>

    <label for="quantity">Quantity</label>
    <?= html_number('productQuantity', 1, 99, 1) ?>
    <?= err('productQuantity') ?>

    <label for="status">Status</label>
    <?= html_select('productStatus', ['available' => 'Available', 'unavailable' => 'Unavailable']) ?>
    <?= err('productStatus') ?>

    <label for="sales">Sales Count</label>
    <?= html_number('salesCount', 1, 99, 1) ?>
    <?= err('salesCount') ?>

    <label for="photo">Photo</label>
    <label class="upload">
        <?= html_file('photo', 'image/*', 'hidden') ?>
        <img src="/images/photo.jpg">
    </label>
    <?= err('photo') ?>

    <section>
        <button class="formButton">Submit</button>
        <button class="formButton" type="reset">Reset</button>
    </section>
</form>
<?php
include '../footer.php';