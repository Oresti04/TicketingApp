<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Create chat messages table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        username VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS online_users (
        user_id INT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Handle different actions
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'getMessages':
        $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE id > :last_id ORDER BY created_at ASC");
        $stmt->execute(['last_id' => $last_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['messages' => $messages]);
        break;
        
    case 'sendMessage':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
            $message = trim($_POST['message']);
            
            $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, username, message) 
                                  VALUES (:user_id, :username, :message)");
            $success = $stmt->execute([
                'user_id' => $user_id,
                'username' => $username,
                'message' => $message
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No message provided']);
        }
        break;
        
    case 'getOnlineUsers':
        // Remove inactive users (more than 2 minutes)
        $stmt = $pdo->prepare("DELETE FROM online_users WHERE last_active < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        $stmt->execute();
        
        // Get current online users
        $stmt = $pdo->prepare("SELECT user_id, username FROM online_users ORDER BY username");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['users' => $users]);
        break;
        
    case 'updateStatus':
        // Update or insert user's online status
        $stmt = $pdo->prepare("INSERT INTO online_users (user_id, username, last_active) 
                              VALUES (:user_id, :username, NOW())
                              ON DUPLICATE KEY UPDATE last_active = NOW()");
        $stmt->execute(['user_id' => $user_id, 'username' => $username]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        break;
        
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
}
?>
