<?php
include 'base.php';
session_start();


$cart = $_SESSION['cart'] ?? [];
$total = 0;
$discount = 0;
$voucherMsg = '';
$voucherID = null;
$taxRate = 0.06;
$shippingFee = 5.00;

$orderID = $_GET['orderID'] ?? ''; 

$sql = "
    SELECT 
        p.productID,
        p.productName,
        p.productDescription,
        p.productPrice,
        p.productPicture,
        oi.orderQuantity
    FROM orderinformation oi
    JOIN product p ON oi.productID = p.productID
    WHERE oi.orderID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $orderID);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $products = $result->fetch_all(MYSQLI_ASSOC);
}


if (isset($_POST['apply_voucher'])) {
    $code = $_POST['voucher_code'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM voucher WHERE voucherCode = ? AND voucherStatus = 'Active' AND startDate <= CURDATE() AND endDate >= CURDATE()");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();

    if ($voucher) {
        $_SESSION['voucherID'] = $voucher['voucherID'];
        $_SESSION['voucherCode'] = $voucher['voucherCode'];
        $_SESSION['discount'] = floatval($voucher['discountRate']);
        header("Location: payment.php?orderID=" . urlencode($orderID)); 
        exit;
    } else {
        $_SESSION['voucherError'] = "Invalid or expired voucher code.";
        unset($_SESSION['voucherID'], $_SESSION['voucherCode'], $_SESSION['discount']);
        header("Location: payment.php?orderID=" . urlencode($orderID));
        exit;
    }
}

if (isset($_POST['remove_voucher'])) {
    unset($_SESSION['voucherID'], $_SESSION['voucherCode'], $_SESSION['discount']);
    header("Location: payment.php?orderID=" . urlencode($orderID));
    exit;
}

$discount = $_SESSION['discount'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="/css/payment.css">
</head>
<body>
    <h2>Checkout</h2>
    <div class="container">
        <div class="cart-section">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th class="price">Price</th>
                        <th>Qty</th>
                        <th class="price">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $qty = $p['orderQuantity'];
                        $subtotal = $p['productPrice'] * $qty;
                        $total += $subtotal;
                    ?>
                    <tr>
                        <td><img src="/images/<?= $p['productPicture'] ?>" width="100"></td>
                        <td><?= $p['productDescription'] ?></td>
                        <td>RM<?= number_format($p['productPrice'], 2) ?></td>
                        <td><?= $qty ?></td>
                        <td>RM<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- VOUCHER -->
            <div class="voucher-box">
                <?php if (isset($_SESSION['voucherID'], $_SESSION['discount'], $_SESSION['voucherCode'])): ?>
                    <p style="color: green;">
                        Applied Voucher: <?= $_SESSION['voucherID'] ?> (<?= $_SESSION['voucherCode'] ?>) – <?= $_SESSION['discount'] ?>% off
                    </p>
                    <form method="POST">
                        <button type="submit" name="remove_voucher">Remove Voucher</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <label>Voucher Code:</label>
                        <input type="text" name="voucher_code" required>
                        <button type="submit" name="apply_voucher">Apply</button>
                    </form>
                    <?php if (isset($_SESSION['voucherError'])): ?>
                        <p style="color: red;"><?= $_SESSION['voucherError'] ?></p>
                        <?php unset($_SESSION['voucherError']); ?> 
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <!-- PAYMENT -->
            <?php
                $discount = $_SESSION['discount'] ?? 0;
                $discountAmount = $total * ($discount / 100);
                $taxAmount = $total * $taxRate;
                $finalTotal = $total - $discountAmount + $taxAmount + $shippingFee;
            ?>
            <div class="summary">
                <p>Subtotal: <span class="price">RM<?= number_format($total, 2) ?></span></p>
                <p>Tax (6%): <span class="price">RM<?= number_format($taxAmount, 2) ?></span></p>
                <p>Shipping: <span class="price">RM<?= number_format($shippingFee, 2) ?></span></p>
                <p>Discount: <span class="price discount-price">-RM<?= number_format($discountAmount, 2) ?></span></p>
                <h3>Total: <span class="price">RM<?= number_format($finalTotal, 2) ?></span></h3>
            </div>

            <form id="payment-form" method="POST" action="../order/completeOrder.php">
                <input type="hidden" name="amount" value="<?= $finalTotal ?>">
                <input type="hidden" name="orderID" value="<?= htmlspecialchars($_GET['orderID']) ?>">

                <div class="payment-methods">
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="card" onclick="toggleFields('card')" required>
                        <img src="/images/cards.png" alt="card">
                        Credit / Debit Card
                    </label>

                    <label class="method-option">
                        <input type="radio" name="payment_method" value="tng" onclick="toggleFields('tng')" required>
                        <img src="/images/tng.png" alt="tng">
                        Touch 'n Go
                    </label>

                    <label class="method-option">
                        <input type="radio" name="payment_method" value="fpx" onclick="toggleFields('fpx')" required>
                        <img src="/images/fpx.png" alt="fpx">
                        Online Banking
                    </label>
                </div>

                <!-- Card Details -->
                <div id="card-details" class="card-fields">
                    <input type="text" name="card_number" placeholder="Card Number">
                    <input type="text" name="card_expiry" placeholder="Expiry (MM/YY)">
                    <input type="text" name="card_cvv" placeholder="CVV">
                </div>

                <!-- Bank Options -->
                <div id="bank-list" class="bank-list">
                    <label>Select Bank:</label>
                    <label class="bank-option">
                        <input type="radio" name="bank" value="HongLeong" />
                        <img src="/images/hongleongbank.png" alt="HongLeong Bank" />
                        <span>HongLeong Bank</span>
                    </label>

                    <label class="bank-option">
                        <input type="radio" name="bank" value="publicbank" />
                        <img src="/images/publicbank.png" alt="Public Bank" />
                        <span>Public Bank</span>
                    </label>

                    <label class="bank-option">
                        <input type="radio" name="bank" value="maybank" />
                        <img src="/images/maybank.png" alt="Maybank" />
                        <span>Maybank</span>
                    </label>
                </div>

                <div class="place-order">
                    <button type="submit">Place Order</button>
                </div>
            </form>
            <form method="post" action="completeOrder.php" style="margin-top: 20px;">
                <input type="hidden" name="orderID" value="<?= htmlspecialchars($_GET['orderID']) ?>">
                <input type="hidden" name="paymentMethod" value="Simulated Failure">
                <button type="submit" name="simulate" value="fail" style="color: red;">Simulate Payment Failure</button>
            </form>
    </div>

    <script>
    function toggleFields(method) {
        const cardFields = document.getElementById('card-details');
        const bankList = document.getElementById('bank-list');

        cardFields.style.display = method === 'card' ? 'block' : 'none';
        bankList.style.display = method === 'fpx' ? 'block' : 'none';
    }

    document.addEventListener("DOMContentLoaded", function () {
        const methodRadios = document.querySelectorAll('input[name="payment_method"]');
        const cardFields = document.getElementById('card-details');
        const bankList = document.getElementById('bank-list');

        // Hide all by default
        cardFields.style.display = "none";
        bankList.style.display = "none";

        methodRadios.forEach(radio => {
            radio.addEventListener("change", function () {
                toggleFields(this.value);
            });
        });

        // Form validation
        const form = document.getElementById("payment-form");
        form.addEventListener("submit", function (e) {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedPayment) {
                alert("Please select a payment method.");
                e.preventDefault();
                return;
            }

            const method = selectedPayment.value;

            if (method === "card") {
                const cardNumber = document.querySelector('input[name="card_number"]').value.trim();
                const expiry = document.querySelector('input[name="card_expiry"]').value.trim();
                const cvv = document.querySelector('input[name="card_cvv"]').value.trim();

                if (!cardNumber || !expiry || !cvv) {
                    alert("Please fill in all card details.");
                    e.preventDefault();
                    return;
                }
            }

            if (method === "fpx") {
                const selectedBank = document.querySelector('input[name="bank"]:checked');
                if (!selectedBank) {
                    alert("Please select a bank for FPX payment.");
                    e.preventDefault();
                    return;
                }
            }
        });
    });
    </script>

</body>
</html>
