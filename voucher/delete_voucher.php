<?php
include '../base.php'; 

// Check if the ID is set
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id']; 

    // Prepare the SQL query to delete the voucher
    $sql = "DELETE FROM voucher WHERE voucherID = ?"; 
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $id); // Since voucherID is a string, use "s"
        $stmt->execute();

        // Check if the deletion was successful
        if ($stmt->affected_rows > 0) {
            header("Location: ../voucher_table.php");
            exit();
        } else {
            // Handle case where the ID was not found
            echo "No voucher found with the provided ID.";
        }
    } else {
        die("SQL Error: " . $conn->error); // Handle error in preparing the statement
    }

    $stmt->close();
} else {
    echo "Invalid ID provided."; // Handle case where ID is not set or empty
}

$conn->close();
?>
