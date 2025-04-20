<?php
include '../base.php';

if (!isset($_GET['cartID'],$_GET['staffID'])) {
    echo "ID";
    exit;
}

$cartID = $_GET['cartID'];


$cartStmt = $_db->prepare("SELECT userID FROM cart WHERE cartID = ?");
$cartStmt->execute([$cartID]);
$user = $cartStmt->fetch(PDO::FETCH_OBJ);
$userID = $user->userID;


$stmt = $_db->prepare("
    SELECT p.productID, p.productName, p.productPrice, c.cartQuantity
    FROM cartItem c
    JOIN product p ON c.productID = p.productID
    WHERE c.cartID = ?
");
$stmt->execute([$cartID]);
$items = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<h2>Cart Item List (Cart ID: <?= $cartID ?>)</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Price</th>
        <th>Quantity in Cart</th>
        <th>Total</th>
        <th>Actions</th>
    </tr>

    <?php
    $grandTotal = 0;
    foreach ($items as $item):
        $total = $item->productPrice * $item->cartQuantity;
        $grandTotal += $total;
    ?>
        <tr>
            <td><?= $item->productID ?></td>
            <td><?= htmlspecialchars($item->productName) ?></td>
            <td>RM <?= number_format($item->productPrice, 2) ?></td>

            <td>
                <form method="post" action="/cartItem/updateQuantity.php" style="display: inline-block;">
                    <input type="hidden" name="productID" value="<?= $item->productID ?>">
                    <input type="hidden" name="cartID" value="<?= $cartID ?>">
                    <input type="hidden" name="staffID" value="<?= $staffID ?>">
                    <input type="number" name="cartQuantity" value="<?= $item->cartQuantity ?>" min="1" max="99" required>
                    <button type="submit">Edit</button>
                </form>
            </td>

            <td>RM <?= number_format($total, 2) ?></td>

            <td>
                <form method="post" action="/cartItem/deleteCartItem.php" onsubmit="return confirm('Are you sure you want to delete this item?');">
                    <input type="hidden" name="productID" value="<?= $item->productID ?>">
                    <input type="hidden" name="cartID" value="<?= $cartID ?>">
                    <input type="hidden" name="staffID" value="<?= $staffID ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>

    <tr>
        <td colspan="4"><strong>Grand Total</strong></td>
        <td colspan="2"><strong>RM <?= number_format($grandTotal, 2) ?></strong></td>
    </tr>
</table>
