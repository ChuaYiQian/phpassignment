<?php 
include '../base.php';  

if (!isset($_GET['id']) || empty($_GET['id'])) { 
    die("Invalid voucher ID."); 
}

$id = $_GET['id'];  

// Fetch existing voucher details
$stmt = $conn->prepare("SELECT * FROM voucher WHERE voucherID = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();  

if (!$row) { 
    die("Error: Voucher not found."); 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $code = $_POST['code']; 
    $discount = $_POST['discount']; 
    $status = $_POST['status']; 
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL; 

    // Update the voucher in the database
    $sql = "UPDATE voucher SET voucherCode=?, discountRate=?, voucherStatus=?, endDate=? WHERE voucherID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $code, $discount, $status, $expiry_date, $id); 

    if ($stmt->execute()) { 
        header("Location: ../voucher_table.php"); 
        exit; 
    } else { 
        echo "Error updating voucher: " . $stmt->error; 
    } 
} 
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Voucher</title>
</head>
<body>
    <h2>Edit Voucher</h2>
    <form method="POST">
        <label>Code: <input type="text" name="code" value="<?= htmlspecialchars($row['voucherCode'] ?? '') ?>" required></label><br>
        <label>Discount: <input type="number" name="discount" step="0.01" value="<?= $row['discountRate'] ?? '0.00' ?>" required></label><br>
        <label>Status:             
            <select name="status">
                <option value="Active" <?= isset($row['voucherStatus']) && $row['voucherStatus'] == 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Expired" <?= isset($row['voucherStatus']) && $row['voucherStatus'] == 'Expired' ? 'selected' : '' ?>>Expired</option>
            </select>
        </label><br>
        <label>Expiry Date: <input type="date" name="expiry_date" value="<?= $row['endDate'] ?? '' ?>"></label><br>
        <button type="submit">Update</button>
    </form>

    <script> 
    document.addEventListener("DOMContentLoaded", function() {
        const expiryInput = document.querySelector('input[name="expiry_date"]');
        const statusSelect = document.querySelector('select[name="status"]');
        
        // Function to update the voucher status
        function updateStatus() {
            const selectedDate = new Date(expiryInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                statusSelect.value = "Expired";
            } else {
                statusSelect.value = "Active";
            }
        }

        // Set initial status based on the expiry date
        updateStatus();

        // Event listener to update status when expiry date changes
        expiryInput.addEventListener("change", updateStatus);
    });
    </script>
</body>
</html>
