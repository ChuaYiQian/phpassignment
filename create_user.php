<?php
session_start();
require_once 'base.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'customer') {
    header("Location: home.php");
    temp('error', 'You do not have permission to access this page.');
    exit();
}

$role = $_GET['role'] ?? 'customer';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle AJAX validation requests
    if (isset($_POST['validate']) && $_POST['validate'] === 'true') {
        header('Content-Type: application/json');

        $field = isset($_POST['username']) ? 'userName' : (isset($_POST['email']) ? 'userEmail' : null);
        $value = $_POST[$field === 'userName' ? 'username' : 'email'] ?? null;
        $dbField = $field;

        if (!$field || !$value) {
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }

        $result = ['status' => 'available'];

        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE $dbField = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                echo json_encode(['status' => 'duplicate']);
            } else {
                echo json_encode(['status' => 'available']);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo json_encode(['error' => 'Database error occurred']);
        }
        exit;
    }

    // Regular form submission
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
        $profile_pic = 'images/default_profile.png';

        $stmt = $conn->prepare("INSERT INTO user (userID, userName, userGender, userEmail, userPhoneNum, userPassword, userAddress, userProfilePicture, userStatus, userRole, userAge, verifystatus) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, 'verified')");
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
        .form-group { margin-bottom: 15px; position: relative; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select, textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background-color: #45a049; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .feedback {
            font-size: 12px;
            padding: 5px 0;
            transition: all 0.3s ease;
        }
        .feedback.valid {
            color: green;
        }
        .feedback.invalid {
            color: red;
        }
        input.valid {
            border: 1px solid green;
        }
        input.invalid {
            border: 1px solid red;
        }
    </style>
