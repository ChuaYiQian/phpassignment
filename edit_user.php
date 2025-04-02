<?php
session_start();
require_once 'base.php';

// Check permissions
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$user_id = $_GET['id'] ?? '';
$user = [];
$errors = [];
$success = false;

// Get user data
$stmt = $conn->prepare("SELECT * FROM user WHERE userID = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user exists
if (!$user) {
    header("Location: admin_dashboard.php");
    exit();
}

// Check permissions (admin can edit anyone, staff can only edit customers)
if ($_SESSION['user_role'] == 'staff' && $user['userRole'] != 'customer') {
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
    $status = $_POST['status'];

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
        $stmt = $conn->prepare("UPDATE user SET userName = ?, userGender = ?, userEmail = ?, userPhoneNum = ?, userAddress = ?, userAge = ?, userStatus = ? WHERE userID = ?");
        $stmt->bind_param("sssssiss", $name, $gender, $email, $phone, $address, $age, $status, $user_id);

        if ($stmt->execute()) {
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
    <title>Edit User - PopZone Collectibles</title>
    <style>
        /* Add your styling here */
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Edit User <?= htmlspecialchars($user['userID']) ?></h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <p>User updated successfully!</p>
                <a href="admin_dashboard.php">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['userName']) ?>" required>
                </div>
                
                <!-- Add all other form fields with current values -->
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="active" <?= $user['userStatus'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['userStatus'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>