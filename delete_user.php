<?php
session_start();
require_once 'base.php';

// Check permissions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
    exit();
}

$user_id = $_GET['id'] ?? '';

// Prevent deleting yourself or the default admin
if ($user_id == $_SESSION['user_id'] || $user_id == 'A0001') {
    header("Location: admin_dashboard.php");
    exit();
}

// Get user role to check permissions
$stmt = $conn->prepare("SELECT userRole FROM user WHERE userID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user exists and permissions (staff can only delete customers)
if ($user) {
    if ($_SESSION['user_role'] == 'staff' && $user['userRole'] != 'customer') {
        header("Location: admin_dashboard.php");
        exit();
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM user WHERE userID = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->close();

    // If customer, delete their cart
    if ($user['userRole'] == 'customer') {
        $stmt = $conn->prepare("DELETE FROM cart WHERE userID = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: admin_dashboard.php");
exit();
?>