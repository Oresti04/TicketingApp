<?php
session_start();
require_once 'db.php';
require_once '../security/security.php';

// Log the logout event if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    Security::logSecurityEvent($pdo, $user_id, 'logout', 'User logged out');
}

// Clear the session
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear auth token cookie
setcookie('auth_token', '', time() - 3600, '/', '', true, true);

// Redirect to login page
header('Location: ../frontend/login.php');
exit;
?>
