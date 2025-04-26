<?php
require_once 'base.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
}

// Initialize variables
$errors = [];
$success = false;

// Function to validate reCAPTCHA
function validateCaptcha($recaptchaResponse) {
    $secretKey = '6LdaT04qAAAAAF3iHJS202HUWb6tI4agZjUH5igi'; // Use the secret key from register.php
    $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';

    $response = file_get_contents($verifyURL . '?secret=' . $secretKey . '&response=' . $recaptchaResponse);
    $responseData = json_decode($response);

    return $responseData->success;
}

// Handle AJAX validation requests
if (isset($_POST['validate']) && $_POST['validate'] === 'true') {
    header('Content-Type: application/json');

    $field = isset($_POST['username']) ? 'userName' : (isset($_POST['email']) ? 'userEmail' : null);
    $value = $_POST[$field === 'userName' ? 'username' : 'email'] ?? null;

    if (!$field || !$value) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $result = ['status' => 'available'];

    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE $field = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        
        if ($count > 0) {
            $result['status'] = 'duplicate';
        }
        echo json_encode($result);
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error occurred']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['validate'])) {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = $_POST['gender'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $age = intval($_POST['age']);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Validate inputs
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    if (empty($gender) || !in_array($gender, ['M', 'F'])) $errors[] = "Valid gender is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($address)) $errors[] = "Address is required";
    if ($age < 13) $errors[] = "You must be at least 13 years old";
    
    // Validate reCAPTCHA
    if (empty($recaptchaResponse)) {
        $errors[] = "Please complete the reCAPTCHA verification";
    } else if (!validateCaptcha($recaptchaResponse)) {
        $errors[] = "reCAPTCHA verification failed. Please try again.";
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT userID FROM user WHERE userEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    $stmt->close();

    // Handle profile picture upload
    $profile_pic = 'images/default_profile.png';
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

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $userID = generateUserID($conn, 'C'); // Generate customer ID starting with "C"

        // Prepare and execute insert statement
        $stmt = $conn->prepare("INSERT INTO user (userID, userName, userGender, userEmail, userPhoneNum, userPassword, userAddress, userProfilePicture, userStatus, userRole, userAge) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'customer', ?)");
        $stmt->bind_param("ssssssssi", $userID, $name, $gender, $email, $phone, $hashed_password, $address, $profile_pic, $age);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Create a cart for the new user
            $cartID = 'C' . uniqid();
            $current_date = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("INSERT INTO cart (cartID, userID, createDate, updateDate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $cartID, $userID, $current_date, $current_date);
            $stmt->execute();
        } else {
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
    <title>Sign Up - PopZone Collectibles</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
            position: relative;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="number"],
        input[type="file"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 15px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
        }
        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 3px solid #3498db;
        }
        .feedback {
            color: red;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
        /* Style for form validation */
        .available {
            border: 1px solid green;
        }
        .duplicate {
            border: 1px solid red;
        }
        /* reCAPTCHA container styling */
        .g-recaptcha {
            margin: 15px 0;
        }
        /* Field icon styling similar to register.php */
        .form-group .icon {
            position: absolute;
            left: 10px;
            top: 38px;
            color: #555;
        }
        .form-group .field-with-icon {
            padding-left: 35px;
        }
        .form-group .toggle-password {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
            color: #555;
        }
        .form-group .checkbox {
            margin-top: 10px;
        }
        .form-group .checkbox input {
            margin-right: 5px;
        }
        /* Webcam styles */
        .webcam-container {
            display: none;
            margin-top: 10px;
        }
        #webcam {
            width: 100%;
            max-width: 400px;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #takePhoto {
            margin-top: 10px;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        #captureButton {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <h1>Create Your Account</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <p>Registration successful! You can now <a href="home.php">login</a>.</p>
            </div>
        <?php else: ?>
            <form action="signup.php" method="POST" enctype="multipart/form-data" id="signupForm">
                <div class="form-group">
                    <label>Profile Picture:</label>
                    <img id="profilePicPreview" src="images/default_profile.png" class="profile-pic-preview" alt="Profile Picture Preview">
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" onchange="previewImage(event)">
                    <span id="profile_picFeedback" class="feedback"></span>
                    <button type="button" id="captureButton">Capture from Webcam</button>
                    <div id="webcamContainer" class="webcam-container">
                        <video id="webcam" autoplay></video>
                        <button type="button" id="takePhoto">Take Photo</button>
                        <canvas id="photoCanvas" style="display: none;"></canvas>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <span class="icon"><i class="fa fa-user" aria-hidden="true"></i></span>
                    <input type="text" id="name" name="name" class="field-with-icon" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <span id="nameFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span>
                    <input type="email" id="email" name="email" class="field-with-icon" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <span id="emailFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password (min 8 characters):</label>
                    <span class="icon"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>
                    <input type="password" id="password" name="password" class="field-with-icon" required>
                    <span class="toggle-password" onclick="togglePassword('password')"><i class="fa fa-eye" aria-hidden="true"></i></span>
                    <span id="passwordFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <span class="icon"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" class="field-with-icon" required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')"><i class="fa fa-eye" aria-hidden="true"></i></span>
                    <span id="confirmPasswordFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <span class="icon"><i class="fa fa-venus-mars" aria-hidden="true"></i></span>
                    <select id="gender" name="gender" class="field-with-icon" required>
                        <option value="">Select Gender</option>
                        <option value="M" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                        <option value="F" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <span class="icon"><i class="fa fa-phone" aria-hidden="true"></i></span>
                    <input type="tel" id="phone" name="phone" class="field-with-icon" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <span class="icon"><i class="fa fa-home" aria-hidden="true"></i></span>
                    <textarea id="address" name="address" class="field-with-icon" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <span class="icon"><i class="fa fa-birthday-cake" aria-hidden="true"></i></span>
                    <input type="number" id="age" name="age" class="field-with-icon" min="13" required value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                </div>
                
                <div class="form-group checkbox">
                    <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                    <label for="agreeTerms">I agree to the <a href="#">terms and conditions</a>.</label>
                </div>
                
                <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="6LdaT04qAAAAAHSIocWGPfx69T4vNOzMf4pz3vlZ"></div>
                    <span id="captchaFeedback" class="feedback"></span>
                </div>
                
                <button type="submit">Sign Up</button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                Already have an account? <a href="home.php">Login here</a>
            </p>
        <?php endif; ?>
    </div>

    <script>
        // Image preview functionality
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const preview = document.getElementById('profilePicPreview');
                preview.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        // Password toggle functionality
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            const nameField = document.getElementById('name');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            // Debounce function to limit API calls
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }
            
            // Email validation with debounce
            if (emailField) {
                emailField.addEventListener('input', debounce(function() {
                    if (this.value.length >= 3) {
                        validateField('email', this.value);
                    }
                }, 500));
            }

            // Name validation with debounce
            if (nameField) {
                nameField.addEventListener('input', debounce(function() {
                    if (this.value.length >= 3) {
                        validateField('username', this.value);
                    }
                }, 500));
            }

            // Password validation
            if (passwordField) {
                passwordField.addEventListener('input', function() {
                    const feedback = document.getElementById('passwordFeedback');
                    if (this.value.length < 8) {
                        feedback.textContent = 'Password must be at least 8 characters long.';
                    } else {
                        feedback.textContent = '';
                    }
                    
                    // Update confirm password validation if it has a value
                    if (confirmPasswordField.value) {
                        validatePasswordMatch();
                    }
                });
            }

            // Confirm password validation
            if (confirmPasswordField && passwordField) {
                confirmPasswordField.addEventListener('input', validatePasswordMatch);
            }
            
            // Form submission validation
            const form = document.getElementById('signupForm');
            if (form) {
                form.addEventListener('submit', function(event) {
                    // Check if terms are agreed to
                    const agreeTerms = document.getElementById('agreeTerms');
                    if (!agreeTerms.checked) {
                        event.preventDefault();
                        document.getElementById('captchaFeedback').textContent = 'You must agree to the terms and conditions.';
                        return;
                    }
                    
                    // Check reCAPTCHA
                    const recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        event.preventDefault();
                        document.getElementById('captchaFeedback').textContent = 'Please complete the reCAPTCHA verification.';
                    }
                });
            }
            
            function validatePasswordMatch() {
                const feedback = document.getElementById('confirmPasswordFeedback');
                if (confirmPasswordField.value !== passwordField.value) {
                    feedback.textContent = 'Passwords do not match.';
                } else {
                    feedback.textContent = '';
                }
            }
        });

        function validateField(field, value) {
            if (!value || value.length < 3) return;

            const fieldName = field === 'username' ? 'name' : 'email';
            const fieldElement = document.getElementById(fieldName);
            const feedback = document.getElementById(`${fieldName}Feedback`);
            
            // Show loading state
            feedback.textContent = 'Checking...';

            // Ajax validation for duplicates
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'signup.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        fieldElement.classList.remove('available', 'duplicate');
                        
                        if (response.status === 'duplicate') {
                            fieldElement.classList.add('duplicate');
                            feedback.textContent = `This ${field} is already taken.`;
                            feedback.style.color = 'red';
                        } else if (response.status === 'available') {
                            fieldElement.classList.add('available');
                            feedback.textContent = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is available.`;
                            feedback.style.color = 'green';
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        feedback.textContent = '';
                    }
                }
            };
            xhr.send(`validate=true&${field}=${encodeURIComponent(value)}`);
        }

        // Webcam functionality
        function initializeWebcam() {
            const captureButton = document.getElementById('captureButton');
            const webcamContainer = document.getElementById('webcamContainer');
            const webcam = document.getElementById('webcam');
            const takePhotoButton = document.getElementById('takePhoto');
            const canvas = document.getElementById('photoCanvas');
            
            let stream = null;
            
            captureButton.addEventListener('click', function() {
                if (webcamContainer.style.display === 'none' || webcamContainer.style.display === '') {
                    webcamContainer.style.display = 'block';
                    
                    navigator.mediaDevices.getUserMedia({ video: true })
                        .then(function(mediaStream) {
                            stream = mediaStream;
                            webcam.srcObject = mediaStream;
                        })
                        .catch(function(error) {
                            console.error("Error accessing webcam:", error);
                            alert("Error accessing webcam. Please make sure your camera is connected and you've granted permission.");
                            webcamContainer.style.display = 'none';
                        });
                } else {
                    webcamContainer.style.display = 'none';
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                }
            });
            
            takePhotoButton.addEventListener('click', function() {
                canvas.width = webcam.videoWidth;
                canvas.height = webcam.videoHeight;
                
                const context = canvas.getContext('2d');
                context.drawImage(webcam, 0, 0, canvas.width, canvas.height);
                
                const imageDataUrl = canvas.toDataURL('image/png');
                
                // Update preview
                document.getElementById('profilePicPreview').src = imageDataUrl;
                
                // Convert data URL to Blob and create a File
                const blob = dataURLtoBlob(imageDataUrl);
                const file = new File([blob], "webcam-capture.png", { type: "image/png" });
                
                // Create a FileList-like object
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                
                // Set the files property of the file input
                document.getElementById('profile_pic').files = dataTransfer.files;
                
                // Validate the profile pic
                validateProfilePic();

                // Stop webcam stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                
                // Hide webcam container
                webcamContainer.style.display = 'none';
            });
        }

        // Helper function: Convert Data URL to Blob
        function dataURLtoBlob(dataURL) {
            const parts = dataURL.split(';base64,');
            const contentType = parts[0].split(':')[1];
            const raw = window.atob(parts[1]);
            const rawLength = raw.length;
            const uInt8Array = new Uint8Array(rawLength);
            
            for (let i = 0; i < rawLength; ++i) {
                uInt8Array[i] = raw.charCodeAt(i);
            }
            
            return new Blob([uInt8Array], { type: contentType });
        }

        function validateProfilePic() {
            var input = document.getElementById('profile_pic');
            var feedback = document.getElementById('profile_picFeedback');
            
            if (!input.files || input.files.length === 0) {
                // Optional file, so no validation needed if empty
                return;
            }
            
            var file = input.files[0];
            var validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            var maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!validTypes.includes(file.type)) {
                feedback.textContent = 'Invalid file type. Please upload an image (JPG, PNG, or GIF).';
                return;
            }
            
            if (file.size > maxSize) {
                feedback.textContent = 'File is too large. Please upload an image less than 5MB.';
                return;
            }
            
            feedback.textContent = '';
        }

        // Initialize webcam when DOM is loaded
        initializeWebcam();
    </script>
</body>
</html>