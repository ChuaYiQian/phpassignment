<?php
include 'base.php';
require_once 'lib/phpqrcode/qrlib.php';
session_start();

$cart = $_SESSION['cart'] ?? [];
$total = 0;
$discount = 0;
$voucherMsg = '';
$voucherID = null;
$shippingFee = 5.00;

// Get the tax rate from the database
$selectedPaymentID = $_POST['payment_method'] ?? null;
$taxRate = 0.00;

if ($selectedPaymentID) {
    $stmt = $conn->prepare("SELECT * FROM paymentmethod WHERE paymentID = ?");
    $stmt->bind_param("s", $selectedPaymentID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $taxRate = floatval($row['taxRate']) / 100;  
    }
}

$orderID = $_POST['orderID'] ?? ''; 

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

$method_sql = "SELECT * FROM paymentmethod";
$method_result = $conn->query($method_sql);
$payment_methods = [];

if ($method_result && $method_result->num_rows > 0) {
    while ($row = $method_result->fetch_assoc()) {
        $payment_methods[] = $row;
    }
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
                        <td><img src="/images/<?= $firstImage = explode(',', $p['productPicture'])[0]; ?>" width="100"></td>
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
                $finalTotal = round($finalTotal, 2);
            ?>
            <div class="summary">
                <p>Subtotal: <span class="price">RM<?= number_format($total, 2) ?></span></p>
                <p id="tax-rate">Tax (<?= $taxRate * 100 ?>%): <span class="price">RM<?= number_format($taxAmount, 2) ?></span></p>
                <p>Shipping: <span class="price">RM<?= number_format($shippingFee, 2) ?></span></p>
                <p>Discount: <span class="price discount-price">-RM<?= number_format($discountAmount, 2) ?></span></p>
                <h3 id="total-price">Total: <span class="price">RM<?= number_format($finalTotal, 2) ?></span></h3>
            </div>

            <form id="payment-form" method="POST" action="../order/payedOrder.php">
                <input type="hidden" name="amount" value="<?= number_format($finalTotal, 2, '.', '') ?>">
                <input type="hidden" name="orderID" value="<?= htmlspecialchars($_POST['orderID']) ?>">
                <input type="hidden" name="voucherID" value="<?= $_SESSION['voucherID'] ?? null ?>">

                <!-- Payment Method -->
                <div class="payment-methods">
                    <?php foreach ($payment_methods as $index => $method): ?>
                        <?php if (in_array($method['category'], ['Credit/Debit Card', 'E-wallet', 'Online Banking'])): ?>
                            <?php $radioID = "payment_" . $index; ?>
                            <input 
                                type="radio" 
                                id="<?= $radioID ?>"
                                name="payment_method"
                                value="<?= htmlspecialchars($method['paymentID']) ?>"
                                data-category="<?= htmlspecialchars($method['category']) ?>"
                                required>
                            <label class="method-option" for="<?= $radioID ?>">
                                <img src="/<?= $method['paymentIcon'] ?>" alt="<?= $method['paymentDescription'] ?>">
                                <?= $method['paymentDescription'] ?>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Dynamic Fields for Selected Payment Method -->
                <div id="card-fields" class="card-fields" style="display: none;">
                    <label>Card Number:
                        <input type="text" id="cardNumber" name="cardNumber" maxlength="16" value="<?= $_SESSION['card_number'] ?? '' ?>">
                    </label><br>
                    <label>Expiry Date (MM/YY):
                        <input type="text" id="expiry" name="expiry" value="<?= $_SESSION['expiry'] ?? '' ?>">
                    </label><br>
                    <label>CVV:
                        <input type="text" id="cvv" name="cvv" maxlength="3" value="<?= $_SESSION['cvv'] ?? '' ?>">
                    </label>
                </div>

                <!-- TNG (QR Code) -->
                <div id="e-wallet" class="paidtng" style="display: none;">
                    <label>Please Scan the QR to Pay</label><br>
                    <img id="qrCode" src="" alt="QR Code" style="display: none;">
                    <p id="qrAmount"></p>
                </div>

                <!-- Bank Options (for Online Banking) -->
                <div id="bank-list" class="bank-list" style="display: none;">
                    <label>Select Bank:</label>
                    <?php foreach ($payment_methods as $method): ?>
                        <?php if ($method['category'] == 'Bank'): ?>
                            <label class="bank-option">
                                <input type="radio" name="bank" value="<?= htmlspecialchars($method['paymentID']) ?>">
                                <img src="/<?= $method['paymentIcon'] ?>" alt="<?= $method['paymentDescription'] ?>">
                                <span><?= $method['paymentDescription'] ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="place-order">
                    <button type="submit">Place Order</button>
                </div>

                <!-- simulate payment failure -->
                <div class="simulate-fail">
                    <button type="submit" name="simulate" value="fail" style="background-color: red; color: white;">Simulate Payment Failure</button>
                </div>
            </form>
    </div>

    <script>
       document.addEventListener("DOMContentLoaded", function () {
            const methodRadios = document.querySelectorAll('input[name="payment_method"]');
            const taxRateElement = document.getElementById('tax-rate');
            const totalElement = document.getElementById('total-price');
            const discount = <?= json_encode($discount) ?>;
            const shippingFee = <?= json_encode($shippingFee) ?>;
            const total = <?= json_encode($total) ?>;

            const cardFields = document.getElementById('card-fields');
            const eWallet = document.getElementById('e-wallet');
            const bankList = document.getElementById('bank-list');

            function updateTotal(taxRate) {
                const taxAmount = total * taxRate;
                const discountAmount = total * (discount / 100);
                const finalTotal = total - discountAmount + taxAmount + shippingFee;
                
                taxRateElement.innerHTML = `Tax (${(taxRate * 100).toFixed(2)}%): <span class="price">RM${taxAmount.toFixed(2)}</span>`;
                totalElement.innerHTML = `Total: <span class="price">RM${finalTotal.toFixed(2)}</span>`;
            }

            methodRadios.forEach(radio => {
                radio.addEventListener("change", function () {
                    const selectedPaymentID = this.value;
                    const selectedCategory = this.getAttribute('data-category');

                    fetch(`getTaxRate.php?paymentID=${selectedPaymentID}`)
                        .then(response => response.json())
                        .then(data => {
                            const taxRate = data.taxRate / 100;
                            updateTotal(taxRate);
                        });

                    // Handle dynamic fields display
                    if (selectedCategory === "Credit/Debit Card") {
                        cardFields.style.display = "block";
                        eWallet.style.display = "none";
                        bankList.style.display = "none";
                    } else if (selectedCategory === "E-wallet") {
                        cardFields.style.display = "none";
                        eWallet.style.display = "block";
                        bankList.style.display = "none";
                        // Fetch the new QR Code
                        fetch(`generateQRCode.php?orderID=<?= $orderID ?>&paymentID=${selectedPaymentID}`)
                            .then(response => response.json())
                            .then(data => {
                                const qrImage = document.getElementById('qrCode');
                                const qrAmount = document.getElementById('qrAmount');
                                qrImage.src = `data:image/png;base64,${data.qr}`;
                                qrImage.style.display = 'block';
                                qrAmount.innerText = `Amount: RM${parseFloat(data.amount).toFixed(2)}`;
                            });
                    } else if (selectedCategory === "Online Banking") {
                        cardFields.style.display = "none";
                        eWallet.style.display = "none";
                        bankList.style.display = "block";
                    } else {
                        cardFields.style.display = "none";
                        eWallet.style.display = "none";
                        bankList.style.display = "none";
                    }
                });
            });
        });
    </script>
</body>
</html>
