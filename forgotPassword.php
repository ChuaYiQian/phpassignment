<?php
include 'base.php';

session_start();

if (is_post()) {
    $email = req('email');

    // Validate: email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Using built-in validation
        $_err['email'] = 'Invalid email';
    } else if (!is_exists($email, 'user', 'userEmail')) { // Check if email exists in the user table
        $_err['email'] = 'Not exists';
    }

    // Send OTP (if valid)
    if (!$_err) {
        // Select user
        $stm = $_db->prepare('SELECT * FROM user WHERE userEmail = ?');
        $stm->execute([$email]);
        $u = $stm->fetch(); // This will be an object of stdClass

        if ($u) { // Check if user exists
            $otp = sprintf("%06d", mt_rand(1, 999999));

            $_SESSION['reset_otp'] = [
                'code' => $otp,
                'email' => $email,
                'expires' => time() + 300
            ];

            // Send email with OTP
            $m = get_mail();
            $m->addAddress($u->userEmail, $u->userName); // Use object syntax to access properties
            $m->isHTML(true);
            $m->Subject = 'Reset Password OTP';
            $m->Body = "
                <p>Dear {$u->userName},</p>
                <h1 style='color: red'>Reset Password</h1>
                <p>Your OTP for password reset is: <strong>$otp</strong></p>
                <p>This OTP will expire in 5 minutes.</p>
                <p>From, 😺 Admin</p>
            ";

            if ($m->send()) {
                redirect('verifyOTP.php'); // Redirect to OTP verification page
            } else {
                $_err['email'] = 'Failed to send OTP. Please try again.';
            }
        } else {
            $_err['email'] = 'User  not found.';
        }
    }
}

$_title = 'Forgot Password';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../css/forgotPassword.css">
</head>

<body>
    <div class="container">
        <div class="image-section">
            <img src="images/lock.jpg" alt="Forgot Password" width="300">
        </div>
        <div class="form-section">
            <h1><?= $_title ?></h1>
            <p>Enter your email and we'll send you a link to reset your password.</p>
            <form method="post" class="form">
                <label for="email">Email</label>
                <?= html_text('email', 'maxlength="100"') ?>
                <?= err('email') ?>

                <section>
                    <button type="submit">Submit</button>
                    <button type="reset">Reset</button>
                </section>
            </form>
            <a href="home.php" class="back-link"><i class="fa fa-arrow-circle-left" aria-hidden="true"></i> Back to home</a>
        </div>
    </div>
</body>

</html>