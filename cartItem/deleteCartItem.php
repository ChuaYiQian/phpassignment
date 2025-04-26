<?php
include '../base.php';
session_start();

//roles validation
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}


// Check if required fields are set
if (!isset($_POST['productID'], $_POST['cartID'])) {
    die("Required fields missing");
}

$stmt = $_db->prepare("DELETE FROM cartItem WHERE productID = ? AND cartID = ?");
$stmt->execute([$_POST['productID'], $_POST['cartID']]);

if (isset($_POST['userID'])) {
    header("Location: /cart/cart.php");
} else if (isset($_POST['staffID'])) {
    header("Location: /cart/maintenanceCart.php");
} else {
    header("Location: /");
}
exit;
?>