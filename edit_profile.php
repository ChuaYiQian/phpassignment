<?php
session_start();

require_once 'base.php';

// Handle AJAX validation requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['validate'])) {
    header('Content-Type: application/json');
    
    $field = isset($_POST['username']) ? 'userName' : (isset($_POST['email']) ? 'userEmail' : null);
    $value = $_POST[$field === 'userName' ? 'username' : 'email'] ?? null;
    $current_id = $_POST['current_id'] ?? null;
    
    if (!$field || !$value) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE $field = ? AND userID != ?");
        $stmt->bind_param("ss", $value, $current_id);
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
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

$user_id = $_SESSION['user_id'];
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

if (!$user) {
    header("Location: home.php");
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
            // Update all session variables
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
    <title>Edit Profile - PopZone Collectibles</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
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
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #3498db;
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
            height: 40px;
            line-height: 20px;
            box-sizing: border-box;
        }
        
        .btn-primary {
            background: #3498db;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
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
input.valid, textarea.valid, select.valid {
    border: 1px solid green;
}
input.invalid, textarea.invalid, select.invalid {
    border: 1px solid red;
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
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Edit Profile</h1>
        
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
                <a href="view_profile.php" class="btn">View Profile</a>
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
                    <span id="nameFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['userEmail']) ?>" required>
                    <span id="emailFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="M" <?= $user['userGender'] == 'M' ? 'selected' : '' ?>>Male</option>
                        <option value="F" <?= $user['userGender'] == 'F' ? 'selected' : '' ?>>Female</option>
                    </select>
                    <span id="genderFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['userPhoneNum']) ?>" required>
                    <span id="phoneFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($user['userAddress']) ?></textarea>
                    <span id="addressFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="13" value="<?= htmlspecialchars($user['userAge']) ?>" required>
                    <span id="ageFeedback" class="feedback"></span>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="view_profile.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeEditForm();
});

function initializeEditForm() {
    const fields = ['name', 'email', 'gender', 'phone', 'address', 'age'];

    fields.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.addEventListener('input', () => validateField(field));
            input.addEventListener('blur', () => validateField(field));
        }
    });

    document.querySelector('form').addEventListener('submit', function(event) {
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
        default:
            if (value === '') {
                setInvalid(input, feedback, `${field.charAt(0).toUpperCase() + field.slice(1)} cannot be empty.`);
            } else {
                setValid(input, feedback, '');
            }
    }
}

// Add this new function for duplicate checking
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
    xhr.open("POST", "edit_profile.php", true);
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
                    } else if (response.error) {
                        setInvalid(field, feedback, response.error);
                    }
                } catch (e) {
                    console.error("Error parsing JSON response:", e);
                    setInvalid(field, feedback, 'Unexpected response from server.');
                }
            } else {
                console.error("Error: Could not contact the server.");
                setInvalid(field, feedback, 'Unable to verify availability.');
            }
        }
    };

    xhr.send(`validate=true&${fieldType}=${encodeURIComponent(fieldValue)}&current_id=<?= $user_id ?>`);
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

function checkEmailAvailability(email) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "edit_profile.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                var emailInput = document.getElementById("email");
                var feedback = document.getElementById("emailFeedback");

                if (response.status === 'duplicate') {
                    setInvalid(emailInput, feedback, 'Email already in use by another account.');
                } else if (response.status === 'available') {
                    setValid(emailInput, feedback, 'Email is available.');
                }
            } catch (e) {
                console.error("Error parsing response:", e);
            }
        }
    };
    xhr.send(`validate_email=true&email=${encodeURIComponent(email)}&current_id=<?= $user_id ?>`);
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
</html>