<?php
session_start();
include '../base.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home.php");
    temp('error', 'Access denied. Please log in to continue.');
    exit;
} else if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') {
    header("Location: ../dashboard.php");
    temp('error', 'Admins are not allowed to access this page.');
    exit();
} else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../home.php");
    temp('error', 'Invalid access method.');
    exit;
}

$userID = $_SESSION['user_id'];
$cartID = $_POST['cartID'] ?? '';
$selectedItems = explode(',', $_POST['selectedItems'] ?? '');

if (empty($selectedItems) || $selectedItems[0] === "") {
    $_SESSION['error'] = 'No items selected for checkout';
    header("Location: /cart/cart.php");
    exit;
}

try {
    $_db->beginTransaction();

    
    $orderStmt = $_db->query("SELECT COUNT(*) FROM `Order`");
    $orderCount = $orderStmt->fetchColumn();
    $orderID = 'O' . str_pad($orderCount + 1, 4, '0', STR_PAD_LEFT);

    
    $orderStmt = $_db->prepare("
        INSERT INTO `Order` 
        (orderID, userID, orderTotal, orderDate, orderStatus)
        VALUES (?, ?, 0, CURDATE(), 'pending')
    ");
    $orderStmt->execute([$orderID, $userID]);
    
    $placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
    $cartItemQuery = $_db->prepare("
        SELECT ci.productID, ci.cartQuantity, p.productPrice
        FROM cartItem ci
        JOIN product p ON ci.productID = p.productID
        WHERE ci.cartID = ? AND ci.productID IN ($placeholders)
    ");
    $params = array_merge([$cartID], $selectedItems);
    $cartItemQuery->execute($params);
    $cartItems = $cartItemQuery->fetchAll(PDO::FETCH_ASSOC);

    
    $reviewBaseStmt = $_db->query("SELECT COUNT(*) FROM Review");
    $reviewBaseCount = $reviewBaseStmt->fetchColumn();
    
    $totalPrice = 0;
    foreach ($cartItems as $index => $item) {
        
        $reviewID = 'RV' . str_pad($reviewBaseCount + $index + 1, 4, '0', STR_PAD_LEFT);

        
        $reviewStmt = $_db->prepare("
            INSERT INTO Review 
            (reviewID, reviewDescription, starQuantity, reviewDate)
            VALUES (?, '', 5, CURDATE())
        ");
        $reviewStmt->execute([$reviewID]);

        
        $orderInfoStmt = $_db->prepare("
            INSERT INTO orderInformation 
            (productID, reviewID, orderID, orderQuantity)
            VALUES (?, ?, ?, ?)
        ");
        $orderInfoStmt->execute([
            $item['productID'],
            $reviewID,
            $orderID,
            $item['cartQuantity']
        ]);

        $totalPrice += $item['productPrice'] * $item['cartQuantity'];
    }

    
    $updateOrderStmt = $_db->prepare("
        UPDATE `Order` 
        SET orderTotal = ? 
        WHERE orderID = ?
    ");
    $updateOrderStmt->execute([$totalPrice, $orderID]);

    
    $deleteStmt = $_db->prepare("
        DELETE FROM cartItem 
        WHERE cartID = ? AND productID IN ($placeholders)
    ");
    $deleteStmt->execute($params);

    $_db->commit();
    header("Location: /payment.php?orderID=" . urlencode($orderID));
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    error_log("Checkout Error: " . $e->getMessage());
    $_SESSION['error'] = 'Checkout failed: ' . $e->getMessage();
    header("Location: /cart/cart.php");
    exit;
}