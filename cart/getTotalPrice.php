<?php
include '../base.php';

if (!isset($_POST['selected'])) {
    echo "0.00";
    exit;
}

$selected = json_decode($_POST['selected'], true);

if (!is_array($selected) || empty($selected)) {
    echo "0.00";
    exit;
}

$placeholders = rtrim(str_repeat('?,', count($selected)), ',');
$sql = "
    SELECT p.productPrice, c.cartQuantity 
    FROM cartItem c 
    JOIN product p ON c.productID = p.productID 
    WHERE c.productID IN ($placeholders)
";
$stmt = $_db->prepare($sql);
$stmt->execute($selected);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['productPrice'] * $item['cartQuantity'];
}

echo number_format($total, 2);
