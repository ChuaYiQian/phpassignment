<?php
require_once 'base.php';
if (is_get()) {
    $token = req('token');
    if ($token) {
        $stm = $_db->prepare('
            SELECT userEmail, tokenexpiresat
            FROM user
            WHERE accountactivationtoken = ?
            AND verifystatus = "Unverified"
        ');
        $stm->execute([$token]);
        $user = $stm->fetch(PDO::FETCH_OBJ);
        
        if ($user) {
            // Check if the current time is after the token expiration time
            if (strtotime($user->tokenexpiresat) < time()) {
                // Token has expired
                header('Location: emailAuthenticate.php?status=token_expired');
                exit();
            }

            // Proceed to verify the user
            $stm = $_db->prepare('
            UPDATE user 
            SET verifystatus = "Verified",
                accountactivationtoken = NULL,
                tokenexpiresat = NULL
            WHERE userEmail = ?');
            $stm->execute([$user->userEmail]);
            session_start();
            $_SESSION['email_verified'] = true;
            $_SESSION['email'] = $user->userEmail;
            header('Location: emailAuthenticate.php?status=success');
            exit();
        } else {
            header('Location: emailAuthenticate.php?status=failure');
            exit();
        }
    } else {
        header('Location: emailAuthenticate.php?status=failure');
        exit();
    }
} else {
    echo 'Invalid request method.';
}