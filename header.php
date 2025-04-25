<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure profile picture is set in session
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_profile_pic'])) {
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
    </style>
</head>
<header>
    <h1>PopZone Collectibles</h1>
    <nav>
        <ul>
            <li><a href="/home.php">Home</a></li>
            <li><a href="/product.php">Products</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="/cart/cart.php">
                        <img src="/images/addToCart.png" style="width: 30px; height: 30px;">
                    </a>
                </li>
                <li>
                    <a href="/order/userOrder.php">
                        <img src="/images/orderIcon.jpg" style="width: 30px; height: 30px;">
                    </a>
                </li>
                <li class="profile-dropdown">
                    <button class="profile-btn">
                        <img src="<?php echo htmlspecialchars($_SESSION['user_profile_pic'] ?? '/uploads/default_profile.png'); ?>" class="profile-img" alt="Profile">
                    </button>
                    <div class="dropdown-content">
                        <a href="/view_profile.php">View Profile</a>
                        <form action="/logout.php" method="POST" style="display:inline;">
                            <button type="submit" style="background:none; border:none; width:100%; text-align:left; padding:12px 16px; cursor:pointer;">Logout</button>
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
        <form action="login_process.php" method="POST">
            <!-- Error message container -->
            <div id="loginError" class="error-message" style="display: none;"></div>
            
            <label for="username">Username/Email:</label>
            <input type="text" id="username" name="username" value="admin" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="admin" required>

            <a href="signup.php" style="display:block; margin-top:10px; margin-bottom:10px">New User? Sign Up Here!</a>

            <button type="submit">Login</button>
        </form>
    </div>
</div>

<!-- Success popup -->
<div id="successPopup" class="popup" style="display:none; z-index:100;">
    <div class="popup-content">
        <span class="close" onclick="closeSuccessPopup()">&times;</span>
        <h2>Login Successful!</h2>
        <p>You are now logged in.</p>
        <button onclick="closeSuccessPopup()">Continue</button>
    </div>
</div>

<script>
    function openLoginPopup() {
        document.getElementById("loginPopup").style.display = "block";
    }

    function closeLoginPopup() {
        document.getElementById("loginPopup").style.display = "none";
    }

    function openSuccessPopup() {
        closeLoginPopup();
        document.getElementById("successPopup").style.display = "block";
    }

    function closeSuccessPopup() {
        document.getElementById("successPopup").style.display = "none";
        window.location.href = "home.php";
    }

    // Check for success parameter in URL
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('login') && urlParams.get('login') === 'success') {
            openSuccessPopup();
        }
    };

    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        // Check for success
        if (urlParams.has('login') && urlParams.get('login') === 'success') {
            openSuccessPopup();
        }
        
        // Check for error
        if (urlParams.has('login') && urlParams.get('login') === 'error') {
            const message = urlParams.get('message');
            showLoginError(message);
        }
    };

    window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check for logout success
    if (urlParams.has('logout') && urlParams.get('logout') === 'success') {
        showLogoutSuccess();
    }
        
        // Open login popup if there's an error
        if (urlParams.has('login') && urlParams.get('login') === 'error') {
            const message = urlParams.get('message');
            showLoginError(message);
            openLoginPopup(); // Make sure popup is open
        }
        // Check for success
        else if (urlParams.has('login') && urlParams.get('login') === 'success') {
            openSuccessPopup();
        }
    };

    function showLoginError(message) {
        const errorElement = document.getElementById('loginError');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    function closeLoginPopup() {
        document.getElementById("loginPopup").style.display = "none";
        // Clear error when closing
        const errorElement = document.getElementById('loginError');
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
        // Clean URL by removing error parameters
        if (window.location.search.includes('login=error')) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    function showLogoutSuccess() {
    const popup = document.createElement('div');
    popup.className = 'popup';
    popup.style.zIndex = '100';
    popup.style.display = 'block';
    popup.innerHTML = `
        <div class="popup-content">
            <h2>Logout Successful!</h2>
            <p>You have been successfully logged out.</p>
            <button onclick="this.parentElement.parentElement.style.display='none'">OK</button>
        </div>
    `;
    document.body.appendChild(popup);
    
    // Remove the logout parameter from URL
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>