<?php
include '../base.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /home.php");
    exit;
}

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
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/css/addToCart.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="main-container">
    <div class="topbar">
        <div class="topbar-Goback">
            <a href="../home.php?userID=<?= $userID ?>">
                <img src="/images/goBackIcon.png" alt="" width="40px" height="40px">
            </a>
        </div>
        <div class="topbar-text"><h1>Go Back</h1></div>
        <div class="topbar-brand"><h1>PopZone Collectibles</h1></div>
    </div>

    <div class="container">
        <?php if (empty($cartItems)): ?>
            <p style="margin: 20px; font-size: 1.2em;">Your cart is currently empty.</p>
        <?php else: ?>
            <div style="margin: 15px;">
                <label><input type="checkbox" id="select-all"> Select All</label>
            </div>
            <?php foreach ($cartItems as $item): ?>
                <div class="container-product-card">
                    <div class="product-checkbox">
                        <input type="checkbox" class="cart-checkbox" data-id="<?= $item['productID'] ?>" data-price="<?= $item['productPrice'] ?>" data-qty="<?= $item['cartQuantity'] ?>">
                    </div>
                    <div class="product-photo">
                        <img src="/images/<?= $item['productPicture'] ?>" alt="" width="200px" height="200px">
                    </div>
                    <div class="product-detail">
                        <div class="product-name"><h2><?= htmlspecialchars($item['productName']) ?></h2></div>
                        <div class="product-desc"><p><?= htmlspecialchars($item['productDescription']) ?></p></div>
                        <div class="product-quantity">
                            <form method="post" action="/cartItem/updateQuantity.php">
                                Quantity: <input type="number" name="newQuantity" value="<?= $item['cartQuantity'] ?>" min="1">
                                <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                                <input type="hidden" name="userID" value="<?= $userID ?>">
                                <button type="submit">Update</button>
                            </form>
                        </div>
                        <div class="product-price"><h2>RM<?= number_format($item['productPrice'], 2) ?></h2></div>
                    </div>
                    <div class="product-delete">
                        <form method="post" action="/cartItem/deleteCartItem.php" onsubmit="return confirm('Are you sure?');">
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
        <h2>Total Price: RM<span id="total-price">0.00</span></h2>
    </div>
    <br>
    <div class="sidebar-btnsec">
        <form method="post" action="checkout.php" id="checkout-form">
            <input type="hidden" name="cartID" value="<?= $cartID ?>">
            <input type="hidden" name="selectedItems" id="selected-items">
            <button type="submit" id="checkout-btn" disabled>Check Out</button>
        </form>
    </div>
</div>

<script>
    function updateTotalPrice() {
        let selected = [];
        $('.cart-checkbox:checked').each(function () {
            selected.push($(this).data('id'));
        });

        if (selected.length === 0) {
            $('#total-price').text("0.00");
            $('#checkout-btn').prop('disabled', true);
            $('#selected-items').val('');
            return;
        }

        $.post("getTotalPrice.php", { items: selected }, function (data) {
            $('#total-price').text(parseFloat(data).toFixed(2));
            $('#checkout-btn').prop('disabled', false);
            $('#selected-items').val(selected.join(','));
        });
    }

    $('.cart-checkbox').on('change', updateTotalPrice);
    $('#select-all').on('change', function () {
        $('.cart-checkbox').prop('checked', $(this).is(':checked'));
        updateTotalPrice();
    });
</script>
</body>
</html>
