<?php  
include 'base.php'; 
session_start();

$fields = [
    'transactionID' => 'Transaction ID',
    'paymentID'     => 'Payment ID',
    'orderID'       => 'Order ID',
    'voucherID'     => 'Voucher ID',
    'paymentTotal'  => 'Payment Total',
    'taxRate'       => 'Tax Rate',
    'transactionDate'=> 'Transaction Date',
    'shippingFee'   => 'Shipping Fee'
];

// Default sorting options
$sort = $_GET['sort'] ?? 'transactionID';
$dir = $_GET['dir'] ?? 'asc';

if (!array_key_exists($sort, $fields)) {
    $sort = 'transactionID';
}

$dir = ($dir === 'asc') ? 'asc' : 'desc';

$sql = "SELECT transactionID, paymentID, orderID, voucherID, paymentTotal, taxRate, transactionDate, shippingFee FROM transaction ORDER BY $sort $dir";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

// Store transactions in array
$transactions = [];
while ($row = $result->fetch_object()) {
    $transactions[] = $row;
}

// Paging
$page = req('page', 1);
require_once 'lib/SimplePager.php';

$p = new SimplePager("SELECT * FROM transaction ORDER BY transactionID DESC", [], 10, $page);
$transaction_list = $p->result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transaction List</title>
    <link rel="stylesheet" href="./css/voucher.css"> 
</head>
<body>
<?php include 'adminheader.php'; ?>
<h2>Transaction List</h2>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?> |
</p>

<!-- Search and Filter Form -->
<form method="GET">
    <button type="button" onclick="window.location.href='transaction_table.php'">Reset</button>
</form>

<!-- Transaction Table -->
<br>
<table class="table">
    <tr>
        <?php foreach ($fields as $key => $label): ?>
            <th>
                <a href="?sort=<?= $key ?>&dir=<?= ($sort === $key && $dir === 'asc') ? 'desc' : 'asc' ?>">
                    <?= $label ?> <?= ($sort === $key) ? ($dir === 'asc' ? '▲' : '▼') : '' ?>
                </a>
            </th>
        <?php endforeach; ?>
    </tr>

    <?php if (!empty($transactions)): ?>
        <?php foreach ($transactions as $s): ?>
        <tr>
            <td><?= $s->transactionID ?></td>
            <td><?= $s->paymentID ?></td>
            <td><?= $s->orderID ?></td>
            <td><?= $s->voucherID ?></td>
            <td>RM <?= number_format($s->paymentTotal, 2) ?></td>
            <td><?= number_format($s->taxRate * 100, 2) ?>%</td>
            <td><?= $s->transactionDate ?></td>
            <td>RM <?= number_format($s->shippingFee, 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <?= $p->html("sort=$sort&dir=$dir") ?>
    <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No transactions found</td></tr>
    <?php endif; ?>
</table>
</body>
</html>

<?php 

$stmt->close();
$conn->close(); 
?>
