<?php
include '../base.php';

// Check if ID is set
if (!isset($_POST['id']) || empty($_POST['id'])) {
    die("Invalid voucher ID.");
}

$id = $_POST['id'];
$code = $_POST['code'];
$discount = $_POST['discount'];
$today = date('Y-m-d'); // Determine voucher status based on expiry date
if (!empty($expiry_date) && strtotime($expiry_date) < strtotime($today)) {
    $status = "Expired";
} else {
    $status = "Active";
}
$expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;

$sql = "UPDATE voucher SET 
        voucherCode = ?, discountRate = ?, voucherStatus = ?, endDate = ? 
        WHERE voucherID = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("sdsss", $code, $discount, $status, $expiry_date, $id);

if ($stmt->execute()) {
    header("Location: ../voucher_table.php");
    exit();
} else {
    echo "Error updating voucher: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
