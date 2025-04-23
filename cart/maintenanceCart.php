<?php
include '../base.php';
session_start();

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Maintenance - PopZone Collectibles</title>
    <style>
        body { font-family: Arial, sans-serif;}
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .action-btns a { margin-right: 10px; text-decoration: none; }
        .btn { padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #4CAF50; color: white; border: none; }
        .btn-danger { background-color: #f44336; color: white; border: none; }
        .btn-edit { background-color: #2196F3; color: white; border: none; }
        .search-form { margin: 20px 0; }
        .search-form input[type="text"] { padding: 8px; width: 300px; }
        .search-form button { padding: 8px 16px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .results-count { margin: 10px 0; color: #666; }
    </style>
</head>
<body>
    <?php include '../adminheader.php'; ?>
    
    <div class="header">
        <h1>Cart Maintenance</h1>
    </div>

    <form method="get" class="search-form">
        <input type="text" name="search" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search by User ID or Name">
        <button type="submit">Search</button>
    </form>

    <p class="results-count"><?= count($carts) ?> result(s)</p>

    <table>
        <thead>
            <tr>
                <th>Cart ID</th>
                <th>User ID</th>
                <th>User Name</th>
                <th>Number of Products</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($carts as $cart): ?>
                <tr>
                    <td><?= $cart->cartID ?></td>
                    <td><?= $cart->userID ?></td>
                    <td><?= htmlspecialchars($cart->userName) ?></td>
                    <td><?= $cart->numProducts ?></td>
                    <td class="action-btns">
                        <form action="/cartItem/maintenanceCartItem.php" method="get" style="display: inline;">
                            <input type="hidden" name="cartID" value="<?= $cart->cartID ?>">
                            <input type="hidden" name="staffID" value="<?= $staffID?>">
                            <button type="submit" class="btn btn-primary">View Cart Items</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</body>
</html>