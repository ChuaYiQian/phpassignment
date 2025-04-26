<?php
include '../base.php';
session_start();

//roles validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid payment ID.");
}

$paymentID = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM paymentmethod WHERE paymentID = ?");
$stmt->bind_param("s", $paymentID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Payment method not found.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = $_POST['description'];
    $taxRate = $_POST['tax'];
    $category = $_POST['category'] ?? '';
    $savedIcon = $row['paymentIcon'];

    if (isset($_FILES['icon']) && $_FILES['icon']['error'] == 0) {
        $uploadDir = '../images/';
        $tmp_name = $_FILES['icon']['tmp_name'];
        $type = $_FILES['icon']['type'];
        $size = $_FILES['icon']['size'];
        $name = basename($_FILES['icon']['name']);

        if (str_starts_with($type, 'image/') && $size <= 1 * 1024 * 1024) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $newName = uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($tmp_name, $targetPath)) {
                if (!empty($savedIcon) && file_exists('../' . $savedIcon)) {
                    unlink('../' . $savedIcon);
                }
                $savedIcon = 'images/' . $newName;
            }
        }
    }

    $update = $conn->prepare("UPDATE paymentmethod SET paymentDescription = ?, taxRate = ?, paymentIcon = ?, category = ? WHERE paymentID = ?");
    $update->bind_param("sdsss", $description, $taxRate, $savedIcon, $category, $paymentID);

    if ($update->execute()) {
        header("Location: ../payment_table.php");
        exit();
    } else {
        echo "Update failed: " . $update->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/insert.css">
    <title>Edit Payment Method</title>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Edit Payment Method</h1>
        <form method="post" enctype="multipart/form-data">
            <label>Description</label>
            <input type="text" name="description" value="<?= htmlspecialchars($row['paymentDescription']) ?>" required>

            <label>Tax Rate (%)</label>
            <input type="number" step="0.01" name="tax" value="<?= $row['taxRate'] ?>" required>

            <label>Category</label>
            <label><input type="radio" name="category" value="Credit/Debit Card" <?= $row['category'] == 'Credit/Debit Card' ? 'checked' : '' ?>> Credit/Debit Card</label>
            <label><input type="radio" name="category" value="E-wallet" <?= $row['category'] == 'E-wallet' ? 'checked' : '' ?>> E-wallet</label>
            <label><input type="radio" name="category" value="Online Banking" <?= $row['category'] == 'Online Banking' ? 'checked' : '' ?>> Online Banking</label>
            <label><input type="radio" name="category" value="Bank" <?= $row['category'] == 'Bank' ? 'checked' : '' ?>> FPX Only - Bank</label><br>


            <label>Current Icon</label>
            <div class="upload-wrapper">
                <label class="upload">
                    <input type="file" name="icon" hidden onchange="previewImage(this)">
                    <img src="../<?= $row['paymentIcon'] ?>" id="preview" onclick="this.previousElementSibling.click();">
                </label>
            </div>

            <button type="submit" class="formButton">Update</button>
            <button type="reset" class="formButton" style="background: gray;">Reset</button>
        </form>
    </div>
</body>
</html>
