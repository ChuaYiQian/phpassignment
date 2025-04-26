<?php
session_start();
require_once 'base.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

// Check if user is admin (only admins should access this page)
if ($_SESSION['user_role'] != 'admin') {
    header("Location: home.php");
    exit();
}

// Handle status toggle (block/unblock)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    // Validate action
    if (!in_array($action, ['block', 'unblock'])) {
        die("Invalid action");
    }
    
    // Prevent modifying the current admin user
    if ($user_id == $_SESSION['user_id']) {
        die("Cannot modify your own status");
    }
    
    $new_status = ($action == 'block') ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE user SET userStatus = ? WHERE userID = ?");
    $stmt->bind_param("ss", $new_status, $user_id);
    
    if ($stmt->execute()) {
        // Success - reload the page to show changes
        header("Location: admin_dashboard.php");
        exit();
    } else {
        die("Error updating user status");
    }
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
        .action-btns a, 
.action-btns form button {
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    font-family: Arial, sans-serif;
    border: none;
    display: inline-block;
    text-align: center;
    min-width: 70px; /* Ensures consistent width */
    margin-right: 5px; /* Spacing between buttons */
}

/* Remove default button styling */
.action-btns form {
    display: inline;
    margin: 0;
    padding: 0;
}

.btn {
    transition: all 0.3s ease; /* Smooth hover transition */
}

/* For the Create New Staff button specifically */
.header .btn-primary {
    margin-top: 10px;
    padding: 10px 20px;
    font-size: 16px;
    min-width: 150px;
    margin-left: auto; /* Ensures it stays aligned to the right */
}

/* Button color classes */
.btn-primary { background-color: #4CAF50; color: white; }
.btn-danger { background-color: #f44336; color: white; }
.btn-edit { background-color: #2196F3; color: white; }
.btn-block { background-color: #ff9800; color: white; }
.btn-unblock { background-color: #4CAF50; color: white; }

.action-btns a:hover, 
.action-btns form button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
        .tabs { display: flex; margin-bottom: 20px; }
        .tab { padding: 10px 20px; cursor: pointer; background-color: #f1f1f1; margin-right: 5px; }
        .tab.active { background-color: #4CAF50; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'adminheader.php'; ?>
    
    <div class="header">
    <h1>Admin Dashboard</h1>
    <a href="create_user.php?role=staff" class="btn btn-primary" style="padding: 10px 20px; font-size: 16px; min-width: 150px;">Create New Staff</a>
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
                    <td class="status-<?= htmlspecialchars($user['userStatus']) ?>">
                        <?= htmlspecialchars(ucfirst($user['userStatus'])) ?>
                    </td>
                    <td class="action-btns">
    <a href="edit_user.php?id=<?= $user['userID'] ?>" class="btn btn-edit">Edit</a>
    
    <?php if ($user['userID'] != 'A0001' && $user['userID'] != $_SESSION['user_id']): ?>
        <?php if ($user['userStatus'] == 'active'): ?>
            <form method="POST" onsubmit="return confirm('Block this user?')">
                <input type="hidden" name="user_id" value="<?= $user['userID'] ?>">
                <input type="hidden" name="action" value="block">
                <button type="submit" class="btn btn-block">Block</button>
            </form>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('Unblock this user?')">
                <input type="hidden" name="user_id" value="<?= $user['userID'] ?>">
                <input type="hidden" name="action" value="unblock">
                <button type="submit" class="btn btn-unblock">Unblock</button>
            </form>
        <?php endif; ?>
        
        <a href="delete_user.php?id=<?= $user['userID'] ?>" class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
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
                    <td class="status-<?= htmlspecialchars($customer['userStatus']) ?>">
                        <?= htmlspecialchars(ucfirst($customer['userStatus'])) ?>
                    </td>
                    <td class="action-btns">
                        <a href="edit_user.php?id=<?= $customer['userID'] ?>" class="btn btn-edit">Edit</a>
                        
                        <?php if ($customer['userStatus'] == 'active'): ?>
                            <form method="POST" onsubmit="return confirm('Block this customer? They will not be able to login.')">
                                <input type="hidden" name="user_id" value="<?= $customer['userID'] ?>">
                                <input type="hidden" name="action" value="block">
                                <button type="submit" class="btn btn-block">Block</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Unblock this customer? They will be able to login again.')">
                                <input type="hidden" name="user_id" value="<?= $customer['userID'] ?>">
                                <input type="hidden" name="action" value="unblock">
                                <button type="submit" class="btn btn-unblock">Unblock</button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="delete_user.php?id=<?= $customer['userID'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to permanently delete this customer?')">Delete</a>
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
                    <td class="status-<?= htmlspecialchars($staff_member['userStatus']) ?>">
                        <?= htmlspecialchars(ucfirst($staff_member['userStatus'])) ?>
                    </td>
                    <td class="action-btns">
                        <a href="edit_user.php?id=<?= $staff_member['userID'] ?>" class="btn btn-edit">Edit</a>
                        
                        <?php if ($staff_member['userID'] != $_SESSION['user_id']): ?>
                            <?php if ($staff_member['userStatus'] == 'active'): ?>
                                <form method="POST" onsubmit="return confirm('Block this staff member? They will not be able to login.')">
                                    <input type="hidden" name="user_id" value="<?= $staff_member['userID'] ?>">
                                    <input type="hidden" name="action" value="block">
                                    <button type="submit" class="btn btn-block">Block</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" onsubmit="return confirm('Unblock this staff member? They will be able to login again.')">
                                    <input type="hidden" name="user_id" value="<?= $staff_member['userID'] ?>">
                                    <input type="hidden" name="action" value="unblock">
                                    <button type="submit" class="btn btn-unblock">Unblock</button>
                                </form>
                            <?php endif; ?>
                            
                            <a href="delete_user.php?id=<?= $staff_member['userID'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to permanently delete this staff member?')">Delete</a>
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