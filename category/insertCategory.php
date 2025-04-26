<?php
include '../base.php';
session_start();

//roles validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}

// ----------------------------------------------------------------------------

try {
    $stmt = $_db->query("SELECT COUNT(*) FROM category");
    $categoryCount = $stmt->fetchColumn();
    $nextCategoryID = 'C' . str_pad($categoryCount + 1, 3, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    die("Error generating category ID: " . $e->getMessage());
}

if (!is_post()) {
    $_POST['categoryID'] = $nextCategoryID;
}


if (is_post()) {
    $categoryID = req('categoryID');
    $categoryName = req("categoryName");
    $categoryStatus = req('categoryStatus');

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
?>
<link rel="stylesheet" href="/css/insertproduct.css">
<div class="container">
    <div class="topbar-Goback">
        <a href="/category/categoryMaintenance.php">
            <img src="/images/goBackIcon.png" alt="" width="40px" height="40px">
        </a>
        <div class="topbar-text">
            <h1>Go Back</h1>
        </div>
    </div>
<form method="post" class="form" enctype="multipart/form-data" novalidate>
    <h1 class="title">Insert Category</h1>
    <div class="form-group">
    <label for="id">Category ID: <?= $nextCategoryID ?></label>
    <input type="hidden" name="categoryID" value="<?= $nextCategoryID ?>">
    <?= err('categoryID') ?>
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
</div>