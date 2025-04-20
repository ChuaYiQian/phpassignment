<?php
include '../base.php';

if (!isset($_GET['cartID'], $_GET['staffID'])) {
    echo "ID";
    exit;
}

$cartID = $_GET['cartID'];
$staffID = $_GET['staffID'];

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Items - PopZone Collectibles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .action-btns form { display: inline-block; margin-right: 5px; }
        .btn { padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #4CAF50; color: white; border: none; }
        .btn-danger { background-color: #f44336; color: white; border: none; }
        .btn-edit { background-color: #2196F3; color: white; border: none; }
        .grand-total { font-weight: bold; background-color: #f8f8f8; }
        input[type="number"] { padding: 6px; width: 60px; }
        h2 { color: #333; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="header">
        <h1>Cart Items Management</h1>
    </div>

    <h2>Cart ID: <?= $cartID ?> (User ID: <?= $userID ?>)</h2>

    <table>
        <thead>
            <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
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
                        <form method="post" action="/cartItem/updateQuantity.php" class="action-btns">
                            <input type="hidden" name="productID" value="<?= $item->productID ?>">
                            <input type="hidden" name="cartID" value="<?= $cartID ?>">
                            <input type="hidden" name="staffID" value="<?= $staffID ?>">
                            <input type="number" name="cartQuantity" value="<?= $item->cartQuantity ?>" min="1" max="99" required>
                            <button type="submit" class="btn btn-edit">Update</button>
                        </form>
                    </td>

                    <td>RM <?= number_format($total, 2) ?></td>

                    <td class="action-btns">
                        <form method="post" action="/cartItem/deleteCartItem.php" onsubmit="return confirm('Are you sure you want to delete this item?');">
                            <input type="hidden" name="productID" value="<?= $item->productID ?>">
                            <input type="hidden" name="cartID" value="<?= $cartID ?>">
                            <input type="hidden" name="staffID" value="<?= $staffID ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>

            <tr class="grand-total">
                <td colspan="3"><strong>Grand Total</strong></td>
                <td></td>
                <td><strong>RM <?= number_format($grandTotal, 2) ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>