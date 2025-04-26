<?php
session_start();
require_once '../base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: ../home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

$records_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

$search = isset($_GET['search']) ? "%{$_GET['search']}%" : '%';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'date';

$query = "SELECT o.orderID, o.userID, u.userName, o.orderDate, o.orderStatus 
          FROM `Order` o
          JOIN user u ON o.userID = u.userID
          WHERE (o.orderID LIKE :search OR u.userName LIKE :search)";

$params = ['search' => $search];

if ($status != 'all') {
    $query .= " AND o.orderStatus = :status";
    $params['status'] = $status;
}

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

$countQuery = str_replace('o.orderID, o.userID, u.userName, o.orderDate, o.orderStatus', 'COUNT(*) as total', $query);
$stmt = $_db->prepare($countQuery);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

$query .= " ORDER BY $orderBy LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $records_per_page;

$stmt = $_db->prepare($query);
foreach ($params as $key => $value) {
    if ($key == 'offset' || $key == 'limit') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Maintenance - PopZone Collectibles</title>
    <link rel="stylesheet" href="admin_dashboard.php">
    <link rel="stylesheet" href="/css/maintenanceOrder.css">
</head>

<body>
    <?php include '../adminheader.php'; ?>

    <div class="container">
        <h1>Order Maintenance</h1>

        <div class="filter-section">
            <form method="get" action="maintenanceOrder.php" class="filter-form">
                <div class="search-group">
                    <input type="text" name="search" class="search-box"
                        placeholder="Search by Order ID or Username..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>

                <div class="status-group">
                    <select name="status" class="status-filter">
                        <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="payed" <?= $status == 'payed' ? 'selected' : '' ?>>Payed</option>
                        <option value="sendout" <?= $status == 'sendout' ? 'selected' : '' ?>>Send Out</option>
                        <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>

                <div class="sort-group">
                    <select name="sort" class="sort-filter">
                        <option value="date" <?= $sort == 'date' ? 'selected' : '' ?>>Sort by Date</option>
                        <option value="id_asc" <?= $sort == 'id_asc' ? 'selected' : '' ?>>Order ID ↑</option>
                        <option value="id_desc" <?= $sort == 'id_desc' ? 'selected' : '' ?>>Order ID ↓</option>
                    </select>
                    <button type="submit" class="btn btn-sort">Sort</button>
                </div>
            </form>
        </div>

        <div class="total-records">
            Total Records: <?= $total_records ?>
        </div>

        <table class="order-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Order ID</th>
                    <th>Username</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th style="border-left: 1px solid #ddd;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $index => $order): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><?= htmlspecialchars($order['orderID']) ?></td>
                        <td><?= htmlspecialchars($order['userName']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['orderDate'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $order['orderStatus'] ?>">
                                <?= ucfirst($order['orderStatus']) ?>
                            </span>
                        </td>
                        <td class="action-btns" style="border-left: 1px solid #ddd;">
                            <a href="/orderInformation/maintenanceOrderItem.php?id=<?= $order['orderID'] ?>"
                                class="btn btn-view">View Detail</a>
                            <?php if ($order['orderStatus'] == 'payed'): ?>
                                <form method="post" action="sendOutOrder.php" style="display:inline;">
                                    <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                                    <button type="submit" class="btn btn-view">Sent Out</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="page-item" href="<?= getPaginationLink(1) ?>">First</a>
                <a class="page-item" href="<?= getPaginationLink($page - 1) ?>">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a class="page-item <?= $i == $page ? 'active' : '' ?>"
                    href="<?= getPaginationLink($i) ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a class="page-item" href="<?= getPaginationLink($page + 1) ?>">Next</a>
                <a class="page-item" href="<?= getPaginationLink($total_pages) ?>">Last</a>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>

<?php
function getPaginationLink($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return 'maintenanceOrder.php?' . http_build_query($params);
}
?>