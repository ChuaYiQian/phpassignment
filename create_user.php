<?php
session_start();
require_once 'base.php';

// Check permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

// Only admin can create staff
if ($_SESSION['user_role'] != 'admin' && ($_GET['role'] ?? '') == 'staff') {
    header("Location: home.php");
    exit();
}

$role = $_GET['role'] ?? 'customer';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $age = intval($_POST['age']);
    $role = $_POST['role'];

    // Validation
    if (empty($name)) { $errors[] = "Name is required"; }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Valid email is required"; }
    if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters"; }
    if ($password !== $confirm_password) { $errors[] = "Passwords do not match"; }
    if (empty($gender) || !in_array($gender, ['M', 'F'])) { $errors[] = "Valid gender is required"; }
    if (empty($phone)) { $errors[] = "Phone number is required"; }
    if (empty($address)) { $errors[] = "Address is required"; }
    if ($age < 13) { $errors[] = "You must be at least 13 years old"; }

    // Check if email exists
    $stmt = $conn->prepare("SELECT userID FROM user WHERE userEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $errors[] = "Email already exists"; }
    $stmt->close();

    if (empty($errors)) {
        // Generate ID with proper prefix (C for customer, S for staff)
        $prefix = ($role == 'staff') ? 'S' : 'C'; // Determine prefix based on role
    $userID = generateUserID($conn, $prefix); // Generate user ID with prefix

        
        // Debug output
        error_log("Creating new $role with ID: $userID");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $profile_pic = 'uploads/default_profile.png';

        $stmt = $conn->prepare("INSERT INTO user (userID, userName, userGender, userEmail, userPhoneNum, userPassword, userAddress, userProfilePicture, userStatus, userRole, userAge) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)");
        $stmt->bind_param("sssssssssi", $userID, $name, $gender, $email, $phone, $hashed_password, $address, $profile_pic, $role, $age);

        if ($stmt->execute()) {
            $success = true;
            // Create cart for customers
            if ($role == 'customer') {
                $cartID = 'CRT' . substr($userID, 1);
                $current_date = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO cart (cartID, userID, createDate, updateDate) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $cartID, $userID, $current_date, $current_date);
                $stmt->execute();
            }
        } else {
            error_log("Failed to create user: " . $conn->error);
            $errors[] = "Registration failed. Please try again.";
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
    <title>Create <?= ucfirst($role) ?> - PopZone Collectibles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Create New <?= ucfirst($role) ?></h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <p><?= ucfirst($role) ?> created successfully with ID: <?= $userID ?></p>
                <a href="admin_dashboard.php">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="role" value="<?= $role ?>">
                
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (min 8 characters):</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="13" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create <?= ucfirst($role) ?></button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>