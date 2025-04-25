<?php
session_start();
$_SESSION['logout_success'] = true;
session_destroy();
session_start(); // Restart session to store our success message
$_SESSION['logout_success'] = true; // Set the success message again
header("Location: home.php");
exit();
?>