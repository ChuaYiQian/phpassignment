<?php
session_start();
include 'base.php';

$orderID = $_GET['orderID'] ?? null;
$userID= $_SESSION['user_id'];

if (!$orderID) {
    $_SESSION['error'] = "Missing order ID.";
    header("Location: /home.php");
    exit;
}

$stmt = $_db->prepare("SELECT userEmail FROM user WHERE userID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::fetch_assoc);

$recipientEmail = $user ? $user['email'] : null; 

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

// Prepare email content
$subject = "Payment Confirmation - Order #$orderID";
$body = "
    <h2>Thank you for your payment!</h2>
    <p>Your payment for Order <strong>#$orderID</strong> has been received successfully.</p>
    <p>You can now continue shopping or view your order history.</p>
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
    <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($recipientEmail); ?></strong>.</p>
    <p>You will be redirected to the homepage shortly...</p>
</body>
</html>
