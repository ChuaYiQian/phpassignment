<?php
include '../base.php'; 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $productID = $_GET['id']; 
    try {
        $stm = $_db->prepare('
            DELETE FROM product WHERE productID = ?
        ');

        $stm->execute([$productID]);

        temp('info', 'Record deleted successfully');
        header('Location: /product/maintenance.php');

    } catch (PDOException $e) {
        die("Error inserting data: " . $e->getMessage());
    }
}
?>