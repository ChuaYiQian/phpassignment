<?php
include '../base.php'; 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $categoryID = $_GET['id']; 
    try {
        $stm = $_db->prepare('
            DELETE FROM category WHERE categoryID = ?
        ');

        $stm->execute([$categoryID]);

        temp('info', 'Record deleted successfully');
        header('Location: /category/categoryMaintenance.php');

    } catch (PDOException $e) {
        die("Error inserting data: " . $e->getMessage());
    }
}
?>