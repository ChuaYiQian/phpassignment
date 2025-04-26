<?php
require_once 'lib/phpqrcode/qrlib.php';
include 'base.php';

$orderID = $_GET['orderID'] ?? '';
$paymentID = $_GET['paymentID'] ?? '';

$total = 0;
$discount = 0;
$shippingFee = 5.00;

// Get products
$sql = "
    SELECT p.productPrice, oi.orderQuantity
    FROM orderinformation oi
    JOIN product p ON oi.productID = p.productID
    WHERE oi.orderID = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $total += $row['productPrice'] * $row['orderQuantity'];
    }
}

// Get discount
session_start();
$discount = $_SESSION['discount'] ?? 0;
$discountAmount = $total * ($discount / 100);

// Get tax rate
$taxRate = 0.00;
if ($paymentID) {
    $stmt = $conn->prepare("SELECT * FROM paymentmethod WHERE paymentID = ?");
    $stmt->bind_param("s", $paymentID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $taxRate = floatval($row['taxRate']) / 100;
    }
}
$taxAmount = $total * $taxRate;

$finalTotal = $total - $discountAmount + $taxAmount + $shippingFee;

// Generate QR
ob_start();
$qrContent = "Pay RM" . number_format($finalTotal, 2) . " for Order #" . $orderID;
QRcode::png($qrContent, null, QR_ECLEVEL_L, 4);
$imageString = base64_encode(ob_get_clean());

// Return as JSON
echo json_encode([
    'qr' => $imageString,
    'amount' => $finalTotal
]);
