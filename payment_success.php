<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

include 'base.php';

$orderID = $_GET['orderID'] ?? null;
$userID= $_SESSION['user_id'];
$paymentID = $_GET['paymentID'] ?? null;
$voucherID = $_SESSION['voucherID'] ?? null;
$paymentTotal = floatval($_GET['amount'] ?? 0);
$taxRate = $_SESSION['taxRate'] ?? 0.06; 
$shippingFee = $_SESSION['shippingFee'] ?? 5.00; 
$transactionDate = date("Y-m-d");

if (!$orderID) {
    $_SESSION['error'] = "Missing order ID.";
    header("Location: /home.php");
    exit;
}

if ($orderID) {
    echo "Order ID: " . htmlspecialchars($orderID);
} else {
    echo "No order ID found.";
}

$stmt = $_db->prepare("SELECT userEmail, userAddress FROM user WHERE userID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$payStmt = $_db->prepare("SELECT paymentDescription FROM paymentmethod WHERE paymentID = ?");
$payStmt->execute([$paymentID]);
$paymentMethodName = $payStmt->fetchColumn();

$recipientEmail = $user ? $user['userEmail'] : null; 
$userAddress = $user ? $user['userAddress'] : null;

// Generate the Transaction ID
function generateTransactionID($db) {
    $stmt = $db->query("SELECT transactionID FROM transaction ORDER BY transactionID DESC LIMIT 1");
    $lastID = $stmt->fetchColumn();
    
    if (!$lastID) {
        return 'T0001';
    }

    $number = (int)substr($lastID, 1) + 1;
    return 'T' . str_pad($number, 4, '0', STR_PAD_LEFT);
}

$transactionID = generateTransactionID($_db);

$insertStmt = $_db->prepare("
    INSERT INTO transaction 
    (transactionID, paymentID, orderID, voucherID, paymentTotal, taxRate, transactionDate, shippingFee)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$insertSuccess = $insertStmt->execute([
    $transactionID,
    $paymentID,
    $orderID,
    $voucherID,
    $paymentTotal,
    $taxRate,
    $transactionDate,
    $shippingFee
]);

if (!$insertSuccess) {
    $_SESSION['error'] = "Failed to record the transaction.";
    $_SESSION['orderID'] = $orderID;
    header("Location: /payment_failed.php");
    exit;
}

// Prepare email content
$subject = "Payment Confirmation - Order #$orderID";
$body = "
    <h2 style='color: #4CAF50;'>Thank you for your payment!</h2>
    <p>Your payment for Order <strong>#$orderID</strong> has been received successfully.</p>
    <h3>Payment Receipt</h3>
    <table style='border-collapse: collapse; width: 100%; max-width: 600px;'>
        <tr style='background-color: #f2f2f2;'>
            <th style='border: 1px solid #ddd; padding: 8px;'>Item</th>
            <th style='border: 1px solid #ddd; padding: 8px;'>Details</th>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>Order ID</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>$orderID</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>Payment Method</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>$paymentMethodName</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>Payment Total</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>RM " . number_format($paymentTotal, 2) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>Shipping Fee</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>RM " . number_format($shippingFee, 2) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px;'>Tax (6%)</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>RM " . number_format($paymentTotal * $taxRate, 2) . "</td>
        </tr>
    </table>
    <br>
    <h3>Shipping Address</h3>
    <p>" . htmlspecialchars($userAddress ?? 'No address available') . "</p>
    <br>
    <p style='color: gray; font-size: 12px;'>This is an automated e-receipt. No signature is required.</p>
";

try {
    $mail = get_mail(); 
    $mail->addAddress($recipientEmail, "Customer");
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(true);
    $mail->send();
} catch (Exception $e) {
    error_log("Email Error: " . $mail->ErrorInfo);
    $_SESSION['error'] = "Failed to send confirmation email.";
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Success</title>
    <meta http-equiv="refresh" content="5;url=/home.php">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; }
        h1 { color: green; }
    </style>
</head>
<body>
    <h1>Payment Successful!</h1>
    <p>A confirmation email has been sent to 
        <strong><?php echo htmlspecialchars($recipientEmail  ?? 'your email'); ?></strong>.</p>
    <p>You will be redirected to the homepage shortly...</p>
</body>
</html>
