<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure profile picture is set in session
if (!isset($_SESSION['user_profile_pic'])) {
    require_once 'base.php';
    $stmt = $conn->prepare("SELECT userProfilePicture FROM user WHERE userID = ?");
    $stmt->bind_param("s", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_profile_pic'] = $user['userProfilePicture'];
    }
    $stmt->close();
}
?>
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
            font-size: 16px;
            padding: 0 10px;
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
            width: calc(100% - 150px);
            height: 60px;
            background-color: #1abc9c;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 150px;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            margin-left: 155px;
            padding-top: 60px;
            margin-right: 10px;
        }

        /* Profile dropdown styles */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
            border-radius: 4px;
        }
        
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }

        .logout-btn {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            padding: 12px 16px;
            cursor: pointer;
            font-size: 14px;
            color: black;
        }

        .logout-btn:hover {
            background-color: #f1f1f1;
        }

        /* Popup Styles */
.popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    width: 300px; /* Set a width for the popup */
    display: none; /* Initially hidden */
}

.popup-content {
    padding: 20px;
    text-align: center;
}

.popup-content h2 {
    margin-bottom: 10px;
    color: #333;
}

.popup-content p {
    margin-bottom: 20px;
    color: #666;
}

.popup-content .close {
    cursor: pointer;
    font-size: 18px;
    color: #999;
}

.popup-content .close:hover {
    color: #333; /* Change color on hover */
}

.popup-content button {
    background-color: #1abc9c;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
}

.popup-content button:hover {
    background-color: #16a085; /* Darker shade on hover */
}
    </style>
</head>

<body>
<div class="sidebar">
    <h2><?php echo "Welcome, " . htmlspecialchars($_SESSION['user_name']); ?></h2>
    <div class="side-btn">
        <a href="/home.php">Home</a>
    </div>
    <div class="side-btn">
        <a href="/dashboard.php">Dashboard</a>
    </div>
    <div class="side-btn">
        <a href="/product/productMaintenance.php">Products</a>
    </div>
    <div class="side-btn">
        <a href="/category/categoryMaintenance.php">Category</a>
    </div>
    <div class="side-btn">
        <a href="/order/maintenanceOrder.php">Orders</a>
    </div>
    
    <?php if ($_SESSION['user_role'] === 'admin'): // Check if the user is an admin ?>
        <div class="side-btn">
            <a href="/admin_dashboard.php">Users</a>
        </div>
    <?php endif; ?>

    <div class="side-btn">
        <a href="/voucher_table.php">Voucher</a>
    </div>
    <div class="side-btn">
        <a href="/payment_table.php">Payment Methods</a>
    </div>
    <div class="side-btn">
        <a href="/transaction_table.php">Transaction</a>
    </div>
</div>
    <div class="adminheader">
        <h1>Admin Panel</h1>
        <div class="profile-dropdown">
            <button class="profile-btn">
                <img src="<?php echo htmlspecialchars($_SESSION['user_profile_pic'] ?? '/uploads/default_profile.png'); ?>" class="profile-img" alt="Profile">
            </button>
            <div class="dropdown-content">
                <a href="/admin_view_profile.php" style="font-size:14px">View Profile</a>
                <form action="/logout.php" method="POST" style="display:inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </div>

      
    <div class="main-content">

    <!-- Login Successful Popup -->
    <div id="successPopup" class="popup" style="display:none; z-index:100;">
        <div class="popup-content">
            <span class="close" onclick="closeSuccessPopup()">&times;</span>
            <h2>Login Successful!</h2>
            <p>You are now logged in.</p>
            <button onclick="closeSuccessPopup()">Continue</button>
        </div>
    </div>
    <script> function openSuccessPopup() {
            document.getElementById("successPopup").style.display = "block"; // Show the success popup
        }

        function closeSuccessPopup() {
            document.getElementById("successPopup").style.display = "none"; // Hide the success popup
        }

        // Check for messages when page loads
        window.onload = function () {
            <?php if (isset($_SESSION['login_success'])): ?>
                openSuccessPopup(); // Call to open the success popup
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>
        };</script>