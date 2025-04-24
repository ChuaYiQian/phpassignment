<?php
session_start();
$orderID = $_GET['orderID'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Failed</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
        h1 { color: red; }
    </style>
</head>
<body>
    <h1>Payment Failed</h1>
    <?php if (isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php else: ?>
        <p>An unexpected error occurred. Please try again.</p>
    <?php endif; ?>
    <p>Your payment for Order <strong>#<?= htmlspecialchars($orderID) ?></strong> was not successful.</p>
    <p>Please try again or contact customer support.</p>
    <a href="payment.php?orderID=<?= urlencode($orderID) ?>">← Back to Payment</a>
</body>
</html>