<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 150px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: rgb(255, 255, 255);
            transition-duration: 0.5s;
            color: #2c3e50;
        }

        /* Header */
        .adminheader {
            width: 100%;
            height: 60px;
            background-color: #1abc9c;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 150px;
            z-index: 1;
        }

        .adminheader h1 {
            color: white;
            font-size: 20px;
        }

        .side-btn:hover {
            transition-duration: 0.5s;
            background-color: rgb(255, 255, 255);
        }
        .main-content {
    margin-left: 150px; /* To offset the sidebar */
    padding-top: 60px;  /* To offset the fixed header */
}
    </style>
</head>

<header>
    <div class="sidebar">
        <h2><?php echo "Welcome, " . $_SESSION['user_name']; ?></h2>
        <div class="side-btn">
            <a href="dashboard.php">Dashboard</a>
        </div>
        <div class="side-btn">
            <a href="/product/productMaintenance.php">Products</a>
        </div>
        <div class="side-btn">
            <a href="/order/maintenanceOrder.php">Orders</a>
        </div>
        <div class="side-btn">
            <a href="/admin_dashboard.php">Users</a>
        </div>
        <div class="side-btn">
            <a href="/cart/maintenanceCart.php">Cart</a>
        </div>
        <div class="side-btn">
            <a href="/voucher_table.php">Voucher</a>
        </div>
        <div class="side-btn">
            <a href="logout.php"><button type="submit" class="login-btn" hidden>Logout</button>Logout</a>
            <form action="/logout.php" method="POST" style="display:inline;">
                <button type="submit" class="login-btn" hidden>Logout</button>
            </form>
        </div>
    </div>
    <div class="adminheader">
        <h1>Admin Panel</h1>
    </div>
</header>
<div class="main-content">