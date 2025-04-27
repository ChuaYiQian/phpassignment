<?php
session_start();
require_once 'base.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role']; // Get the role from the form

    // Function to check if the user is verified
    function isUserVerified($user) {
        return $user['verifystatus'] === 'verified'; // Assuming 'verified' is the value indicating a verified user
    }

    // Check for admin or staff credentials
    if ($role === 'admin' || $role === 'staff') {
        // First check if user exists (regardless of status)
        $stmt = $conn->prepare("SELECT * FROM user WHERE userName = ? AND (userRole = 'admin' OR userRole = 'staff')");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Check if user is inactive
            if ($user['userStatus'] !== 'active') {
                $_SESSION['login_error'] = "Your account is banned. Please contact admin.";
                header("Location: home.php");
                exit();
            }
            if (password_verify($password, $user['userPassword'])) {
                if (isUserVerified($user)) {
                    // Set session variables for admin or staff
                    $_SESSION['user_id'] = $user['userID'];
                    $_SESSION['user_name'] = $user['userName'];
                    $_SESSION['user_role'] = $user['userRole'];
                    $_SESSION['user_status'] = $user['userStatus'];
                    $_SESSION['user_profile_pic'] = $user['userProfilePicture'];

                    // Set success message
                    $_SESSION['login_success'] = true;

                    header("Location: dashboard.php");
                    exit();
                } elseif (!isUserVerified($user)){
                    $_SESSION['login_error'] = "Account is not verified.";
                    header("Location: home.php");
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "Incorrect password.";
                header("Location: home.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "User not found.";
            header("Location: home.php");
            exit();
        }
    }

    // Check for customer credentials
    if ($role === 'customer') {
        // First check if user exists (regardless of status)
        $stmt = $conn->prepare("SELECT * FROM user WHERE (userEmail = ? OR userName = ?) AND userRole = 'customer'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $customer = $result->fetch_assoc();
            // Check if user is inactive
            if ($customer['userStatus'] !== 'active') {
                $_SESSION['login_error'] = "Your account is banned. Please contact admin.";
                header("Location: home.php");
                exit();
            }
            if (password_verify($password, $customer['userPassword'])) {
                if (isUserVerified($customer)) {
                    // Set session variables for customer
                    $_SESSION['user_id'] = $customer['userID'];
                    $_SESSION['user_name'] = $customer['userName'];
                    $_SESSION['user_role'] = $customer['userRole'];
                    $_SESSION['user_status'] = $customer['userStatus'];
                    $_SESSION['user_profile_pic'] = $customer['userProfilePicture'];

                    // Set success message
                    $_SESSION['login_success'] = true;

                    header("Location: home.php");
                    exit();
                } else {
                    $_SESSION['login_error'] = 'Customer account is not verified. <a href="resendVerification.php?email=' . urlencode($customer['userEmail']) . '">Resend Verification Email</a>';
                    header("Location: home.php");
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "Incorrect password for customer.";
                header("Location: home.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Customer user not found.";
            header("Location: home.php");
            exit();
        }
    }
} else {
    header("Location: home.php");
    exit();
}
?>