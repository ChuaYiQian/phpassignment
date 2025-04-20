<?php
include '../base.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $voucherCode = $_POST['code'];
    $discountRate = $_POST['discount'];
    $startDate = date('Y-m-d'); 

    // Set endDate to a default value if expiry_date is empty
    if (empty($_POST['expiry_date'])) {
        $endDate = date('Y-m-d', strtotime('+1 year')); 
    } else {
        $endDate = $_POST['expiry_date'];
    }

    $voucherStatus = "Active"; 

    // Generate the new voucherID
    $result = $conn->query("SELECT voucherID FROM voucher ORDER BY voucherID DESC LIMIT 1");
    $lastID = $result->fetch_row()[0];

    // Generate the new ID
    if ($lastID) {
        $number = (int) substr($lastID, 1); 
        $newID = 'v' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);  
    } else {
        $newID = 'v001';  
    }

    $sql = "INSERT INTO voucher (voucherID, voucherCode, startDate, endDate, discountRate, voucherStatus) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param("ssssds", $newID, $voucherCode, $startDate, $endDate, $discountRate, $voucherStatus);
    $stmt->execute();

    header("Location: ../voucher_table.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Voucher</title>
</head>
<body>

<h2>Add New Voucher</h2>

<form method="POST">
    <label>Code: <input type="text" name="code" required></label><br>
    <label>Discount: <input type="number" name="discount" step="0.01" required></label><br>
    <label>Expiry Date: <input type="date" name="expiry_date"></label><br>
    <button type="submit">Add Voucher</button>
</form>

</body>
</html>

<?php
if (isset($stmt) && $stmt) {
    $stmt->close();
}

$conn->close();
?>
