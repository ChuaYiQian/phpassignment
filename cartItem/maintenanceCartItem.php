<?php
include '../base.php';

$userID = "C001";
$stmt = $_db->prepare("SELECT cartID FROM cart WHERE userID = ?");
$stmt->execute([$userID]);
$cart = $stmt->fetch(PDO::FETCH_OBJ);

if (!$cart) {
    echo "Cart not found.";
    exit;
}

$cartID = $cart->cartID;

$stmt = $_db->prepare("
    SELECT c.productID, c.cartQuantity, p.productName, p.productDescription, 
           p.productPrice, p.productQuantity, p.productPicture 
    FROM cartItem c
    JOIN product p ON c.productID = p.productID
    WHERE c.cartID = ?
");
$stmt->execute([$cartID]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrice = 0;
$selectedIDs = $_POST['selected'] ?? [];

foreach ($cartItems as $item) {
    if (in_array($item['productID'], $selectedIDs)) {
        $totalPrice += $item['productPrice'] * $item['cartQuantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/css/addToCart.css">
</head>

<body>

    <div class="main-container">
        <div class="topbar">
            <div class="topbar-Goback">
                <a href="homepage.php">
                    <img src="/images/goBackIcon.png" width="40px" height="40px">
                </a>
            </div>
            <div class="topbar-text">
                <h1>Go Back</h1>
            </div>
            <div class="topbar-brand">
                <h1>PopZone Collectibles</h1>
            </div>
        </div>

        <form method="post" class="container">
            <?php if (empty($cartItems)): ?>
                <p style="margin: 20px; font-size: 1.2em;">Your cart is currently empty.</p>
            <?php else: ?>
                <?php foreach ($cartItems as $item): ?>
                    <div class="container-product-card">
                        <div class="product-checkbox">
                            <input type="checkbox" class="product-tick"
                                   data-price="<?= $item['productPrice'] * $item['cartQuantity'] ?>"
                                   name="selected[]"
                                   value="<?= $item['productID'] ?>"
                                   <?= in_array($item['productID'], $selectedIDs) ? 'checked' : '' ?>>
                        </div>

                        <div class="product-photo">
                            <img src="/images/<?= $item['productPicture'] ?>" width="200px" height="200px">
                        </div>

                        <div class="product-detail">
                            <div class="product-name">
                                <h2><?= htmlspecialchars($item['productName']) ?></h2>
                            </div>
                            <div class="product-desc">
                                <p><?= htmlspecialchars($item['productDescription']) ?></p>
                            </div>

                            <div class="product-quantity">
                                <form method="post" action="/cartItem/updateQuantity.php">
                                    Quantity:
                                    <input type="number" name="newQuantity" value="<?= $item['cartQuantity'] ?>" min="1">
                                    <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                    <input type="hidden" name="cartID" value="<?= $cartID ?>">
                                    <button type="submit">Update</button>
                                </form>
                            </div>

                            <div class="product-price">
                                <h2>RM<?= number_format($item['productPrice'], 2) ?></h2>
                            </div>
                        </div>

                        <div class="product-delete">
                            <form method="post" action="/cartItem/deleteCartItem.php" onsubmit="return confirm('Delete this item?');" style="display:inline;">
                                <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                                <input type="hidden" name="userID" value="<?= $userID ?>">
                                <button type="submit" style="border:none; background:none;">
                                    <img src="/images/deleteIcon.png" width="30px" height="30px">
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div style="margin-top: 20px;">
                    <button type="submit">Update Total</button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="sidebar">
        <div class="sidebar-price">
            <h2>Total Price: <span id="totalPrice">RM<?= number_format($totalPrice, 2) ?></span></h2>
        </div>
        <br>
        <div class="sidebar-btnsec">
            <form method="post" action="/cart/checkout.php">
                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                <input type="hidden" name="userID" value="<?= $userID ?>">
                <button type="submit">Check Out</button>
            </form>
        </div>
    </div>

</body>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkboxes = document.querySelectorAll(".product-tick");
        const totalPriceElem = document.querySelector("#totalPrice");

        function updateTotal() {
            let total = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.dataset.price);
                }
            });
            totalPriceElem.textContent = "RM" + total.toFixed(2);
        }

        checkboxes.forEach(cb => {
            cb.addEventListener("change", updateTotal);
        });

        updateTotal();
    });
</script>

</html>
