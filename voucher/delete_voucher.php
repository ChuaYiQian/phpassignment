<?php
include '../base.php'; 

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id']; 

    $sql = "DELETE FROM voucher WHERE voucherID = ?"; 
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $id); 
        $stmt->execute();

        // Check if the deletion was successful
        if ($stmt->affected_rows > 0) {
            header("Location: ../voucher_table.php");
            exit();
        } else {
            echo "No voucher found with the provided ID.";
        }
    } else {
        die("SQL Error: " . $conn->error); 
    }

    $stmt->close();
} else {
    echo "Invalid ID provided."; 
}

$conn->close();
?>
