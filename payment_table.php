<?php 
include 'base.php'; 
session_start();

$fields = [
    'paymentID'          => 'ID',
    'paymentDescription' => 'Description',
    'paymentIcon'        => 'Icon',
    'taxRate'            => 'Tax Rate (%)',
    'category'           => 'Category'
];

// Default sorting options
$sort = $_GET['sort'] ?? 'paymentID';
$dir = $_GET['dir'] ?? 'asc';

if (!array_key_exists($sort, $fields)) {
    $sort = 'paymentID';
}

$dir = ($dir === 'asc') ? 'asc' : 'desc';

$sql = "SELECT paymentID, paymentDescription, paymentIcon, taxRate, category FROM paymentmethod ORDER BY $sort $dir";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
while ($row = $result->fetch_object()) {
    $payments[] = $row;
}

// Paging
$page = req('page', 1);
require_once 'lib/SimplePager.php';

$paginator = new SimplePager("SELECT * FROM paymentmethod ORDER BY paymentID DESC", [], 10, $page);
$payment_list = $paginator->result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Method List</title>
    <link rel="stylesheet" href="./css/voucher.css"> 
</head>
<body>
<?php include 'adminheader.php'; ?>
<h2>Payment Method List</h2>

<p>
    <?= $paginator->count ?> of <?= $paginator->item_count ?> record(s) |
    Page <?= $paginator->page ?> of <?= $paginator->page_count ?> |
</p>

<a href="../paymentMethod/add_payment.php"><button>Add Payment Method</button></a>
<table class="table">
    <tr>
        <?php foreach ($fields as $key => $label): ?>
            <th>
                <a href="?sort=<?= $key ?>&dir=<?= ($sort === $key && $dir === 'asc') ? 'desc' : 'asc' ?>">
                    <?= $label ?> <?= ($sort === $key) ? ($dir === 'asc' ? '▲' : '▼') : '' ?>
                </a>
            </th>
        <?php endforeach; ?>
        <th>Actions</th>
    </tr>

    <?php if (!empty($payments)): ?>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td><?= $p->paymentID ?></td>
            <td><?= htmlspecialchars($p->paymentDescription) ?></td>
            <td><img src="<?= htmlspecialchars($p->paymentIcon) ?>" alt="icon" width="40"></td>
            <td><?= $p->taxRate ?>%</td>
            <td><?= htmlspecialchars($p->category) ?></td>
            <td>
                <a href="../paymentMethod/edit_payment.php?id=<?= $p->paymentID ?>">Edit</a>
                <a href="../paymentMethod/delete_payment.php?id=<?= $p->paymentID ?>" onclick="return confirm('Delete this payment method?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?= $paginator->html("sort=$sort&dir=$dir") ?>
    <?php else: ?>
        <tr><td colspan="6" style="text-align:center;">No payment methods found</td></tr>
    <?php endif; ?>
</table>

</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>