</head>
<body>
    
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
            <form id="createForm" method="POST">
                <input type="hidden" name="role" value="<?= $role ?>">
                
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                    <span id="nameFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <span id="emailFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (min 8 characters):</label>
                    <input type="password" id="password" name="password" required>
                    <span id="passwordFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span id="confirm_passwordFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                    <span id="genderFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" required>
                    <span id="phoneFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required></textarea>
                    <span id="addressFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="13" required>
                    <span id="ageFeedback" class="feedback"></span>
                </div>
                
                <button type="submit" class="btn btn-primary">Create <?= ucfirst($role) ?></button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeCreateForm();
    });

    function initializeCreateForm() {
        const fields = ['name', 'email', 'password', 'confirm_password', 'phone', 'address', 'age', 'gender'];

        fields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', () => validateField(field));
                input.addEventListener('blur', () => validateField(field));
            }
        });

        document.getElementById('createForm').addEventListener('submit', function(event) {
            let hasErrors = false;
            
            fields.forEach(field => {
                validateField(field);
                const input = document.getElementById(field);
                if (input && input.classList.contains('invalid')) {
                    hasErrors = true;
                }
            });

            if (hasErrors) {
                event.preventDefault();
                alert('Please correct the errors before submitting.');
            }
        });
    }

    function validateField(field) {
        const input = document.getElementById(field);
        const feedback = document.getElementById(`${field}Feedback`);

        if (!input || !feedback) return;

        const value = input.value.trim();

        switch (field) {
            case 'name':
                if (value === '') {
                    setInvalid(input, feedback, 'Name cannot be empty.');
                } else {
                    checkDuplicate('name', 'username');
                }
                break;
            case 'email':
                if (value === '') {
                    setInvalid(input, feedback, 'Email cannot be empty.');
                } else if (!isValidEmail(value)) {
                    setInvalid(input, feedback, 'Please enter a valid email address.');
                } else {
                    checkDuplicate('email', 'email');
                }
                break;
            case 'phone':
                validatePhone();
                break;
            case 'address':
                if (value === '') {
                    setInvalid(input, feedback, 'Address cannot be empty.');
                } else {
                    setValid(input, feedback, 'Valid address.');
                }
                break;
            case 'age':
                validateAge();
                break;
            case 'gender':
                if (value === '') {
                    setInvalid(input, feedback, 'Please select a gender.');
                } else {
                    setValid(input, feedback, 'Valid selection.');
                }
                break;
            case 'password':
                if (value === '') {
                    setInvalid(input, feedback, 'Password cannot be empty.');
                } else if (value.length < 8) {
                    setInvalid(input, feedback, 'Password must be at least 8 characters.');
                } else {
                    setValid(input, feedback, 'Valid password.');
                }
                break;
            case 'confirm_password':
                const password = document.getElementById('password').value;
                if (value === '') {
                    setInvalid(input, feedback, 'Please confirm your password.');
                } else if (value !== password) {
                    setInvalid(input, feedback, 'Passwords do not match.');
                } else {
                    setValid(input, feedback, 'Passwords match.');
                }
                break;
            default:
                if (value === '') {
                    setInvalid(input, feedback, `${field.charAt(0).toUpperCase() + field.slice(1)} cannot be empty.`);
                } else {
                    setValid(input, feedback, '');
                }
        }
    }

    function validatePhone() {
        var phone = document.getElementById("phone").value.trim();
        var phoneFeedback = document.getElementById("phoneFeedback");

        var phoneRegex = /^\+?[0-9]{10,14}$/;

        if (phone === "") {
            setInvalid(document.getElementById("phone"), phoneFeedback, 'Phone number cannot be empty.');
        } else if (!phoneRegex.test(phone)) {
            setInvalid(document.getElementById("phone"), phoneFeedback, 'Invalid phone number format. Please enter 10-14 digits.');
        } else {
            setValid(document.getElementById("phone"), phoneFeedback, 'Valid phone number.');
        }
    }

    function validateAge() {
        var age = document.getElementById("age").value.trim();
        var ageFeedback = document.getElementById("ageFeedback");

        if (age === "") {
            setInvalid(document.getElementById("age"), ageFeedback, 'Age cannot be empty.');
        } else if (isNaN(age) || parseInt(age) < 13) {
            setInvalid(document.getElementById("age"), ageFeedback, 'You must be at least 13 years old.');
        } else {
            setValid(document.getElementById("age"), ageFeedback, 'Valid age.');
        }
    }

    function isValidEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
        return re.test(email);
    }

    function checkDuplicate(fieldId, fieldType) {
        const field = document.getElementById(fieldId);
        const feedback = document.getElementById(`${fieldId}Feedback`);
        const fieldValue = field.value.trim();

        if (fieldValue === "") {
            setInvalid(field, feedback, 'This field cannot be empty.');
            return;
        }

        if (fieldType === 'email' && !isValidEmail(fieldValue)) {
            setInvalid(field, feedback, 'Invalid email format.');
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "create_user.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);

                        if (response.status === 'duplicate') {
                            setInvalid(field, feedback, `${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} already exists.`);
                        } else if (response.status === 'available') {
                            setValid(field, feedback, `${fieldType.charAt(0).toUpperCase() + fieldType.slice(1)} is available.`);
                        } else {
                            setInvalid(field, feedback, `Error in checking ${fieldType}.`);
                        }
                    } catch (e) {
                        console.error("Error parsing JSON response:", e);
                        setInvalid(field, feedback, 'Unexpected response from the server.');
                    }
                } else {
                    console.error("Error: Could not contact the server.");
                    setInvalid(field, feedback, `Unable to check ${fieldType} due to server error.`);
                }
            }
        };

        xhr.send(`validate=true&${fieldType}=${encodeURIComponent(fieldValue)}`);
    }

    function setValid(input, feedback, message) {
        input.classList.remove('invalid');
        input.classList.add('valid');
        feedback.textContent = message;
        feedback.className = 'feedback valid';
    }
    
    function setInvalid(input, feedback, message) {
        input.classList.remove('valid');
        input.classList.add('invalid');
        feedback.textContent = message;
        feedback.className = 'feedback invalid';
    }
    </script>
</body>
</html>