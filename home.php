<?php
include 'base.php';
$arr = $_db->query('SELECT * FROM product ORDER BY salesCount DESC LIMIT 5')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PopZone Collectibles</title>
    <link rel="stylesheet" href="/css/home.css">
    <link rel="stylesheet" href="/css/header.css">
    <style>
        /* Profile Dropdown Styles */
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
            text-align: left !important;
            font-size:14px !important;
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

        /* Popup Styles */
        .popup {
            display: none;
          
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .popup-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 400px;
            border-radius: 5px;
            position: relative;
        }

        .popup .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .error-message {
            color: #d9534f;
            background-color: #f2dede;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .success-message {
            color: #3c763d;
            background-color: #dff0d8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        /* Login Button Styles */
        .login-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .login-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="hero">
        <div class="hero-overlay">
            <h2>Discover the Magic of Collectibles</h2>
            <p>Limited edition art toys and designer figurines.</p>
            <a href="product.php" class="btn">ShopNow</a>
        </div>
    </div>

    <h2 style="text-align: center;">Top 5 Product</h2>
    <div class="slider-container">
        <button class="prev" onclick="moveSlide(-1)">&#10094;</button>
        <div class="slider">
            <?php foreach ($arr as $p): ?>
                <?php
                $images = explode(',', $p->productPicture);
                $firstImage = $images[0];
                ?>
                <div class="slide">
                    <a href="productDetails.php?id=<?= $p->productID ?>" class="slide-link">
                        <img src="/images/<?= $firstImage ?>" alt="<?= htmlspecialchars($p->productName) ?>">
                        <p><?= htmlspecialchars($p->productName) ?></p>
                        <p>RM <?= number_format($p->productPrice, 2) ?></p>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
        <button class="next" onclick="moveSlide(1)">&#10095;</button>
    </div>
    <script src="/js/home.js"></script>
    <?php include 'footer.php'; ?>

    <script>
        // Auto-open popup if needed when page loads
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['keep_login_modal_open']) && $_SESSION['keep_login_modal_open']): ?>
            openLoginPopup();
            <?php $_SESSION['keep_login_modal_open'] = false; ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_error'])): ?>
            showLoginError("<?php echo addslashes($_SESSION['login_error']); ?>");
            <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['logout_success'])): ?>
            showLogoutSuccess();
            <?php unset($_SESSION['logout_success']); ?>
        <?php endif; ?>
    });

    // Modify the form submission to handle errors without page reload
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                showLoginError(data.message);
                // Keep modal open
                openLoginPopup();
            }
        })
        .catch(error => {
            showLoginError('An error occurred. Please try again.');
        });
    });
    </script>
</body>
</html>