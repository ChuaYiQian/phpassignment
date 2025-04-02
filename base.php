<?php
// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

// Global PDO object
$_db = new PDO('mysql:dbname=assignment', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
]);

$host = "localhost";   
$user = "root";        
$password = ""; 
$database = "assignment"; // Changed to match your database name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function generateUserID($conn, $prefix = 'C') {
    $sql = "SELECT MAX(CAST(SUBSTRING(userID, 2) AS UNSIGNED)) as max_num
            FROM user
            WHERE userID LIKE '$prefix%'";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $next_num = $row['max_num'] + 1;
    } else {
        $next_num = 1;
    }
    return $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT); // Format ID
}


// Create default admin if not exists
function createDefaultAdmin($conn) {
    $checkAdmin = $conn->query("SELECT userID FROM user WHERE userID = 'A0001'");
    if ($checkAdmin->num_rows == 0) {
        $hashed_password = password_hash('admin', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO user (userID, userName, userGender, userEmail, userPhoneNum, userPassword, userAddress, userProfilePicture, userStatus, userRole, userAge) 
                      VALUES ('A0001', 'Admin', 'M', 'admin@popzone.com', '0000000000', '$hashed_password', 'PopZone HQ', 'uploads/default_admin.png', 'active', 'admin', 30)");
    }
}

createDefaultAdmin($conn);
?>
