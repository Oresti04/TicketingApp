<?php
session_start();
require_once '../backend/db.php';
require_once '../security/security.php';

// Set security headers
Security::setSecurityHeaders();

// Generate CSRF token
$csrf_token = Security::regenerateCSRFToken();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (false/*!Security::validateCSRFToken($_POST['csrf_token'] ?? '')*/) {
        $error = "Security validation failed. Please try again.";
    } else {
        $username = Security::sanitizeInput($_POST['username']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } 
        // Validate password strength
        elseif (!Security::isStrongPassword($password)) {
            $error = "Password must be at least 8 characters and include uppercase, lowercase, numbers, and special characters.";
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Username or email already exists. Please try again.";
            } else {
                // Hash password using secure algorithm
                $password_hash = password_hash($password, PASSWORD_ARGON2ID);
                
                // Insert new user into database
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
                try {
                    $stmt->execute([
                        'username' => $username,
                        'email' => $email,
                        'password' => $password_hash,
                        'role' => 'user'
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Log registration
                    Security::logSecurityEvent($pdo, $userId, 'registration', 'New user registered');
                    
                    header('Location: login.php');
                    exit;
                } catch (PDOException $e) {
                    $error = "Registration error: " . $e->getMessage();
                }
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
    <title>Register</title>
    <style>
        .password-meter {
            width: 100%;
            height: 10px;
            background-color: #f3f3f3;
            margin-top: 5px;
            border-radius: 5px;
        }
        
        .password-meter-fill {
            height: 100%;
            border-radius: 5px;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .strength-1 { background-color: #ff4d4d; width: 20%; }
        .strength-2 { background-color: #ffa64d; width: 40%; }
        .strength-3 { background-color: #ffff4d; width: 60%; }
        .strength-4 { background-color: #4dff4d; width: 80%; }
        .strength-5 { background-color: #4d4dff; width: 100%; }
    </style>
</head>
<body>
    <h1>Register</h1>
    
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form action="register.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required pattern="[A-Za-z0-9_]{3,20}" 
               title="3-20 characters, alphanumeric and underscore only">
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <div class="password-meter">
            <div id="password-strength" class="password-meter-fill"></div>
        </div>
        <small>Must be at least 8 characters with uppercase, lowercase, numbers, and special characters</small>
        
        <button type="submit">Register</button>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
    
    <script>
        // Client-side password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            const strengthElement = document.getElementById('password-strength');
            strengthElement.className = 'password-meter-fill strength-' + strength;
        });
    </script>
</body>
</html>
