<?php
session_start();
include '../base.php';
$userID = $_SESSION['user_id'];
$stmt = $_db->prepare("SELECT cartID FROM cart WHERE userID = ?");
$stmt->execute([$userID]);
$cart = $stmt->fetch(PDO::FETCH_OBJ);

if (!$cart) {
    echo "Cart not found.";
    exit;
}

$cartID = $cart->cartID;

$stmt = $_db->prepare("
    SELECT c.productID, c.cartQuantity, p.productName, p.productDescription, p.productPrice, p.productQuantity, p.productPicture 
    FROM cartItem c
    JOIN product p ON c.productID = p.productID
    WHERE c.cartID = ?
");
$stmt->execute([$cartID]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrice = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/css/addToCart.css">
</head>

<body>
    <div class="main-container">
        <div class="topbar">
            <div class="topbar-Goback">
                <a href="/home.php">
                    <img src="/images/goBackIcon.png" alt="" width="40px" height="40px">
                </a>
            </div>
            <div class="topbar-text">
                <h1>Go Back</h1>
            </div>
            <div class="topbar-brand">
                <h1>PopZone Collectibles</h1>
            </div>
        </div>

        <div class="container">
            <?php if (empty($cartItems)): ?>
                <p style="margin: 20px; font-size: 1.2em;">Your cart is currently empty.</p>
            <?php else: ?>

                <div style="margin: 10px 0;">
                    <input type="checkbox" id="select-all"> <label for="select-all">Select All</label>
                </div>

                <?php foreach ($cartItems as $item): ?>
                    <?php $totalPrice += $item['productPrice'] * $item['cartQuantity']; ?>
                    <div class="container-product-card">
                        <div class="product-checkbox">
                            <input type="checkbox" class="cart-check" data-productid="<?= $item['productID'] ?>">
                        </div>
                        <div class="product-photo">
                            <img src="/images/<?= $item['productPicture'] ?>" alt="" width="200px" height="200px">
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
                                    <input type="hidden" name="userID" value="<?= $userID ?>">
                                    <button type="submit">Update</button>
                                </form>
                            </div>
                            <div class="product-price">
                                <h2>RM<?= number_format($item['productPrice'], 2) ?></h2>
                            </div>
                        </div>
                        <div class="product-delete">
                            <form method="post" action="/cartItem/deleteCartItem.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                                <input type="hidden" name="userID" value="<?= $userID ?>">
                                <button type="submit" style="border:none; background:none;">
                                    <img src="/images/deleteIcon.png" alt="" width="30px" height="30px">
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-price">
            <h2>Total Price: <span id="total-price">RM<?= number_format($totalPrice, 2) ?></span></h2>
        </div>
        <br>
        <div class="sidebar-btnsec">
            <form method="post" action="checkout.php">
                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                <button type="submit">Check Out</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.cart-check');
        const totalPriceElem = document.getElementById('total-price');
        const selectAll = document.getElementById('select-all');

        function updateTotalPrice() {
            const selected = [];
            const cartID = "<?= $cartID ?>";

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    selected.push(cb.getAttribute('data-productid'));
                }
            });

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/cart/getTotalPrice.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function () {
                if (xhr.status === 200) {
                    totalPriceElem.textContent = "RM" + parseFloat(xhr.responseText).toFixed(2);
                }
            };

            xhr.send("selected=" + JSON.stringify(selected) + "&cartID=" + encodeURIComponent(cartID));
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateTotalPrice);
        });

        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateTotalPrice();
        });

        updateTotalPrice();
    });
    </script>
</body>
</html>
