<?php
include '../base.php';
session_start();

//roles validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

// ----------------------------------------------------------------------------
$categoryID = $_GET['id'];

if (!is_post()) {
    $stm = $_db->prepare("SELECT * FROM category WHERE categoryID = ?");
    $stm->execute([$categoryID]);
    $row = $stm->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        foreach ($row as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    } else {
        die("Category not found");
    }
}

if (is_post()) {
    $categoryName = req("categoryName");
    $categoryStatus  = req('categoryStatus');

    // Validate: name
    if ($categoryName == '') {
        $_err['categoryName'] = 'Required';
    }
    else if (strlen($categoryName) > 15) {
        $_err['categoryName'] = 'Maximum 15 characters';
    }

    // Validate: status
    if (!$categoryStatus) {
        $_err['categoryStatus'] = 'Required';
    } else if (!in_array($categoryStatus, ['available', 'unavailable'])) {
        $_err['categoryStatus'] = 'Invalid selection';
    }

    if (empty($_err)) {
        try {
            $stm = $_db->prepare('
                UPDATE category SET categoryName=?, categoryStatus=? WHERE categoryID=?
            ');
    
            $stm->execute([$categoryName, $categoryStatus, $categoryID]);
    
            temp('info', 'Record updated successfully');
            header('Location: /category/categoryMaintenance.php');
    
        } catch (PDOException $e) {
            die("Error inserting data: " . $e->getMessage());
        }
    }
}

// ----------------------------------------------------------------------------

$_title = 'Category | Update';
?>
<link rel="stylesheet" href="/css/insertproduct.css">

<form method="post" class="form" enctype="multipart/form-data" novalidate>
    <h1 class="title">Update Category</h1>
    <div class="form-group">
    <label for="id">Category ID: <?php echo $categoryID ?></label>
    <?= html_hidden('categoryID'); ?>
    </div>

    <div class="form-group">
    <label for="name">Name</label>
    <?= html_text('categoryName', 'maxlength="15"') ?>
    <?= err('categoryName') ?>
    </div>

    <div class="form-group">
    <label for="status">Status</label>
    <?= html_select('categoryStatus', ['available' => 'Available', 'unavailable' => 'Unavailable']) ?>
        <?= err('categoryStatus') ?>
    </div>

    <section>
        <button>Submit</button>
        <button type="reset">Reset</button>
    </section>
</form>
