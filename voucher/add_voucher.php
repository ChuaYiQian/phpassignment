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

    // Determine voucher status based on expiry date
    $today = date('Y-m-d');
    if (strtotime($endDate) < strtotime($today)) {
        $voucherStatus = "Expired";
    } else {
        $voucherStatus = "Active";
    }

    // Generate the new voucherID
    $result = $conn->query("SELECT voucherID FROM voucher ORDER BY voucherID DESC LIMIT 1");
    $lastID = $result->fetch_row()[0];

    if ($lastID) {
        $number = (int) substr($lastID, 1); 
        $newID = 'V' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);  
    } else {
        $newID = 'V0001';  
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
    <link rel="stylesheet" href="../css/insert.css">
    <title>Add Voucher</title>
</head>
<body>
    <div class="container">
        <h2>Add New Voucher</h2>
        <form method="POST">
            <label for="code">Code</label>
            <input type="text" name="code" id="code" required>

            <label for="discount">Discount (%)</label>
            <?= html_number('discount', 0, 100, 1) ?>
            <?= err('discount') ?>

            <label for="expiry_date">Expiry Date</label>
            <input type="date" name="expiry_date" id="expiry_date">

            <button type="submit" class="formButton">Add Voucher</button>
            <button type="reset" class="formButton reset">Reset</button>
        </form>
    </div>
</body>
</html>

<?php
if (isset($stmt) && $stmt) {
    $stmt->close();
}

$conn->close();
?>
