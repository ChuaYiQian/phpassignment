<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (isset($_SESSION['user_id'])) {

    if (!isset($_SESSION['user_profile_pic'])) {
        $stmt = $conn->prepare("SELECT userProfilePicture FROM user WHERE userID = ?");
        $stmt->bind_param("s", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_profile_pic'] = $user['userProfilePicture'];
            $_SESSION['user_role'] = $user['userRole'];
        }
        $stmt->close();
    }

    $cstmt = $_db->prepare("SELECT cartID FROM cart WHERE userID = ?");
    $cstmt->execute([$_SESSION['user_id']]);
    $cartRow = $cstmt->fetch(PDO::FETCH_ASSOC);

    if ($cartRow) {
        $cartID = $cartRow['cartID'];

        $cstmt = $_db->prepare("SELECT SUM(cartQuantity) AS total FROM cartItem WHERE cartID = ?");
        $cstmt->execute([$cartID]);
        $result = $cstmt->fetch(PDO::FETCH_ASSOC);

        $_SESSION['cart_id'] = $cartID;
        $_SESSION['cart_count'] = $result['total'] ?? 0;
    } else {
        $_SESSION['cart_count'] = 0;
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/header.css">
    <style>
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
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            border-radius: 4px;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left !important;
            font-size: 14px !important;
            font-weight: normal !important;
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

        a:hover {
            transform: translateY(-5px);
            transition-duration: 0.2s;
        }
    </style>
</head>
<header>
    <h1>PopZone Collectibles</h1>
    <nav>
        <ul>
            <li><a href="/home.php">Home</a></li>
            <li><a href="/product.php">Products</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                    <li>
                        <a href="/cart/cart.php" class="cart-icon-wrapper">
                            <img src="/images/addToCart.png" style="width: 30px; height: 30px;">
                            <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                                <span class="cart-count"><?php echo $_SESSION['cart_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="/order/userOrder.php">
                            <img src="/images/orderIcon.jpg" style="width: 30px; height: 30px;">
                        </a>
                    </li>
                <?php endif; ?>

                <li class="profile-dropdown">
                    <button class="profile-btn">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_profile_pic'] ?? '/uploads/default_profile.png'); ?>"
                            class="profile-img" alt="Profile">
                    </button>
                    <div class="dropdown-content">
                        <a href="/view_profile.php">View Profile</a>

                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff' ): ?>
                            <a href="/dashboard.php">Dashboard</a>
                        <?php endif; ?>

                        <form action="/logout.php" method="POST" style="display:inline;">
                            <button type="submit" class="logout-btn">Logout</button>
                        </form>
                    </div>

                </li>
            <?php else: ?>
                <li><button class="login-btn" onclick="openLoginPopup()">Login</button></li>
            <?php endif; ?>

        </ul>
    </nav>
</header>
<div id="loginPopup" class="popup" style="z-index: 99;">
    <div class="popup-content">
        <span class="close" onclick="closeLoginPopup()">&times;</span>
        <h2>Login</h2>
        
        <!-- Tab Navigation -->
        <div class="tab">
            <button class="tablinks" onclick="openTab(event, 'Customer')">Customer</button>
            <button class="tablinks" onclick="openTab(event, 'Admin')">Admin/Staff</button>
        </div>

        <!-- Customer Login Form -->
        <div id="Customer" class="tabcontent">
            <form action="login_process.php" method="POST">
                <div id="loginError" class="error-message" style="display: none; color: red;"></div>

                <label for="customer_username">Username/Email:</label>
                <input type="text" id="customer_username" name="username" required>

                <label for="customer_password">Password:</label>
                <input type="password" id="customer_password" name="password" required>

                <button type="submit" name="role" value="customer">Login as Customer</button>
                <a href="signup.php" style="display:block; margin-top:10px; margin-bottom:10px">New User? Sign Up Here!</a>
                <a href="forgotPassword.php" style="display:block; margin-top:10px; margin-bottom:10px">forgot password?</a>
            </form>
        </div>

        <!-- Admin/Staff Login Form -->
        <div id="Admin" class="tabcontent" style="display:none;">
            <form action="login_process.php" method="POST">
                <div id="adminError" class="error-message" style="display: none; color: red;"></div>

                <label for="admin_username">Username:</label>
                <input type="text" id="admin_username" name="username" required>

                <label for="admin_password">Password:</label>
                <input type="password" id="admin_password" name="password" required>

                <button type="submit" name="role" value="admin">Login as Admin/Staff</button>
            </form>
        </div>
    </div>
</div>

<div id="successPopup" class="popup" style="display:none; z-index:100;">
    <div class="popup-content">
        <span class="close" onclick="closeSuccessPopup()">&times;</span>
        <h2>Login Successful!</h2>
        <p>You are now logged in.</p>
        <button onclick="closeSuccessPopup()">Continue</button>
    </div>
</div>

<div id="logoutPopup" class="popup" style="display:none; z-index:100;">
    <div class="popup-content">
        <span class="close" onclick="closeLogoutPopup()">&times;</span>
        <h2>Logout Successful!</h2>
        <p>You have been successfully logged out.</p>
        <button onclick="closeLogoutPopup()">OK</button>
    </div>
</div>

<script>
    function openLoginPopup() {
        document.getElementById("loginPopup").style.display = "block";
    }

    function closeLoginPopup() {
        document.getElementById("loginPopup").style.display = "none";
        // Clear error when closing
        const errorElement = document.getElementById('loginError');
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
    }

    function openSuccessPopup() {
        closeLoginPopup();
        document.getElementById("successPopup").style.display = "block";
    }

    function closeSuccessPopup() {
        document.getElementById("successPopup").style.display = "none";
    }

    function openLogoutPopup() {
        document.getElementById("logoutPopup").style.display = "block";
    }

    function closeLogoutPopup() {
        document.getElementById("logoutPopup").style.display = "none";
    }

    function showLoginError(message) {
    const errorElement = document.getElementById('loginError');
    if (errorElement) {
        errorElement.innerHTML = message;
        errorElement.style.display = 'block';
    }
}

function openTab(evt, tabName) {
        // Hide all tab content
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Remove the active class from all tab links
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show the current tab and add an "active" class to the button that opened the tab
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

    // Check for messages when page loads
    window.onload = function () {
        <?php if (isset($_SESSION['login_error'])): ?>
            showLoginError(<?php echo json_encode($_SESSION['login_error']); ?>);
            openLoginPopup();
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_success'])): ?>
            openSuccessPopup();
            <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['logout_success'])): ?>
            openLogoutPopup();
            <?php unset($_SESSION['logout_success']); ?>
        <?php endif; ?>
    };
</script>