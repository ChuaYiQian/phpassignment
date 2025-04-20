<?php
include '../base.php';

$staffID = "S001";
$keyword = $_GET['search'] ?? '';

$stmt = $_db->prepare("
    SELECT 
        cart.cartID,
        cart.userID,
        user.userName,
        COUNT(cartItem.cartQuantity) AS numProducts
    FROM cart
    JOIN user ON cart.userID = user.userID
    LEFT JOIN cartItem ON cart.cartID = cartItem.cartID
    WHERE user.userID LIKE ? OR user.userName LIKE ?
    GROUP BY cart.cartID, cart.userID, user.userName
");
$searchTerm = "%$keyword%";
$stmt->execute([$searchTerm, $searchTerm]);
$carts = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<form method="get" style="margin: 20px;">
    <input type="text" name="search" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search by User ID or Name">
    <button type="submit">Search</button>
</form>

<p><?= count($carts) ?> result(s)</p>

<table border="1" cellpadding="10">
    <tr>
        <th>Cart ID</th>
        <th>User ID</th>
        <th>User Name</th>
        <th>Number of Products</th>
        <th>Action</th>
    </tr>

    <?php foreach ($carts as $cart): ?>
        <tr>
            <td><?= $cart->cartID ?></td>
            <td><?= $cart->userID ?></td>
            <td><?= htmlspecialchars($cart->userName) ?></td>
            <td><?= $cart->numProducts ?></td>
            <td>
                <form action="/cartItem/maintenanceCartItem.php" method="get">
                    <input type="hidden" name="cartID" value="<?= $cart->cartID ?>">
                    <input type="hidden" name="staffID" value="<?= $staffID?>">
                    <button type="submit">View Cart Item</button>
                </form>
            </td>
        </tr>
    <?php endforeach ?>
</table>
