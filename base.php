<?php
// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');


// Global PDO object
$_db = new PDO('mysql:host=localhost;port=3306;dbname=assignment', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
]);

$host = "localhost:3306";   
$user = "root";        
$password = ""; 
$database = "assignment";

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


// Is unique?
function is_unique($value, $table, $field) {
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() == 0;
}

// Is exists?
function is_exists($value, $table, $field) {
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
    $stm->execute([$value]);
    return $stm->fetchColumn() > 0;
}


function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

// Is POST request?
function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

// Obtain REQUEST (GET and POST) parameter
function req($key, $value = null) {
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}

// Set or get temporary session variable
function temp($key, $value = null) {
    if ($value !== null) {
        $_SESSION["temp_$key"] = $value;
    }
    else {
        $value = $_SESSION["temp_$key"] ?? null;
        unset($_SESSION["temp_$key"]);
        return $value;
    }
}

// Obtain uploaded file --> cast to object
function get_file($key) {
    $f = $_FILES[$key] ?? null;
    
    if ($f && $f['error'] == 0) {
        return (object)$f;
    }

    return null;
}

// Crop, resize and save photo
function save_photo($f, $folder, $width = 200, $height = 200) {
    $photo = uniqid() . '.jpg';
    
    require_once 'lib/SimpleImage.php';
    $img = new SimpleImage();
    $img->fromFile($f->tmp_name)
        ->thumbnail($width, $height)
        ->toFile("$folder/$photo", 'image/jpeg');

    return $photo;
}

// Is money?
function is_money($value) {
    return preg_match('/^\-?\d+(\.\d{1,2})?$/', $value);
}

function encode($value) {
    return htmlentities($value);
}

function html_text($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='text' id='$key' name='$key' value='$value' $attr>";
}

function html_hidden($key) {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='hidden' name='$key' value='$value'>";
}

// Generate <input type='number'>
function html_number($key, $min = '', $max = '', $step = '', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='number' id='$key' name='$key' value='$value'
                 min='$min' max='$max' step='$step' $attr>";
}

function html_select($key, $items, $default = '- Select One -', $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<select id='$key' name='$key' $attr>";
    if ($default !== null) {
        echo "<option value=''>$default</option>";
    }
    foreach ($items as $id => $text) {
        $state = $id == $value ? 'selected' : '';
        echo "<option value='$id' $state>$text</option>";
    }
    echo '</select>';
}

function html_file($key, $accept = '', $attr = '') {
    echo "<input type='file' id='$key' name='$key' accept='$accept' $attr>";
}

$_err = [];

// Generate <span class='err'>
function err($key) {
    global $_err;
    if ($_err[$key] ?? false) {
        echo "<span class='err'>$_err[$key]</span>";
    }
    else {
        echo '<span></span>';
    }
}

function html_search($key, $attr = '') {
    $value = encode($GLOBALS[$key] ?? '');
    echo "<input type='search' id='$key' name='$key' value='$value' $attr>";
}

function table_headers($fields, $sort, $dir, $href = '', $sortable = []) {
    foreach ($fields as $k => $v) {
        if (!in_array($k, $sortable)) {
            echo "<th>$v</th>"; // plain header, not sortable
            continue;
        }
        $d = 'asc'; // Default direction
        $c = '';    // Default class
        
        if($k == $sort){
            $d = $dir == 'asc' ? 'desc' : 'asc';
            $c = $dir;
        }

        echo "<th><a href='?sort=$k&dir=$d&$href' class='$c'>$v</a></th>";
    }
}

function get_mail() {
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';

    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->SMTPAuth = true;
    $m->Host = 'smtp.gmail.com';
    $m->Port = 587;
    $m->Username = 'liawcv1@gmail.com';
    $m->Password = 'pztq znli gpjg tooe';
    $m->CharSet = 'utf-8';
    $m->setFrom($m->Username, '😺 Admin');

    return $m;
}
?>