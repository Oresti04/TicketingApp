<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../frontend/login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Handle ticket creation
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        if (isset($_POST['title'], $_POST['description'], $_POST['priority'], $_POST['visibility'])) {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $priority = trim($_POST['priority']);
            $visibility = trim($_POST['visibility']);
            
            // Only proceed if all fields have values
            if (!empty($title) && !empty($description) && !empty($priority)) {
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
                    
                    // Award points for creating a ticket
                    awardPoints($user_id, 5);
                    
                    header('Location: ../frontend/my_tickets.php');
                    exit;
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
            } else {
                echo "All fields are required.";
            }
        }
    }
    
    // Handle ticket actions that require a ticket_id
    if (isset($_POST['ticket_id'])) {
        $ticket_id = $_POST['ticket_id'];
        
        // First, get the ticket details to check permissions
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
                // Toggle visibility (only by creator)
                case 'toggle_visibility':
                    if ($ticket['created_by'] == $user_id || $role === 'admin') {
                        $new_visibility = ($ticket['visibility'] === 'public') ? 'private' : 'public';
                        $stmt = $pdo->prepare("UPDATE tickets SET visibility = :visibility WHERE id = :id");
                        $stmt->execute(['visibility' => $new_visibility, 'id' => $ticket_id]);
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

// Handle ticket deletion with password verification
if (isset($_POST['action']) && $_POST['action'] === 'delete_ticket' && isset($_POST['ticket_id'], $_POST['admin_password'])) {
    $ticketId = $_POST['ticket_id'];
    $password = $_POST['admin_password'];
    $adminId = $_SESSION['user_id'];
    
    // Verify admin role
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ../frontend/dashboard.php');
        exit;
    }
    
    // Verify password
    if (verifyAdminPassword($adminId, $password)) {
        // Get the ticket details to check if it's being handled by this admin
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = :id");
        $stmt->execute(['id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket && $ticket['currently_handled_by'] == $adminId) {
            // Delete the ticket
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :id");
            $stmt->execute(['id' => $ticketId]);
            
            // Redirect to the admin panel with success message
            header('Location: ../frontend/admin_panel.php?deleted=success');
        } else {
            // Redirect with error
            header('Location: ../frontend/admin_panel.php?error=not_handling');
        }
    } else {
        // Redirect with incorrect password error
        header('Location: ../frontend/admin_panel.php?error=invalid_password');
    }
    exit;
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
