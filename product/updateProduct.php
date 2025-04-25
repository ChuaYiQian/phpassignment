<?php
include '../base.php';
session_start();

// ----------------------------------------------------------------------------
$productID = $_GET['id'];
$categoryOptions = [];
try {
    $stm = $_db->query("SELECT categoryID, categoryName FROM category");
    if ($stm) {
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $categoryOptions[$row['categoryID']] = $row['categoryName'];
        }
    }
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

if (!is_post()) {
    $stm = $_db->prepare("SELECT * FROM product WHERE productID = ?");
    $stm->execute([$productID]);
    $row = $stm->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        foreach ($row as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    } else {
        die("Product not found");
    }
}

if (is_post()) {
    $categoryID = req("categoryID");
    $productName = req('productName');
    $productDescription = req('productDescription');
    $productPrice = req('productPrice');
    $productQuantity = req('productQuantity');
    $productStatus = req('productStatus');
    $salesCount = req('salesCount');
    $photos = $_FILES['photo'];
    $validPhotos = [];
    $hasNewPhotos = false;
    $uploadDir = '../images/';

    //for photos
    for ($i = 0; $i < count($photos['name']); $i++) {
        if ($photos['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $hasNewPhotos = true;

        $name = basename($photos['name'][$i]);
        $type = $photos['type'][$i];
        $tmp_name = $photos['tmp_name'][$i];
        $size = $photos['size'][$i];

        if (!str_starts_with($type, 'image/')) {
            $_err['photo'] = 'All files must be images';
            break;
        } else if ($size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Each file must be under 1MB';
            break;
        }

        if (move_uploaded_file($tmp_name, "$uploadDir/$name")) {
            $validPhotos[] = $name;
        }
    }


    // Validate: product picture
    if ($hasNewPhotos && empty($validPhotos)) {
        $_err['photo'] = 'At least one valid photo is required.';
    }

    if (!$hasNewPhotos) {
        $stm = $_db->prepare("SELECT productPicture FROM product WHERE productID = ?");
        $stm->execute([$productID]);
        $photoString = $stm->fetchColumn();
    } else {
        $photoString = implode(',', $validPhotos);
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
    } else if ($productPrice < 0.01) {
        $_err['productPrice'] = 'Cannot less than 0.01';
    }

    // Validate: quantity
    if ($productQuantity == '') {
        $_err['productQuantity'] = 'Required';
    } else if ($productQuantity < 1) {
        $_err['productQuantity'] = 'Cannot less than 1';
    }

    // Validate: status
    if (!$productStatus) {
        $_err['productStatus'] = 'Required';
    } else if (!in_array($productStatus, ['available', 'unavailable'])) {
        $_err['productStatus'] = 'Invalid selection';
    }

    if (empty($_err)) {
        try {
            if ($hasNewPhotos) {
                $photoString = implode(',', $validPhotos);
            }

            $stm = $_db->prepare('
            UPDATE product SET productName=?, 
            productDescription=?, productPrice=?, productPicture=?, productQuantity=?, 
            productStatus=?, categoryID=? WHERE productID=?
        ');

            $stm->execute([
                $productName,
                $productDescription,
                $productPrice,
                $photoString,
                $productQuantity,
                $productStatus,
                $categoryID,
                $productID
            ]);

            temp('info', 'Record updated successfully');
            header('Location: /product/productMaintenance.php');
            exit();
        } catch (PDOException $e) {
            die("Error inserting data: " . $e->getMessage());
        }
    }

}

// ----------------------------------------------------------------------------

$_title = 'Product | Update';
?>
<link rel="stylesheet" href="/css/insertproduct.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/js/insertproduct.js"></script>
<div class="container">
    <div class="topbar-Goback">
        <a href="/product/productMaintenance.php">
            <img src="/images/goBackIcon.png" alt="" width="40px" height="40px">
        </a>
        <div class="topbar-text">
            <h1>Go Back</h1>
        </div>
    </div>
    <form method="post" class="form" enctype="multipart/form-data" novalidate>
        <h1 class="title">Update Product</h1>
        <div class="form-group">
            <label for="id">Product ID: <?php echo $productID ?></label>
            <?= html_hidden('productID'); ?>
        </div>

        <div class="form-group">
        <label for="id">Category ID</label>
        <?= html_select('categoryID', $categoryOptions) ?>
            <?= err('categoryID') ?>
        </div>

        <div class="form-group">
            <label for="name">Name</label>
            <?= html_text('productName', 'maxlength="100"') ?>
            <?= err('productName') ?>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <?= html_text('productDescription', 'maxlength="100"') ?>
            <?= err('productDescription') ?>
        </div>

        <div class="form-group">
            <label for="price">Price (RM)</label>
            <?= html_number('productPrice', 0.01, 99.99, 0.01) ?>
            <?= err('productPrice') ?>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity</label>
            <?= html_number('productQuantity', 1, 99, 1) ?>
            <?= err('productQuantity') ?>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <?= html_select('productStatus', ['available' => 'Available', 'unavailable' => 'Unavailable']) ?>
            <?= err('productStatus') ?>
        </div>

        <div class="form-group">
            <label for="photo">Photo</label>
            <label class="upload">
                <?= html_file('photo[]', 'image/*', 'hidden multiple') ?>
                <?php
                $firstPhoto = '';
                if (!empty($productPicture)) {
                    $photos = explode(',', $productPicture);
                    $firstPhoto = trim($photos[0]);
                }
                ?>
                <img src="<?= $firstPhoto ? "/images/$firstPhoto" : '/images/photo.jpg' ?>" alt="Preview">
            </label><br /><br /><br /><br />
            <?= err('photo') ?>
        </div>

        <section>
            <button class="formButton">Submit</button>
            <button class="formButton" type="reset">Reset</button>
        </section>
    </form>
</div>
<?php
include '../footer.php';