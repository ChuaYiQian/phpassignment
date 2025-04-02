<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/header.css">
</head>
    <header>
        <h1>PopZone Collectibles</h1>
        <nav>
            <ul>
                <li><a href="/home.php">Home</a></li>
                <li><a href="/product.php">Products</a></li>
                <li><a href="/aboutUs.php">About</a></li>
                <li><a href="/contact.php">Contact</a></li>
                <li><button class="login-btn" onclick="openLoginPopup()">Login</button></li>
            </ul>
        </nav>
    </header>

    <div id="loginPopup" class="popup">
    <div class="popup-content">
        <span class="close" onclick="closeLoginPopup()">&times;</span>
        <h2>Login</h2>
        <form>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<script>
function openLoginPopup() {
    document.getElementById("loginPopup").style.display = "block";
}

function closeLoginPopup() {
    document.getElementById("loginPopup").style.display = "none";
}
</script>
