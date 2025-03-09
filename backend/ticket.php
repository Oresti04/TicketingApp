<?php
session_start();
require_once 'db.php';
require_once '../security/auth.php';
require_once '../security/security.php';



   // Require authentication
$user = requireAuth();
$user_id = $user['user_id'];
$role = $user['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token for web requests
    if (!isAPIRequest() && !Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        echo "CSRF validation failed. Please try again.";
        exit;
    }
    
    // Handle ticket creation
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        if (isset($_POST['title'], $_POST['description'], $_POST['priority'], $_POST['visibility'])) {
            $title = Security::sanitizeInput($_POST['title']);
            $description = Security::sanitizeInput($_POST['description']);
            $priority = in_array($_POST['priority'], ['low', 'medium', 'high']) ? $_POST['priority'] : 'low';
            $visibility = in_array($_POST['visibility'], ['public', 'private']) ? $_POST['visibility'] : 'private';
            
            // Create ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (title, description, priority, visibility, created_by) 
                                  VALUES (:title, :description, :priority, :visibility, :created_by)");
            
            try {
                $stmt->execute([
                    'title' => $title, 
                    'description' => $description, 
                    'priority' => $priority,
                    'visibility' => $visibility,
                    'created_by' => $user_id
                ]);
                
                // Log ticket creation
                $ticketId = $pdo->lastInsertId();
                Security::logSecurityEvent($pdo, $user_id, 'ticket_create', "Created ticket #$ticketId");
                
                // Award points for creating a ticket
                awardPoints($user_id, 5);
                
                header('Location: ../frontend/tickets.php');
                exit;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
                exit;
            }
        }
    }
    
    // Handle ticket actions that require a ticket_id
    if (isset($_POST['ticket_id'])) {
        $ticket_id = (int)$_POST['ticket_id'];
        
        // Get ticket details for permission checks
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = :id");
        $stmt->execute(['id' => $ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            echo "Ticket not found.";
            exit;
        }
        
        // Process different actions
        if (isset($_POST['action'])) {
            switch($_POST['action']) {
                // Toggle visibility (only by creator or admin)
                case 'toggle_visibility':
                    if ($ticket['created_by'] == $user_id || $role === 'admin') {
                        $new_visibility = ($ticket['visibility'] === 'public') ? 'private' : 'public';
                        $stmt = $pdo->prepare("UPDATE tickets SET visibility = :visibility WHERE id = :id");
                        $stmt->execute(['visibility' => $new_visibility, 'id' => $ticket_id]);
                        
                        Security::logSecurityEvent($pdo, $user_id, 'ticket_update', 
                            "Changed visibility of ticket #$ticket_id to $new_visibility");
                    }
                    break;
                
                // Handle a ticket
                case 'handle':
                    // Can only handle if not already being handled
                    if ($ticket['currently_handled_by'] === null) {
                        $stmt = $pdo->prepare("UPDATE tickets SET currently_handled_by = :user_id, 
                                              handling_started = NOW() WHERE id = :id");
                        $stmt->execute(['user_id' => $user_id, 'id' => $ticket_id]);
                        
                        // Award points for taking initiative
                        awardPoints($user_id, 2);
                    }
                    break;
                
                // Release a ticket
                case 'release':
                    // Can only release if you're handling it
                    if ($ticket['currently_handled_by'] == $user_id) {
                        $stmt = $pdo->prepare("UPDATE tickets SET currently_handled_by = NULL, 
                                              handling_started = NULL WHERE id = :id");
                        $stmt->execute(['id' => $ticket_id]);
                    }
                    break;
                
                // Resolve a ticket
                case 'resolve':
                    // Can only resolve if you're handling it
                    if ($ticket['currently_handled_by'] == $user_id) {
                        $stmt = $pdo->prepare("UPDATE tickets SET status = 'resolved', 
                                              currently_handled_by = NULL, handling_started = NULL WHERE id = :id");
                        $stmt->execute(['id' => $ticket_id]);
                        
                        // Award points for resolving
                        awardPoints($user_id, 10);
                    }
                    break;
                
                // Delete a ticket
                case 'delete':
                    // Can only delete if you created it and it's not being handled
                    if ($ticket['created_by'] == $user_id && $ticket['currently_handled_by'] === null) {
                        $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :id");
                        $stmt->execute(['id' => $ticket_id]);
                    }
                    break;
            }
            
            // Redirect based on the original page
            $referer = $_SERVER['HTTP_REFERER'] ?? '../frontend/tickets.php';
            header("Location: $referer");
            exit;
        }
    }
}



// Function to award points
function awardPoints($userId, $points) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :id");
    try {
        $stmt->execute(['points' => $points, 'id' => $userId]);
    } catch (PDOException $e) {
        // Log error
    }
}
?>
