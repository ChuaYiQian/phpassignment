<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/header.css">
</head>
    <header>
        <h1>PopZone Collectibles</h1>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="product.php">Products</a></li>
                <li><a href="aboutUs.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><button class="login-btn" onclick="openLoginPopup()">Login</button></li>
            </ul>
        </nav>
    </header>

<!-- Login Popup -->
<div id="loginPopup" class="popup" style="z-index: 99;">
    <div class="popup-content">
        <span class="close" onclick="closeLoginPopup()">&times;</span>
        <h2>Login</h2>
        <form action="login_process.php" method="POST">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="admin" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="admin" required>

            <a href="signup.php" style="display:block">New User? Sign Up Here!</a>
            
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
    if(urlParams.has('login') && urlParams.get('login') === 'success') {
        openSuccessPopup();
    }
};
</script>