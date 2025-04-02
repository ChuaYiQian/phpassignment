<?php
session_start();
require_once 'base.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for default admin credentials first
    if ($username === 'admin' && $password === 'admin') {
        $result = $conn->query("SELECT * FROM user WHERE userID = 'A0001'");
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            $_SESSION['user_id'] = $admin['userID'];
            $_SESSION['user_name'] = $admin['userName'];
            $_SESSION['user_role'] = $admin['userRole'];
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // Regular login process for other users
    $stmt = $conn->prepare("SELECT userID, userName, userPassword, userRole FROM user WHERE userEmail = ? OR userName = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['userPassword'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['user_name'] = $user['userName'];
            $_SESSION['user_role'] = $user['userRole'];
            
            // Redirect based on role
            if ($user['userRole'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: home.php?login=success");
            }
            exit();
        } else {
            // Incorrect password
            header("Location: home.php?login=error&message=Incorrect password");
            exit();
        }
    } else {
        // User not found
        header("Location: home.php?login=error&message=User not found");
        exit();
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: home.php");
    exit();
}
?>