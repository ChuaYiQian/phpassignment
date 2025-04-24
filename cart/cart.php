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
    SELECT c.productID, c.cartQuantity, p.productName, p.productDescription, 
           p.productPrice, p.productQuantity, p.productPicture 
    FROM cartItem c
    JOIN product p ON c.productID = p.productID
    WHERE c.cartID = ?
");
$stmt->execute([$cartID]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

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
            <div class="topbar-text">
                <h1>Go Back</h1>
            </div>
            <div class="topbar-brand">
                <h1>PopZone Collectibles</h1>
            </div>
        </div>
        <div class="container">
            <?php if (empty($cartItems)): ?>
                <img id="emptyCart" src="/images/emptyCart.png" height="100px" width="100px" style="margin-top:auto; margin-left:auto;margin-right:auto;">
                <p style="color:grey;margin: 20px; font-size: 1.6em; text-align:center; margin-bottom:auto;
                font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif; font-weight:bold;">Your cart is currently empty</p>
            <?php else: ?>
                <div style="margin: 15px;">
                    <label><input type="checkbox" id="select-all"> Select All</label>
                </div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="container-product-card">

                        <div class="product-checkbox">
                            <input type="checkbox" class="cart-checkbox"
                                data-id="<?= $item['productID'] ?>"
                                data-price="<?= $item['productPrice'] ?>"
                                data-qty="<?= $item['cartQuantity'] ?>"
                                data-name="<?= htmlspecialchars($item['productName']) ?>">
                        </div>

                        <div class="product-photo">
                            <?php $firstImage = explode(',', $item['productPicture'])[0]; ?>
                            <img src="/images/<?= htmlspecialchars(trim($firstImage)) ?>"
                                alt="<?= htmlspecialchars($item['productName']) ?>"
                                width="200" height="200">
                        </div>

                        <div class="product-detail">
                            <div class="product-name">
                                <h2><?= htmlspecialchars($item['productName']) ?></h2>
                            </div>
                            <div class="product-desc">
                                <p><?= htmlspecialchars($item['productDescription']) ?></p>
                            </div>
                            <div class="product-quantity">
                                <form class="quantity-form" method="post" action="/cartItem/updateQuantity.php">
                                    <input type="number" name="newQuantity"
                                        value="<?= $item['cartQuantity'] ?>" min="1">

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
                            <form action="/cartItem/deleteCartItem.php" method="post" id="deleteForm">
                                <input type="hidden" name="productID" value="<?= $item['productID'] ?>">
                                <input type="hidden" name="cartID" value="<?= $cartID ?>">
                                <input type="hidden" name="userID" value="<?= $_SESSION['user_id'] ?>">
                                <button type="button" onclick="showDeleteConfirmation()" style="border: none; background-color:white;">
                                    <img src="/images/deleteIcon.png" alt="Delete" width="30" height="30">
                                </button>
                            </form>

                            <div id="confirmation" class="modal-container">
                                <div class="modal">
                                    <section>
                                        <span onclick="hideDeleteConfirmation()">&times;</span>
                                        <header class="modal-header">
                                            <h2>Are you sure you want to delete this?</h2>
                                        </header>
                                        <section class="modal-content">
                                            <p>This action cannot be undone</p>
                                        </section>
                                        <footer class="modal-footer">
                                            <button class="modal-button" onclick="hideDeleteConfirmation()">Cancel</button>
                                            <button class="modal-button modal-confirm-button" onclick="confirmDelete()">Confirm</button>
                                        </footer>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar">
        <div class="sidebar-content" style="text-align: center;">
            <h2 class="sidebar-title" style="margin-bottom: 10px;">Order Summary</h2>

            <div class="item-list" id="selected-items-list">
                <div class="no-items">No items selected</div>
            </div>

            <div class="sidebar-price" style="margin-bottom: 15px;">
                <div class="price-row total">
                    <span>Total:</span>
                    <span>RM<span id="total-price">0.00</span></span>
                </div>
            </div>

            <div class="sidebar-btnsec">
                <form method="post" action="checkOut.php" id="checkout-form">
                    <input type="hidden" name="cartID" value="<?= $cartID ?>">
                    <input type="hidden" name="selectedItems" id="selected-items">
                    <button type="submit" id="checkout-btn" class="checkout-btn" disabled>
                        Proceed to Checkout (0 items)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateTotalPrice() {
            let selectedItems = [];
            let totalPrice = 0;
            let itemCount = 0;

            $('#selected-items-list').empty();

            $('.cart-checkbox:checked').each(function() {
                const item = {
                    id: $(this).data('id'),
                    name: $(this).data('name'),
                    price: parseFloat($(this).data('price')),
                    qty: parseInt($(this).data('qty')),
                    total: function() {
                        return this.price * this.qty;
                    }
                };

                const itemHtml = `
                <div class="item-card">
                    <div class="item-header">
                        <span class="item-name">${item.name}</span>
                        <span class="item-price">RM${item.total().toFixed(2)}</span>
                    </div>
                    <div class="item-details">
                        <span>Qty: ${item.qty}</span>
                        <span>Unit Price: RM${item.price.toFixed(2)}</span>
                    </div>
                </div>
            `;

                $('#selected-items-list').append(itemHtml);
                selectedItems.push(item.id);
                totalPrice += item.total();
                itemCount++;
            });

            $('#total-price').text(totalPrice.toFixed(2));
            $('#checkout-btn').prop('disabled', itemCount === 0);
            $('#checkout-btn').html(`Proceed to Checkout (${itemCount} items)`);
            $('#selected-items').val(selectedItems.join(','));

            if (itemCount === 0) {
                $('#selected-items-list').html('<div class="no-items">No items selected</div>');
            }
        }

        $(document).on('change', '.cart-checkbox', updateTotalPrice);
        $(document).on('submit', '.quantity-form', function(e) {
            e.preventDefault();
            $.post($(this).attr('action'), $(this).serialize(), function() {
                location.reload();
            });
        });

        $('#select-all').change(function() {
            $('.cart-checkbox').prop('checked', $(this).prop('checked'));
            updateTotalPrice();
        });

        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('emptyCart').classList.add('animate');
        });

        let deleteForm = null;

        function showDeleteConfirmation() {
            deleteForm = event.target.closest('form');
            document.getElementById('confirmation').style.display = 'block';
        }

        function hideDeleteConfirmation() {
            document.getElementById('confirmation').style.display = 'none';
            deleteForm = null;
        }

        function confirmDelete() {
            if (deleteForm) {
                deleteForm.submit();
            }
            hideDeleteConfirmation();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('confirmation');
            if (event.target === modal) {
                hideDeleteConfirmation();
            }
        }
    </script>
</body>

</html>