<?php
require_once 'base.php';
session_start();

$email = $_SESSION['email'] ?? '';
$verified = false;

if ($email) {
    $stm = $_db->prepare('
        SELECT verifystatus FROM user
        WHERE email = ?
    ');

    $stm->execute([$email]);
    $result = $stm->fetchColumn();

    if ($result !== false) {
        $verified = (bool)$result;
        if ($verified) {
            $_SESSION['email_verified'] = true;
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['Verified' => $verified]);
