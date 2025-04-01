<?php
include '../db.php';

$id = $_POST['id'];
$code = $_POST['code'];
$discount = $_POST['discount'];
$status = $_POST['status'];
$quantity = $_POST['quantity'];

$sql = "UPDATE vouchers SET 
        code='$code', discount='$discount', `status`='$status', quantity='$quantity' 
        WHERE id=$id";

$conn->query($sql);
$conn->close();
header("Location: voucher_table.php");
exit();
?>
