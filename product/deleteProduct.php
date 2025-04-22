<?php
include '../base.php';
session_start();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $productID = $_GET['id'];
    try {
        $stm = $_db->prepare('
            DELETE FROM product WHERE productID = ?
        ');

        $stm->execute([$productID]);

        temp('info', 'Record deleted successfully');
        header('Location: /product/productMaintenance.php');
        exit();

    } catch (PDOException $e) {
        die("Error inserting data: " . $e->getMessage());
    }
} else {
    temp('error', 'Invalid or missing product ID');
    header('Location: /product/productMaintenance.php');
    exit();
}
