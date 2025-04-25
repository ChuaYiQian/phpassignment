<?php
session_start();
require_once 'base.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
    exit();
}

$cardData = [
    'customers' => 0,
    'purchase_orders' => 0,
    'sales_orders' => 0,
    'products' => 0
];

$stmt = $_db->prepare("SELECT COUNT(*) FROM user WHERE userRole = 'customer'");
$stmt->execute();
$cardData['customers'] = $stmt->fetchColumn();

$stmt = $_db->prepare("SELECT COUNT(*) FROM `Order` WHERE orderStatus = 'pending'");
$stmt->execute();
$cardData['purchase_orders'] = $stmt->fetchColumn();

$stmt = $_db->prepare("SELECT COUNT(*) FROM `Order` WHERE orderStatus IN ('sendout', 'payed', 'completed')");
$stmt->execute();
$cardData['sales_orders'] = $stmt->fetchColumn();

$stmt = $_db->prepare("SELECT COUNT(*) FROM Product");
$stmt->execute();
$cardData['products'] = $stmt->fetchColumn();

$chartData = [];
$statuses = ['sendout', 'payed', 'completed'];

foreach ($statuses as $status) {
    $stmt = $_db->prepare("
        SELECT SUM(oi.orderQuantity * p.productPrice) as total 
        FROM orderInformation oi
        JOIN Product p ON oi.productID = p.productID
        JOIN `Order` o ON oi.orderID = o.orderID
        WHERE o.orderStatus = ?
    ");
    $stmt->execute([$status]);
    $chartData[$status] = $stmt->fetchColumn() ?? 0;
}


$topProducts = [];
$totalRevenue = 0;
$otherRevenue = 0;

try {
    $stmt = $_db->prepare("
        SELECT SUM(oi.orderQuantity * p.productPrice) as total 
        FROM orderInformation oi
        JOIN Product p ON oi.productID = p.productID
        JOIN `Order` o ON oi.orderID = o.orderID
        WHERE o.orderStatus IN ('payed', 'sendout', 'completed')
    ");
    $stmt->execute();
    $totalRevenue = $stmt->fetchColumn() ?? 0;


    $stmt = $_db->prepare("
        SELECT p.productName, SUM(oi.orderQuantity * p.productPrice) as totalRevenue 
        FROM orderInformation oi
        JOIN Product p ON oi.productID = p.productID
        JOIN `Order` o ON oi.orderID = o.orderID
        WHERE o.orderStatus IN ('payed', 'sendout', 'completed')
        GROUP BY p.productID
        ORDER BY totalRevenue DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


    $topTotal = array_sum(array_column($topProducts, 'totalRevenue'));
    $otherRevenue = $totalRevenue - $topTotal;

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PopZone Collectibles</title>
    <link rel="stylesheet" href="/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'adminheader.php'; ?>

    <div class="main-container">
    <div class="card-container">
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <div class="card-title">CUSTOMERS</div>
                <div class="card-value"><?= $cardData['customers'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <div class="card-title">PURCHASE ORDERS</div>
                <div class="card-value"><?= $cardData['purchase_orders'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <div class="card-title">SALES ORDERS</div>
                <div class="card-value"><?= $cardData['sales_orders'] ?></div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-icon"></div>
                <div class="card-title">PRODUCTS</div>
                <div class="card-value"><?= $cardData['products'] ?></div>
            </div>
        </div>
        <div class="chart-container">
            <div class="pie-chart">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="top-products">
                <h3>TOP 5 PRODUCTS BY REVENUE</h3>
                <ul>
                    <?php foreach ($topProducts as $product): ?>
                        <li>
                            <span><?= htmlspecialchars($product['productName']) ?></span>
                            <span>$<?= number_format($product['totalRevenue'], 2) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($otherRevenue > 0): ?>
                        <li style="background-color: #f8f9fa;">
                            <span>Other Products</span>
                            <span>$<?= number_format($otherRevenue, 2) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <script>

    const ctx = document.getElementById('salesChart').getContext('2d');
    
    const chartLabels = [
        <?php foreach ($topProducts as $index => $product): ?>
            '<?= addslashes($product['productName']) ?>'<?= $index < count($topProducts)-1 ? ',' : '' ?>
        <?php endforeach; ?>
        <?php if (count($topProducts) > 0 && $otherRevenue > 0): ?>,<?php endif; ?>
        <?php if ($otherRevenue > 0): ?>'Other Products'<?php endif; ?>
    ];

    const chartValues = [
        <?php foreach ($topProducts as $product): ?>
            <?= floatval($product['totalRevenue']) ?>,
        <?php endforeach; ?>
        <?= floatval($otherRevenue) ?>
    ].filter(value => value > 0);

    const backgroundColors = [
        '#FF6384', '#36A2EB', '#4BC0C0', '#FFCE56', '#9966FF', '#E7E9ED'
    ].slice(0, chartValues.length);

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: chartLabels,
            datasets: [{
                data: chartValues,
                backgroundColor: backgroundColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    enabled: <?= $totalRevenue > 0 ? 'true' : 'false' ?>,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const percentage = <?= $totalRevenue > 0 ? 
                                '((value / '.$totalRevenue.') * 100).toFixed(2)' : 
                                '0' ?>;
                            return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateRotate: <?= $totalRevenue > 0 ? 'true' : 'false' ?>
            }
        }
    });

    <?php if ($totalRevenue <= 0): ?>
        document.getElementById('salesChart').parentElement.innerHTML = 
            '<div class="no-data">No sales data available</div>';
    <?php endif; ?>
</script>
</body>
</html>