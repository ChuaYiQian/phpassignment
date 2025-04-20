<?php
include '../base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedItems = $_POST['productID'];

    foreach ($selectedItems as $pid) {

    }

    echo json_encode(["status" => "success", "message" => "Checkout complete"]);
    exit;
}

if (!isset($_POST['selected']) || empty($_POST['selected'])) {
    echo "<p>No product selected.</p>";
    echo "<a href='/cart/cart.php" . htmlspecialchars($_POST['userID']) . "'>Back to Cart</a>";
    exit;
}

$selectedIDs = $_POST['selected'];
$cartID = $_POST['cartID'];
$total = 0.00;

echo "<h2>Selected Products:</h2>";
echo "<ul>";

foreach ($selectedIDs as $productID) {
    $stmt = $_db->prepare("
        SELECT p.productName, p.productPrice, c.cartQuantity 
        FROM product p 
        JOIN cartItem c ON p.productID = c.productID 
        WHERE c.cartID = ? AND c.productID = ?
    ");
    $stmt->execute([$cartID, $productID]);
    $item = $stmt->fetch(PDO::FETCH_OBJ);

    if ($item) {
        $subtotal = $item->productPrice * $item->cartQuantity;
        $total += $subtotal;

        echo "<li>{$item->productName} - RM" . number_format($item->productPrice, 2) . 
             " × {$item->cartQuantity} = RM" . number_format($subtotal, 2) . "</li>";
    }
}

echo "</ul>";
echo "<h3>Total Price: RM" . number_format($total, 2) . "</h3>";
