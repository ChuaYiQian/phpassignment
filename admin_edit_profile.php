<?php
session_start();
require_once 'base.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = [];
$errors = [];
$success = false;

// Get admin data
$stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $age = intval($_POST['age']);
    
    // Handle file upload
    $profile_pic = $user['userProfilePicture'];
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'images/';
        $file_name = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $target_path = $upload_dir . $file_name;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES['profile_pic']['tmp_name']);
        if ($check !== false) {
            // Check file size (5MB max)
            if ($_FILES['profile_pic']['size'] <= 5000000) {
                // Allow certain file formats
                $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_path)) {
                        // Delete old profile picture if it's not the default
                        if ($profile_pic != 'images/default_profile.png' && file_exists($profile_pic)) {
                            unlink($profile_pic);
                        }
                        $profile_pic = $target_path;
                    } else {
                        $errors[] = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $errors[] = "Sorry, your file is too large (max 5MB).";
            }
        } else {
            $errors[] = "File is not an image.";
        }
    }

    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($gender) || !in_array($gender, ['M', 'F'])) $errors[] = "Valid gender is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if ($age < 13) $errors[] = "You must be at least 13 years old";

    // Check if email exists for another user
    $stmt = $conn->prepare("SELECT userID FROM user WHERE userEmail = ? AND userID != ?");
    $stmt->bind_param("ss", $email, $user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $errors[] = "Email already in use by another account";
    $stmt->close();

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE user SET userName = ?, userGender = ?, userEmail = ?, userPhoneNum = ?, userAddress = ?, userAge = ?, userProfilePicture = ? WHERE userID = ?");
        $stmt->bind_param("sssssiss", $name, $gender, $email, $phone, $address, $age, $profile_pic, $user_id);

        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['user_name'] = $name;
            $_SESSION['user_profile_pic'] = $profile_pic;
            
            $success = true;
        } else {
            $errors[] = "Update failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Admin Panel Styling */
        .sidebar {
            width: 150px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: fixed;
        }

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

        .main-content {
            margin-left: 155px;
            padding-top: 60px;
            margin-right: 10px;
        }

        /* Profile Edit Form Styling */
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

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }

        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #1abc9c;
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
            margin-right: 10px;
        }

        .btn-primary {
            background: #1abc9c;
        }

        .btn-primary:hover {
            background: #16a085;
        }

        .btn-secondary {
            background: #3498db;
        }

        .btn-secondary:hover {
            background: #2980b9;
        }

        .error {
            color: #e74c3c;
            background: #fadbd8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .success {
            color: #27ae60;
            background: #d5f5e3;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .form-actions {
            margin-top: 20px;
        }
    </style>
    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const preview = document.getElementById('profilePicPreview');
                preview.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</head>
<body>
    <?php include 'adminHeader.php'; ?>
    
    <div class="main-content">
        <div class="admin-container">
            <h1>Edit Admin Profile</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <p>Profile updated successfully!</p>
                    <a href="admin_view_profile.php" class="btn btn-primary">View Profile</a>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Profile Picture:</label>
                        <img id="profilePicPreview" src="<?= htmlspecialchars($user['userProfilePicture']) ?>" class="profile-pic-preview" alt="Current Profile Picture">
                        <input type="file" name="profile_pic" accept="image/*" onchange="previewImage(event)">
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['userName']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['userEmail']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender:</label>
                        <select id="gender" name="gender" required>
                            <option value="M" <?= $user['userGender'] == 'M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= $user['userGender'] == 'F' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number:</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['userPhoneNum']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address:</label>
                        <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($user['userAddress']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" id="age" name="age" min="13" value="<?= htmlspecialchars($user['userAge']) ?>" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="admin_view_profile.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>