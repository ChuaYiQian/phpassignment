<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Page</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        form { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        input, select, button { width: 100%; margin-bottom: 10px; padding: 8px; }
    </style>
    <script>
        function togglePaymentFields() {
            let method = document.getElementById("payment_method").value;
            document.getElementById("card_fields").style.display = (method === "card") ? "block" : "none";
        }
    </script>
</head>
<body>

<h2>Make a Payment</h2>

<form action="process_payment.php" method="POST">
    <label>Amount (RM):</label>
    <input type="number" name="amount" required>

    <label>Payment Method:</label>
    <select name="payment_method" id="payment_method" onchange="togglePaymentFields()">
        <option value="card">Credit/Debit Card</option>
        <option value="tng">Touch 'n Go eWallet</option>
        <option value="fpx">FPX (Online Banking)</option>
    </select>

    <!-- Card Payment Fields -->
    <div id="card_fields">
        <label>Card Number:</label>
        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
        
        <label>Expiry Date:</label>
        <input type="month" name="expiry_date">
        
        <label>CVV:</label>
        <input type="number" name="cvv" placeholder="123" maxlength="3">
    </div>

    <button type="submit">Proceed to Pay</button>
</form>

</body>
</html>
