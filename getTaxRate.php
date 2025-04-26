<?php
include 'base.php';

if (isset($_GET['paymentID'])) {
    $paymentID = $_GET['paymentID'];

    $stmt = $conn->prepare("SELECT taxRate FROM paymentmethod WHERE paymentID = ?");
    $stmt->bind_param("s", $paymentID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(['taxRate' => $row['taxRate']]);
    } else {
        echo json_encode(['taxRate' => 0]);
    }
}
?>
