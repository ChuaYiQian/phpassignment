<?php 
include '../base.php';
session_start();

if (is_post()) {
    $description = req('description');
    $taxRate = req('tax');
    $category = req('category');
    $icon = $_FILES['icon'];
    $_err = [];
    $uploadDir = '../images/';
    $savedIcon = '';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $name = basename($icon['name']);
    $type = $icon['type'];
    $tmp_name = $icon['tmp_name'];
    $size = $icon['size'];

    if (!str_starts_with($type, 'image/')) {
        $_err['icon'] = 'File must be an image';
    } else if ($size > 1 * 1024 * 1024) {
        $_err['icon'] = 'Image must be under 1MB';
    } else {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $newName;

        if (move_uploaded_file($tmp_name, $targetPath)) {
            $savedIcon = 'images/' . $newName; 
        } else {
            $_err['icon'] = 'Failed to upload image';
        }
    }

    if (empty($_err)) {
        $result = $_db->query("SELECT paymentID FROM paymentMethod ORDER BY paymentID DESC LIMIT 1");
        $lastID = $result->fetchColumn();
        $newID = $lastID ? 'PM' . str_pad(substr($lastID, 2) + 1, 3, '0', STR_PAD_LEFT) : 'PM001';

        try {
            $stm = $_db->prepare("
                INSERT INTO paymentMethod (paymentID, paymentDescription, paymentIcon, taxRate, category)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stm->execute([$newID, $description, $savedIcon, $taxRate, $category]);

            temp('info', 'Payment method added successfully');
            header("Location: ../payment_table.php");
            exit();
        } catch (PDOException $e) {
            die("Insert error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/insertproduct.js"></script>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/insert.css">
    <title>Add Payment Method</title>
</head>
<body>
    <div class="container">
        <h1>Add Payment Method</h1>
        <form method="post" enctype="multipart/form-data" novalidate>
            <label>Description</label>
            <?= html_text('description') ?>
            <?= err('description') ?>

            <label>Tax Rate (%)</label>
            <?= html_number('tax', 0, 100, 0.1) ?>
            <?= err('tax') ?>

            <label>Category</label>
            <label>
                <input type="radio" name="category" value="Credit/Debit Card" required> Credit/Debit Card
            </label>
            <label>
                <input type="radio" name="category" value="E-wallet" required> E-wallet
            </label>
            <label>
                <input type="radio" name="category" value="Online Banking" required> Online Banking
            </label>
            <label>
                <input type="radio" name="category" value="Bank" required> FPX Only - Bank
            </label>
            <?= err('category') ?>

            <label>Upload Icon</label>
            <div class="upload-wrapper">
                <label class="upload">
                    <input type="file" name="icon" hidden onchange="previewImage(this)">
                    <img src="/images/photo.jpg" onclick="this.previousElementSibling.click();" id="preview">
                </label>
            </div>
            <?= err('icon') ?>

            <button type="submit" class="formButton">Add</button>
            <button type="reset" class="formButton" style="background: gray;">Reset</button>
        </form>
    </div>
</body>
</html>
