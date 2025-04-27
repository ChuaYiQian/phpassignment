<?php
require_once 'base.php';

// Check if this is being accessed from a link
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    // Validate the email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Generate a new activation token hash
        $activation_token_hash = sha1(uniqid() . rand());

        // Update the user's record with the new token
        $stmt = $_db->prepare("UPDATE user SET accountactivationtoken = ?, tokenexpiresat = ? WHERE userEmail = ?");
        $token_expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $stmt->execute([$activation_token_hash, $token_expires_at, $email]);

        // Send the verification email
        if (verification_email($email, $activation_token_hash)) {
            $_SESSION['login_error'] = "Verification email has been resent. Please check your inbox.";
        } else {
            $_SESSION['login_error'] = "Failed to resend verification email. Please try again later.";
        }
    } else {
        $_SESSION['login_error'] = "Invalid email address.";
    }
    
    // Redirect back to the home page
    header("Location: home.php");
    exit;
}

// Handle invalid request method
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
?>