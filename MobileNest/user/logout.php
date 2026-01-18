<?php
session_start();

// Destroy all session data
$_SESSION = [];

if (ini_get('session.use_cookies') && !headers_sent()) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>