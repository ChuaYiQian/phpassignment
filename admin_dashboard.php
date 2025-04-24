<?php
session_start();
require_once 'base.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
    exit();
}

// Get all users
$users = [];
$result = $conn->query("SELECT * FROM user ORDER BY 
    CASE userRole 
        WHEN 'admin' THEN 1 
        WHEN 'staff' THEN 2 
        WHEN 'customer' THEN 3 
        ELSE 4 
    END, userID");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PopZone Collectibles</title>
    <style>
        body { font-family: Arial, sans-serif;}
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .action-btns a { margin-right: 10px; text-decoration: none; }
        .btn { padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-primary { background-color: #4CAF50; color: white; border: none; }
        .btn-danger { background-color: #f44336; color: white; border: none; }
        .btn-edit { background-color: #2196F3; color: white; border: none; }
        .tabs { display: flex; margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; background-color: #f1f1f1; margin-right: 5px; }
        .tab.active { background-color: #4CAF50; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <?php include 'adminheader.php'; ?>
    
    <div class="header">
        <h1>Admin Dashboard</h1>
        <a href="create_user.php?role=staff" class="btn btn-primary">Create New Staff</a>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="openTab(event, 'all-users')">All Users</div>
        <div class="tab" onclick="openTab(event, 'customers')">Customers</div>
        <div class="tab" onclick="openTab(event, 'staff')">Staff</div>
    </div>

    <div id="all-users" class="tab-content active">
        <h2>All Users</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['userID']) ?></td>
                    <td><?= htmlspecialchars($user['userName']) ?></td>
                    <td><?= htmlspecialchars($user['userEmail']) ?></td>
                    <td><?= htmlspecialchars($user['userRole']) ?></td>
                    <td><?= htmlspecialchars($user['userStatus']) ?></td>
                    <td class="action-btns">
                        <a href="edit_user.php?id=<?= $user['userID'] ?>" class="btn btn-edit">Edit</a>
                        <?php if ($user['userID'] != 'A0001' && $user['userID'] != $_SESSION['user_id']): ?>
                            <a href="delete_user.php?id=<?= $user['userID'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="customers" class="tab-content">
    <h2>Customers</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $customers = $conn->query("SELECT * FROM user WHERE userRole = 'customer' ORDER BY userID");
            while($customer = $customers->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($customer['userID']) ?></td>
                <td><?= htmlspecialchars($customer['userName']) ?></td>
                <td><?= htmlspecialchars($customer['userEmail']) ?></td>
                <td><?= htmlspecialchars($customer['userStatus']) ?></td>
                <td class="action-btns">
                    <a href="edit_user.php?id=<?= $customer['userID'] ?>" class="btn btn-edit">Edit</a>
                    <a href="delete_user.php?id=<?= $customer['userID'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="staff" class="tab-content">
    <h2>Staff</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $staff = $conn->query("SELECT * FROM user WHERE userRole = 'staff' ORDER BY userID");
            while($staff_member = $staff->fetch_assoc()):
            ?>
            <tr>
                <td><?= htmlspecialchars($staff_member['userID']) ?></td>
                <td><?= htmlspecialchars($staff_member['userName']) ?></td>
                <td><?= htmlspecialchars($staff_member['userEmail']) ?></td>
                <td><?= htmlspecialchars($staff_member['userStatus']) ?></td>
                <td class="action-btns">
                    <a href="edit_user.php?id=<?= $staff_member['userID'] ?>" class="btn btn-edit">Edit</a>
                    <?php if ($staff_member['userID'] != $_SESSION['user_id']): ?>
                        <a href="delete_user.php?id=<?= $staff_member['userID'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
    <script>
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }

            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html>