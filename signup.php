<?php
require_once 'base.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$errors = [];
$success = false;

// reCAPTCHA validation function
function validateRecaptcha($response) {
    $secretKey = '6LdaT04qAAAAAF3iHJS202HUWb6tI4agZjUH5igi'; // Use your own secret key
    $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';

    $responseData = file_get_contents($verifyURL . '?secret=' . $secretKey . '&response=' . $response);
    $responseData = json_decode($responseData);

    return $responseData->success;
}

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
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

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
    if (!validateRecaptcha($recaptcha_response)) {
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
        input[type="file"],
        input[type="number"],
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
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
            <form id="signupForm" action="signup.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Profile Picture:</label>
                    <img id="profilePicPreview" src="images/default_profile.png" class="profile-pic-preview" alt="Profile Picture Preview">
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
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
                    <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <span id="nameFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                        <option value="M" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                        <option value="F" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                    </select>
                    <span id="genderFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    <span id="phoneFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea id="address" name="address" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    <span id="addressFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="13" required value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                    <span id="ageFeedback" class="feedback"></span>
                </div>
                
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="agreeTerms" name="agreeTerms" required>
                        <label for="agreeTerms">I agree to the <a href="#">terms and conditions</a>.</label>
                        <span id="agreeTermsFeedback" class="feedback"></span>
                    </div>
                </div>

                <div class="g-recaptcha" data-sitekey="6LdaT04qAAAAAHSIocWGPfx69T4vNOzMf4pz3vlZ"></div>
                <span id="recaptchaFeedback" class="feedback"></span>
                
                <button type="submit">Sign Up</button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                Already have an account? <a href="home.php">Login here</a>
            </p>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        initializeSignupForm();
        initializeWebcam();
    });

    function initializeSignupForm() {
        const fields = ['name', 'email', 'password', 'confirm_password', 'phone', 'address', 'age', 'gender', 'profile_pic'];

        fields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                if (field === 'profile_pic') {
                    input.addEventListener('change', validateProfilePic);
                } else {
                    input.addEventListener('input', () => validateField(field));
                    input.addEventListener('blur', () => validateField(field));
                }
            }
        });

        document.getElementById('signupForm').addEventListener('submit', function(event) {
            let hasErrors = false;
            
            fields.forEach(field => {
                validateField(field);
                const input = document.getElementById(field);
                if (input && input.classList.contains('invalid')) {
                    hasErrors = true;
                }
            });

            // Check recaptcha
            const recaptchaResponse = grecaptcha.getResponse();
            if (!recaptchaResponse) {
                const feedback = document.getElementById('recaptchaFeedback');
                feedback.textContent = 'Please complete the reCAPTCHA verification.';
                feedback.className = 'feedback invalid';
                hasErrors = true;
            }

            // Check terms agreement
            const agreeTerms = document.getElementById('agreeTerms');
            if (!agreeTerms.checked) {
                const feedback = document.getElementById('agreeTermsFeedback');
                feedback.textContent = 'You must agree to the terms and conditions.';
                feedback.className = 'feedback invalid';
                hasErrors = true;
            }

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
                // Custom message for confirm_password field
                if (field === 'confirm_password') {
                    setInvalid(input, feedback, 'Confirm password cannot be empty.');
                } else {
                    setInvalid(input, feedback, `${field.charAt(0).toUpperCase() + field.slice(1)} cannot be empty.`);
                }
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
            setInvalid(document.getElementById("age"), ageFeedback, 'You must be at least 13 years old to register.');
        } else {
            setValid(document.getElementById("age"), ageFeedback, 'Valid age.');
        }
    }

    function validateProfilePic() {
        var input = document.getElementById('profile_pic');
        var feedback = document.getElementById('profile_picFeedback');
        
        if (!input.files || input.files.length === 0) {
            setValid(input, feedback, ''); // Optional file, so no validation needed if empty
            return;
        }
        
        var file = input.files[0];
        var validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        var maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!validTypes.includes(file.type)) {
            setInvalid(input, feedback, 'Invalid file type. Please upload an image (JPG, PNG, or GIF).');
            return;
        }
        
        if (file.size > maxSize) {
            setInvalid(input, feedback, 'File is too large. Please upload an image less than 5MB.');
            return;
        }
        
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePicPreview').src = e.target.result;
            setValid(input, feedback, 'Valid image selected.');
        };
        reader.readAsDataURL(file);
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
        xhr.open("POST", "signup.php", true);
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

    // Webcam functionality - Fixed version
function initializeWebcam() {
    const captureButton = document.getElementById('captureButton');
    const webcamContainer = document.getElementById('webcamContainer');
    const webcam = document.getElementById('webcam');
    const takePhotoButton = document.getElementById('takePhoto');
    const canvas = document.getElementById('photoCanvas');
    const profilePicPreview = document.getElementById('profilePicPreview');
    const profilePicInput = document.getElementById('profile_pic');
    
    let stream = null;
    
    // Toggle webcam on/off
    captureButton.addEventListener('click', async function() {
        try {
            if (webcamContainer.style.display === 'block') {
                // Webcam is currently showing - turn it off
                webcamContainer.style.display = 'none';
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                return;
            }
            
            // Show webcam container
            webcamContainer.style.display = 'block';
            
            // Get webcam stream with ideal constraints
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user' // Front camera
                },
                audio: false
            });
            
            // Set video source and play
            webcam.srcObject = stream;
            webcam.play();
            
            // Change button text
            captureButton.textContent = 'Close Webcam';
        } catch (error) {
            console.error("Error accessing webcam:", error);
            alert("Could not access the webcam. Please ensure you've granted camera permissions and that no other application is using the camera.");
            webcamContainer.style.display = 'none';
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }
    });
    
    // Capture photo from webcam
    takePhotoButton.addEventListener('click', function() {
        if (!stream) {
            alert("Webcam is not active. Please start the webcam first.");
            return;
        }
        
        try {
            // Set canvas dimensions to match video
            const videoWidth = webcam.videoWidth;
            const videoHeight = webcam.videoHeight;
            canvas.width = videoWidth;
            canvas.height = videoHeight;
            
            // Draw current video frame to canvas
            const context = canvas.getContext('2d');
            context.drawImage(webcam, 0, 0, videoWidth, videoHeight);
            
            // Convert canvas to data URL (JPEG with 90% quality)
            const imageDataUrl = canvas.toDataURL('image/jpeg', 0.9);
            
            // Update preview
            profilePicPreview.src = imageDataUrl;
            
            // Convert data URL to Blob
            const blob = dataURLtoBlob(imageDataUrl);
            
            // Create a File from the Blob
            const file = new File([blob], "webcam-capture.jpg", { 
                type: "image/jpeg",
                lastModified: Date.now()
            });
            
            // Create a FileList-like object and add the file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            
            // Set the files property of the file input
            profilePicInput.files = dataTransfer.files;
            
            // Validate the profile pic
            validateProfilePic();
            
            // Stop webcam stream
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            
            // Hide webcam container
            webcamContainer.style.display = 'none';
            
            // Reset button text
            captureButton.textContent = 'Capture from Webcam';
        } catch (error) {
            console.error("Error capturing photo:", error);
            alert("Error capturing photo. Please try again.");
        }
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

// Initialize webcam when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeWebcam();
});
    
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

    document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    initializeSignupForm();
    initializeWebcam();
});
</script>

</body>
</html>