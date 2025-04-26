<?php 
include 'base.php'; 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

$fields = [
    'voucherID'    => 'ID',
    'voucherCode'  => 'Code',
    'startDate'    => 'Start Date',
    'endDate'      => 'End Date',
    'discountRate' => 'Discount',
    'voucherStatus'=> 'Status'
];

// Default sorting options
$sort = $_GET['sort'] ?? 'voucherID';
$dir = $_GET['dir'] ?? 'asc';

if (!array_key_exists($sort, $fields)) {
    $sort = 'voucherID';
}

$dir = ($dir === 'asc') ? 'asc' : 'desc';

$status_filter = $_GET['status'] ?? '';
$search_code = $_GET['search_code'] ?? '';

$sql = "SELECT voucherID, voucherCode, startDate, endDate, discountRate, voucherStatus FROM voucher WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND voucherStatus = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_code)) {
    $sql .= " AND voucherCode LIKE ?";
    $params[] = "%$search_code%";
    $types .= "s";
}

$sql .= " ORDER BY $sort $dir";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Store vouchers in array
$vouchers = [];
while ($row = $result->fetch_object()) {
    $vouchers[] = $row;
}

// Paging
$page = req('page', 1);
require_once 'lib/SimplePager.php';

$p = new SimplePager("SELECT * FROM voucher ORDER BY voucherID DESC", [], 10, $page);
$voucher_list = $p->result;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher List</title>
    <link rel="stylesheet" href="./css/voucher.css"> 
</head>
<body>
<?php include 'adminheader.php'; ?>
<h2>Voucher List</h2>

<p>
    <?= $p->count ?> of <?= $p->item_count ?> record(s) |
    Page <?= $p->page ?> of <?= $p->page_count ?> |
</p>

<!-- Search and Filter Form -->
<form method="GET">
    <input type="text" name="search_code" placeholder="Search by Code" value="<?= htmlspecialchars($search_code) ?>">
    <button type="submit">Search</button>
    <button type="button" onclick="window.location.href='voucher_table.php'">Reset</button>

    <select name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="Active" <?= ($status_filter === 'Active') ? 'selected' : '' ?>>Active</option>
        <option value="Expired" <?= ($status_filter === 'Expired') ? 'selected' : '' ?>>Expired</option>
    </select>
</form>

<!-- Voucher Table -->
<a href="voucher/add_voucher.php"><button>Add Voucher</button></a>
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
        <th>Actions</th>
    </tr>

    <?php if (!empty($vouchers)): ?>
        <?php foreach ($vouchers as $s): ?>
        <tr>
            <td><?= $s->voucherID ?></td>
            <td><?= htmlspecialchars($s->voucherCode) ?></td>
            <td><?= $s->startDate ?></td>
            <td><?= $s->endDate ?></td>
            <td><?= $s->discountRate ?>%</td>
            <td><?= $s->voucherStatus ?></td>
            <td>
                <a href="voucher/edit_voucher.php?id=<?= $s->voucherID ?>">Edit</a>
                <a href="voucher/delete_voucher.php?id=<?= $s->voucherID ?>" onclick="return confirm('Delete this voucher?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?= $p->html("sort=$sort&dir=$dir") ?>
    <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">No vouchers found</td></tr>
    <?php endif; ?>
</table>


</body>
</html>

<?php 

$stmt->close();
$conn->close(); 
?>