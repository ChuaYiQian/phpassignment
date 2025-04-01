<?php
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $quantity = $_POST['quantity'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    $status = "unused"; 

    $sql = "INSERT INTO vouchers (code, discount, status, quantity, created_at, expiry_date) 
            VALUES (?, ?, ?, ?, NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsis", $code, $discount, $status, $quantity, $expiry_date);
    $stmt->execute();
    
    header("Location: voucher_table.php");
    exit();
}
?>

<form method="POST">
    Code: <input type="text" name="code" required>
    Discount: <input type="number" name="discount" step="0.01" required>
    Quantity: <input type="number" name="quantity" required>
    Expiry Date: <input type="date" name="expiry_date"> 
    <button type="submit">Add Voucher</button>
</form>
