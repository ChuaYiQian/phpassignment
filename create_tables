<?php

$conn = new mysqli("localhost","root","","payment_system");

if($conn->connect_error){
    die("Connection failed:" . $conn->connect_error);
}

$sql1 = "CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount DECIMAL(10,2) NOT NULL,
    status ENUM('unused', 'used') DEFAULT 'unused',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql2 = "CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    voucher_code VARCHAR(50),
    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql1) === TRUE) {
    echo "Table 'vouchers' created successfully!<br>";
} else {
    echo "Error creating 'vouchers' table: " . $conn->error . "<br>";
}

if ($conn->query($sql2) === TRUE) {
    echo "Table 'payments' created successfully!";
} else {
    echo "Error creating 'payments' table: " . $conn->error;
}

$conn->close();
?>
