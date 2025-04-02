<?php 
include 'db.php'; 

$fields = [
    'id'          => 'ID',
    'code'        => 'Code',
    'discount'    => 'Discount',
    'status'      => 'Status',
    'quantity'    => 'Quantity',
    'created_at'  => 'Created At',
    'expiry_date' => 'Expiry Date'
];

$sort = $_GET['sort'] ?? 'id';
$dir = $_GET['dir'] ?? 'asc';

if (!array_key_exists($sort, $fields)) {
    $sort = 'id';
}
$dir = ($dir === 'asc') ? 'asc' : 'desc';

$status_filter = $_GET['status'] ?? '';
$search_code = $_GET['search_code'] ?? '';

$sql = "SELECT id, code, discount, status, quantity, created_at, expiry_date FROM vouchers WHERE 1=1";
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if (!empty($search_code)) {
    $sql .= " AND code LIKE ?";
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

$vouchers = [];
while ($row = $result->fetch_object()) {
    $vouchers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher List</title>
    <link rel="stylesheet" href="./css/voucher.css"> 
</head>
<body>

<h2>Voucher List</h2>

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
            <td><?= $s->id ?></td>
            <td><?= htmlspecialchars($s->code) ?></td>
            <td><?= $s->discount ?>%</td>
            <td><?= $s->status ?></td>
            <td><?= max(0, $s->quantity) ?></td>
            <td><?= $s->created_at ?></td>
            <td><?= $s->expiry_date ?></td>
            <td>
                <a href="product/edit_voucher.php?id=<?= $s->id ?>">Edit</a>
                <a href="product/delete_voucher.php?id=<?= $s->id ?>" onclick="return confirm('Delete this voucher?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="8" style="text-align:center;">No vouchers found</td></tr>
    <?php endif; ?>
</table>

<a href="product/add_voucher.php"><button>Add Voucher</button></a>

</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>
