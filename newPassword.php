<?php
include 'base.php';

session_start();


// Check if the user is allowed to reset the password
if (!isset($_SESSION['allow_password_reset']) || $_SESSION['allow_password_reset'] !== true) {
    temp('error', 'Unauthorized access. Please start the password reset process again.');
    redirect('forgotPassword.php');
}

// Get the email from the session variable
$email = $_SESSION['reset_email'];

if (is_post()) {
    $password = req('password');
    $confirm  = req('repeat');

    // Validate password
    if ($password == '') {
        $_err['password'] = 'Required';
    } else if (strlen($password) < 8) { // Ensure minimum length is 8
        $_err['password'] = 'Password must be at least 8 characters';
    }

    // Validate password confirmation
    if ($confirm == '') {
        $_err['repeat'] = 'Required';
    } else if ($confirm !== $password) {
        $_err['repeat'] = 'Passwords do not match';
    }

    // If no errors, proceed with password reset
    if (empty($_err)) {
        // Hash the new password using password_hash
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement to update the user's password
        $stmt = $conn->prepare('UPDATE user SET userPassword = ? WHERE userEmail = ?');
        $stmt->bind_param("ss", $hashed_password, $email);

        if ($stmt->execute()) {
            // Clear session variables related to password reset
            unset($_SESSION['allow_password_reset']);
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);

            $_SESSION['success'] = 'Password updated successfully.';
            redirect('home.php'); // 
        } else {
            $_err['database'] = 'Failed to update password. Please try again.';
        }
        $stmt->close();
    }
}

$_title = 'Reset Password';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_title) ?></title>
    <link rel="stylesheet" href="../css/newPassword.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
    <div class="container">
        <div class="password-reset-form">
            <h1>Reset Your Password</h1>
            <p>Please enter your new password below.</p>

            <form method="post" class="form" id="newPasswordForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email) ?>" readonly class="form-control readonly-email">
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" class="form-control" maxlength="100" required>
                    <span id="passwordFeedback" class="feedback"><?= err('password') ?></span>
                </div>

                <div class="form-group">
                    <label for="confirm">Confirm New Password</label>
                    <input type="password" name="repeat" id="confirm" class="form-control" maxlength="100" required>
                    <span id="repeatFeedback" class="feedback"><?= err('repeat') ?></span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                    <button type="reset" class="btn btn-secondary">Clear</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../JS/register.js"></script>
    <script src="../JS/initializer.js">
        $(document).ready(function() {
            // Additional JavaScript if needed
        });
    </script>
</body>

</html>