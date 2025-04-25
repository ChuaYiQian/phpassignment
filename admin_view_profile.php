<?php
session_start();
require_once 'base.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
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
    header("Location: admin_dashboard.php");
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
    <title>My Profile - Admin Panel</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .admin-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #1abc9c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #1abc9c;
        }
        
        .profile-info {
            flex-grow: 1;
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
            flex-grow: 1;
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
            text-align: center;
        }

        .btn-dashboard {
    background: #3498db;
    margin-left: 10px;
}
.btn-dashboard:hover {
    background: #2980b9;
}
    </style>
</head>
<body>
    <?php include 'adminHeader.php'; ?>
    
    <div class="main-content">
        <div class="admin-container">
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
                
                <div class="info-row">
                    <div class="info-label">Account Status:</div>
                    <div class="info-value"><?php echo ucfirst($user['userStatus']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Account Type:</div>
                    <div class="info-value"><?php echo ucfirst($user['userRole']); ?></div>
                </div>
            </div>
            
            <div class="profile-actions">
    <a href="admin_edit_profile.php" class="btn btn-primary">Edit Profile</a>
    <a href="admin_dashboard.php" class="btn btn-dashboard">Back to Dashboard</a>
</div>
        </div>
    </div>
</body>
</html>