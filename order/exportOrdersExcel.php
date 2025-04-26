<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
  header("HTTP/1.1 403 Forbidden");
  exit('Access Denied');
}

$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';

switch ($sort) {
  case 'id_asc':
    $orderBy = 'o.orderID ASC';
    break;
  case 'id_desc':
    $orderBy = 'o.orderID DESC';
    break;
  default:
    $orderBy = 'o.orderDate DESC';
}

$query = "SELECT 
            o.orderID, 
            u.userName, 
            o.orderTotal, 
            o.orderDate, 
            o.orderStatus 
          FROM `Order` o
          JOIN user u ON o.userID = u.userID
          WHERE (o.orderID LIKE :search OR u.userName LIKE :search)";

$params = [':search' => $search];

if ($status != 'all') {
  $query .= " AND o.orderStatus = :status";
  $params[':status'] = $status;
}

$query .= " ORDER BY $orderBy";

try {
  $stmt = $_db->prepare($query);
  
  foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
  }
  
  $stmt->execute();
  $exportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("export failures: " . $e->getMessage());
  header("HTTP/1.1 500 Internal Server Error");
  exit('error');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Orders_' . date('YmdHis') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
  'Order ID', 
  'Username', 
  'Order Total', 
  'Date', 
  'Status'
]);

foreach ($exportData as $row) {
  $formattedRow = [
    'orderID' => $row['orderID'],
    'userName' => $row['userName'],
    'orderTotal' => '$' . number_format((float)$row['orderTotal'], 2),
    'orderDate' => date('d/m/Y H:i', strtotime($row['orderDate'])),
    'orderStatus' => ucfirst($row['orderStatus'])
  ];
  
  fputcsv($output, $formattedRow);
}

fclose($output);
exit();
?>