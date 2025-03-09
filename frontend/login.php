<?php
session_start();
require_once '../backend/db.php';
require_once '../security/security.php';
require_once '../security/jwt.php';

// Set security headers
Security::setSecurityHeaders();

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security validation failed. Please try again.";
    } else {
        $username = Security::sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // Check for rate limiting
        if (!Security::checkLoginAttempts($pdo, $username)) {
            $error = "Too many failed attempts. Please try again later.";
        } else {
            // Fetch user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Generate JWT token
                $payload = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ];
                $token = JWT::generate($payload);
                
                // Store token in secure HTTP-only cookie
                setcookie('auth_token', $token, time() + 3600, '/', '', true, true);
                
                // Record successful login
                Security::recordLoginAttempt($pdo, $username, true);
                Security::logSecurityEvent($pdo, $user['id'], 'login', 'Successful login');
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                header('Location: waiting_room.php');
                exit;
            } else {
                // Failed login
                Security::recordLoginAttempt($pdo, $username, false);
                Security::logSecurityEvent($pdo, null, 'failed_login', "Failed login attempt for user: $username");
                $error = "Invalid username or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <label for="username">Username:</label>
        <input type="text" name="username" required>
        
        <label for="password">Password:</label>
        <input type="password" name="password" required>
        
        <button type="submit">Login</button>
    </form>
    
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>
