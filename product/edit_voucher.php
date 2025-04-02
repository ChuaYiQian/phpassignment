<?php
include '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid voucher ID.");
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM vouchers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Error: Voucher not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;

    $sql = "UPDATE vouchers SET code=?, discount=?, status=?, quantity=?, expiry_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsssi", $code, $discount, $status, $quantity, $expiry_date, $id);
    
    if ($stmt->execute()) {
        header("Location: voucher_table.php");
        exit;
    } else {
        echo "Error updating voucher: " . $stmt->error;
    }
}
?>

<form method="POST">
    Code: <input type="text" name="code" value="<?= htmlspecialchars($row['code'] ?? '') ?>" required><br>
    Discount: <input type="number" name="discount" step="0.01" value="<?= $row['discount'] ?? '0.00' ?>" required><br>
    Quantity: <input type="number" name="quantity" value="<?= $row['quantity'] ?? '0' ?>" required><br>
    Status: 
    <select name="status">
        <option value="Active" <?= isset($row['status']) && $row['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
        <option value="Expired" <?= isset($row['status']) && $row['status'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
    </select><br>
    Expiry Date: <input type="date" name="expiry_date" value="<?= $row['expiry_date'] ?? '' ?>"><br>
    <button type="submit">Update</button>
</form>

