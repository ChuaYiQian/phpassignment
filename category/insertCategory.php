<?php
include '../base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $categoryID = req('categoryID');
    $categoryName = req("categoryName");
    $categoryStatus  = req('categoryStatus');

    // Validate: product id
    if ($categoryID == '') {
        $_err['categoryID'] = 'Required';
    }
    else if (!preg_match('/^C\d{3}$/', $categoryID)) {
        $_err['categoryID'] = 'Invalid format';
    }
    else if (!is_unique($categoryID, 'category', 'categoryID')) {
        $_err['categoryID'] = 'Duplicated';
    }

    // Validate: name
    if ($categoryName == '') {
        $_err['categoryName'] = 'Required';
    }
    else if (strlen($categoryName) > 100) {
        $_err['categoryName'] = 'Maximum 100 characters';
    }

    // Validate: status
    if (!$categoryStatus) {
        $_err['categoryStatus'] = 'Required';
    } 
    else if (!in_array($categoryStatus, ['available', 'unavailable'])) {
        $_err['categoryStatus'] = 'Invalid selection';
    }

    if (empty($_err)) {
        try {
            $stm = $_db->prepare('
                INSERT INTO category (categoryID, categoryName, categoryStatus)
                VALUES (?, ?, ?)
            ');
    
            $stm->execute([$categoryID, $categoryName, $categoryStatus]);
    
            temp('info', 'Record inserted successfully');
            header('Location: /category/categoryMaintenance.php');
    
        } catch (PDOException $e) {
            die("Error inserting data: " . $e->getMessage());
        }
    }
}

// ----------------------------------------------------------------------------

$_title = 'Category | Insert';
include '../header.php';
?>
<link rel="stylesheet" href="/css/insertproduct.css">

<form method="post" class="form" enctype="multipart/form-data" novalidate>
    <label for="id">Category ID</label>
    <?= html_text('categoryID', 'maxlength="4" placeholder="P999" data-upper') ?>
    <?= err('categoryID') ?>

    <label for="name">Name</label>
    <?= html_text('categoryName', 'maxlength="100"') ?>
    <?= err('categoryName') ?>

    <label for="status">Status</label>
    <?= html_select('categoryStatus',['available' => 'Available', 'unavailable' => 'Unavailable']) ?>
    <?= err('categoryStatus') ?>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>
<?php
include '../footer.php';