<?php
// Database connection file
$host = 'localhost';
$dbname = 'ticketing_app';
$username = 'root';
$password = 'Orushi04';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Verify admin password for secure operations
 */
function verifyAdminPassword($adminId, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id AND role = 'admin'");
    $stmt->execute(['id' => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        return true;
    }
    return false;
}

?>


