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
    $products = $conn->query($sql)->fetch_all();
}

$payment_methods = $conn->query("SELECT * FROM paymentmethod")->fetch_all();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Page</title>
    <link rel="stylesheet" href="/css/payment.css">
</head>
<body>

<h2>Checkout</h2>

<div class="container">
    <div class="cart-section">
        <table>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th class="price">Price</th>
                <th>Qty</th>
                <th class="price">Subtotal</th>
            </tr>
            <?php foreach ($products as $p): 
                $qty = $cart[$p['productID']];
                $subtotal = $p['productPrice'] * $qty;
                $total += $subtotal;
            ?>
            <tr>
                <td><img src="/images/<?= $p['productPicture'] ?>"></td>
                <td>
                    <strong><?= $p['productName'] ?></strong><br>
                    <small><?= $p['productDescription'] ?></small>
                </td>
                <td class="price">RM<?= number_format($p['productPrice'], 2) ?></td>
                <td><?= $qty ?></td>
                <td class="price">RM<?= number_format($subtotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
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

    <!-- PAYMENT SIDEBAR -->
    <div class="sidebar">
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

        <!-- Payment Form -->
        <form method="POST" action="submit_payment.php">
            <input type="hidden" name="amount" value="<?= $finalTotal ?>">
            <label><strong>Payment Method</strong></label><br><br>
            <!-- Credit / Debit Card -->
            <div class="method-option">
                <input type="radio" name="payment_method" value="card" id="card" onclick="toggleBankList(false)" required>
                <label for="card">
                    <img src="/images/cards.png" alt="card"> 
                    Credit / Debit Card
                </label>
            </div>

            <!-- Touch 'n Go -->
            <div class="method-option">
                <input type="radio" name="payment_method" value="tng" id="tng" onclick="toggleBankList(false)" required>
                <label for="tng">
                    <img src="/images/tng.png" alt="tng"> 
                    Touch 'n Go eWallet
                </label>
            </div>

            <!-- Online Banking (FPX) -->
            <div class="method-option">
                <input type="radio" name="payment_method" value="fpx" id="fpx" onclick="toggleBankList(false)" required>
                <label for="fpx">
                    <img src="/images/fpx.png" alt="fpx"> 
                    FPX (Online Banking)
                </label>
            </div>


            <div id="bank-list" style="display:none; margin-left: 30px; margin-top:10px;">
                <label for="bank">Choose your bank:</label>
                    <div class="bank-option">
                        <input type="radio" name="bank" value="HongLeong" id="hlb">
                        <label for="hlb">
                            <img src="/images/hongleongbank.png" alt="HongLeongBank">
                            HongLeong Bank
                        </label>
                    </div>
                    <div class="bank-option">
                        <input type="radio" name="bank" value="PublicBank" id="pb">
                        <label for="pb">
                            <img src="/images/publicbank.png" alt="PublicBank">
                            Public Bank
                        </label>
                    </div>
                    <div class="bank-option">
                        <input type="radio" name="bank" value="MayBank" id="myb">
                        <label for="MyB">
                            <img src="/images/maybank.png" alt="Maybank">
                            Maybank
                        </label>
                    </div>
                </div>
            </div>

            <br><br>
            <button type="submit">Place Order(s)</button>
        </form>

        <script>
        function toggleBankList(show) {
            document.getElementById('bank-list').style.display = show ? 'block' : 'none';
        }
        </script>

            <!-- Card details placeholder -->
            <div id="card-details" style="display:none;">
                <label>Card Number:</label>
                <input type="text" name="card_number"><br>
                <label>Expiry:</label>
                <input type="text" name="card_expiry"><br>
                <label>CVV:</label>
                <input type="text" name="card_cvv"><br>
            </div>

        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="payment_method"]').forEach(el => {
    el.addEventListener('change', () => {
        const label = el.nextElementSibling?.textContent?.toLowerCase();
        document.getElementById('card-details').style.display = label.includes("card") ? 'block' : 'none';
    });
});
</script>

</body>
</html>
