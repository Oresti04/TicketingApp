<?php
session_start();
session_destroy(); // Destroy all session data
header('Location: ../frontend/login.php'); // Redirect to login page
exit;
?>
