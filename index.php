<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to dashboard if logged in
    header('Location: frontend/dashboard.php');
    exit;
} else {
    // Redirect to login page if not logged in
    header('Location: frontend/login.php');
    exit;
}
?>
