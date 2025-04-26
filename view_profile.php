<?php
session_start();
require_once 'base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: home.php");
    exit();
}

// Update profile picture in session if it's different
if (!isset($_SESSION['user_profile_pic']) || $_SESSION['user_profile_pic'] !== $user['userProfilePicture']) {
    $_SESSION['user_profile_pic'] = $user['userProfilePicture'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PopZone Collectibles</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            text-align: start !important;
        }
        
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-left: 0 !important;

        }
        
        .profile-header {
            display: flex;
            margin-bottom: 20px;
        }
        
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #3498db;
            margin-top:15px;
        }
        
        .profile-info {
        
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #2c3e50;
        }
        
        .info-value {
            
            color: #34495e;
        }
        
        .btn {
            display: inline-block;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .btn-primary {
            background: #1abc9c;
        }
        
        .btn-primary:hover {
            background: #16a085;
        }
        
        .profile-actions {
            margin-top: 20px;
        }

        .btn-secondary {
            background: #3498db;
    margin-left: 10px;
}
.btn-secondary:hover {
    background: #2980b9;
}
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>My Profile</h1>
        
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($user['userProfilePicture']); ?>" class="profile-pic" alt="Profile Picture">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['userName']); ?></h2>
                <p>Member since: <?php echo date('F Y', strtotime($user['createdDate'] ?? 'now')); ?></p>
            </div>
        </div>
        
        <div class="profile-details">
            <div class="info-row">
                <div class="info-label">User ID:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['userID']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['userEmail']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Gender:</div>
                <div class="info-value"><?php echo $user['userGender'] == 'M' ? 'Male' : 'Female'; ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Phone Number:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['userPhoneNum']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Address:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['userAddress']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Age:</div>
                <div class="info-value"><?php echo htmlspecialchars($user['userAge']); ?></div>
            </div>
        </div>
        
        <div class="profile-actions" style="text-align: center;">
    <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
    <a href="home.php" class="btn btn-secondary">Back to Home</a>
</div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>