<?php
session_start();
require_once 'base.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for default admin credentials first
    if ($username === 'admin' && $password === 'admin') {
        $result = $conn->query("SELECT * FROM user WHERE userID = 'A0001' AND userStatus = 'active'");
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            $_SESSION['user_id'] = $admin['userID'];
            $_SESSION['user_name'] = $admin['userName'];
            $_SESSION['user_role'] = $admin['userRole'];
            $_SESSION['user_status'] = $admin['userStatus'];
            $_SESSION['user_profile_pic'] = $admin['userProfilePicture'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            exit();
        }
    }

    // Regular login process for other users
    $stmt = $conn->prepare("SELECT userID, userName, userPassword, userRole, userStatus, userProfilePicture FROM user WHERE (userEmail = ? OR userName = ?)");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check if account is active
        if ($user['userStatus'] != 'active') {
            $_SESSION['login_error'] = "Your account has been blocked. Please contact an administrator.";
            header("Location: home.php");
            exit();
        }

        // Verify password
        if (password_verify($password, $user['userPassword'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['user_name'] = $user['userName'];
            $_SESSION['user_role'] = $user['userRole'];
            $_SESSION['user_status'] = $user['userStatus'];
            $_SESSION['user_profile_pic'] = $user['userProfilePicture'];

            // Set success message
            $_SESSION['login_success'] = true;

            // Redirect based on role
            if ($user['userRole'] == 'admin') {
                header("Location: dashboard.php");
            } elseif ($user['userRole'] == 'staff') {
                header("Location: home.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            // Incorrect password
            $_SESSION['login_error'] = "Incorrect password";
            header("Location: home.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "User not found";
        header("Location: home.php");
        exit();
    }

    $stmt->close();
} else {
    header("Location: home.php");
    exit();
}
?>