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

if (isset($_POST['apply_voucher'])) {
    $code = $_POST['voucher_code'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM voucher WHERE voucherCode = ? AND voucherStatus = 'Active' AND startDate <= CURDATE() AND endDate >= CURDATE()");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();

    if ($voucher) {
        $voucherID = $voucher['voucherID'];
        $discount = floatval($voucher['discountRate']);
        $voucherMsg = "Voucher applied: {$discount}% off";
        $_SESSION['voucherID'] = $voucherID;
        $_SESSION['discount'] = $discount;
    } else {
        $voucherMsg = "Invalid or expired voucher code.";
    }
}

$productIDs = implode(',', array_keys($cart));
$products = [];
if (!empty($cart)) {
    $sql = "SELECT * FROM product WHERE productID IN ($productIDs)";
    $products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/* Navigate to external web */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $selected_bank = $_POST['bank'] ?? '';

    if ($payment_method === 'fpx' && $selected_bank === 'HongLeong') {

        // Redirect user to HLB payment page
        header("Location: https://www.hlbepay.com.my/HLB-ePayment/logonDisplay");
        exit;
    }

}


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
                    <th class="price">Price(RM)</th>
                    <th>Qty</th>
                    <th class="price">Subtotal(RM)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): 
                    $qty = $cart[$p['productID']];
                    $subtotal = $p['productPrice'] * $qty;
                    $total += $subtotal;
                ?>
                <tr>
                    <td><img src="/images/<?= $p['productPicture'] ?>"></td>
                    <td><strong><?= $p['productName'] ?></strong><br><small><?= $p['productDescription'] ?></small></td>
                    <td class="price">RM<?= number_format($p['productPrice'], 2) ?></td>
                    <td><?= $qty ?></td>
                    <td class="price">RM<?= number_format($subtotal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="voucher-box">
            <form method="POST">
                <label>Voucher Code:</label>
                <input type="text" name="voucher_code">
                <button type="submit" name="apply_voucher">Apply</button>
            </form>
            <p style="color: green;"><?= $voucherMsg ?></p>
        </div>
    </div>

    <!-- PAYMENT -->
        <?php
            $discountAmount = $total * ($discount / 100);
            $taxAmount = $total * $taxRate;
            $finalTotal = $total - $discountAmount + $taxAmount + $shippingFee;
        ?>
        <div class="summary">
            <p>Subtotal: RM<?= number_format($total, 2) ?></p>
            <p>Discount: -RM<?= number_format($discountAmount, 2) ?></p>
            <p>Tax (6%): RM<?= number_format($taxAmount, 2) ?></p>
            <p>Shipping: RM<?= number_format($shippingFee, 2) ?></p>
            <h3>Total: RM<?= number_format($finalTotal, 2) ?></h3>
        </div>

        <form method="POST" action="submit_payment.php">
            <input type="hidden" name="amount" value="<?= $finalTotal ?>">

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
</div>

<script>
function toggleFields(method) {
    const cardFields = document.getElementById('card-details');
    const bankList = document.getElementById('bank-list');

    cardFields.style.display = method === 'card' ? 'block' : 'none';
    bankList.style.display = method === 'fpx' ? 'block' : 'none';
}

document.addEventListener("DOMContentLoaded", function () {
    const methodRadios = document.querySelectorAll('input[name="payment-method"]');
    const cardFields = document.querySelector(".card-fields");
    const bankList = document.querySelector(".bank-list");

    methodRadios.forEach(radio => {
        radio.addEventListener("change", function () {
            // Hide all by default
            cardFields.style.display = "none";
            bankList.style.display = "none";

            // Show based on selected method
            if (this.value === "card") {
                cardFields.style.display = "block";
            } else if (this.value === "bank") {
                bankList.style.display = "block";
            }
        });
    });
});
</script>

</body>
</html>
