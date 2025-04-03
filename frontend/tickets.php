<?php
session_start();
require_once '../backend/db.php';
require_once '../security/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch tickets based on role
if ($role === 'admin') {
    // Admins see all tickets
    $stmt = $pdo->prepare("SELECT t.*, u.username AS creator_username, 
                          h.username AS handler_username
                          FROM tickets t 
                          LEFT JOIN users u ON t.created_by = u.id
                          LEFT JOIN users h ON t.currently_handled_by = h.id
                          ORDER BY t.created_at DESC");
} else {
    // Users see public tickets + their own tickets
    $stmt = $pdo->prepare("SELECT t.*, u.username AS creator_username, 
                          h.username AS handler_username
                          FROM tickets t 
                          LEFT JOIN users u ON t.created_by = u.id
                          LEFT JOIN users h ON t.currently_handled_by = h.id
                          WHERE t.visibility = 'public' OR t.created_by = :user_id
                          ORDER BY t.created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
}

$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>All Tickets</title>
  <style>
    .being-handled {
        color: #f57c00;
        font-weight: bold;
    }
    tr.locked {
        background-color: #fff8e1;
    }
    tr.locked-by-me {
        background-color: #e8f5e9;
    }
    .badge {
        display: inline-block;
        padding: 3px 7px;
        border-radius: 10px;
        font-size: 12px;
    }
    .badge-public {
        background: #4CAF50;
        color: white;
    }
    .badge-private {
        background: #F44336;
        color: white;
    }
  </style>
</head>
<body>
  <h1>All Tickets</h1>
  
  <div class="navigation">
    <a href="dashboard.php">Dashboard</a>
    <a href="my_tickets.php">My Tickets</a>
    <a href="handling_tickets.php">Tickets I'm Handling</a>
    <a href="create_ticket.php">Create New Ticket</a>
  </div>
  
  <table border="1">
      <thead>
          <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Description</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Visibility</th>
              <th>Created By</th>
              <th>Created At</th>
              <th>Being Handled By</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($tickets as $ticket): ?>
              <tr class="<?php 
                if ($ticket['currently_handled_by'] !== null) {
                    echo ($ticket['currently_handled_by'] == $_SESSION['user_id']) 
                        ? 'locked-by-me' : 'locked';
                }
              ?>">
                  <td><?= htmlspecialchars($ticket['id']) ?></td>
                  <td><?= htmlspecialchars($ticket['title']) ?></td>
                  <td><?= htmlspecialchars($ticket['description']) ?></td>
                  <td><?= htmlspecialchars($ticket['priority']) ?></td>
                  <td><?= htmlspecialchars($ticket['status']) ?></td>
                  <td>
                      <span class="badge badge-<?= $ticket['visibility'] ?>">
                          <?= htmlspecialchars($ticket['visibility']) ?>
                      </span>
                  </td>
                  <td><?= htmlspecialchars($ticket['creator_username']) ?></td>
                  <td><?= htmlspecialchars($ticket['created_at']) ?></td>
                  <td>
                      <?php if ($ticket['currently_handled_by']): ?>
                          <?= htmlspecialchars($ticket['handler_username']) ?>
                      <?php else: ?>
                          <em>Not being handled</em>
                      <?php endif; ?>
                  </td>
                  <td>
                      <form action="../backend/ticket.php" method="POST">
                          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                          <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                          
                          <?php if ($ticket['created_by'] == $_SESSION['user_id']): ?>
                              <!-- Owner can toggle visibility -->
                              <button type="submit" name="action" value="toggle_visibility">
                                  Make <?= $ticket['visibility'] == 'public' ? 'Private' : 'Public' ?>
                              </button>
                          <?php endif; ?>
                          
                          <?php if ($ticket['currently_handled_by'] === null): ?>
                              <!-- Ticket is not being handled -->
                              <button type="submit" name="action" value="handle">Handle Ticket</button>
                              
                          <?php elseif ($ticket['currently_handled_by'] == $_SESSION['user_id']): ?>
                              <!-- User is handling this ticket -->
                              <button type="submit" name="action" value="release">Release Ticket</button>
                              
                              <?php if ($ticket['status'] !== 'resolved'): ?>
                                  <button type="submit" name="action" value="resolve">Mark as Resolved</button>
                              <?php endif; ?>
                              
                          <?php else: ?>
                              <!-- Someone else is handling this ticket -->
                              <span class="being-handled">In progress</span>
                          <?php endif; ?>
                          
                          <?php if ($role === 'admin' && $ticket['status'] !== 'resolved'): ?>
                              <button type="submit" name="action" value="admin_resolve">Admin Resolve</button>
                          <?php endif; ?>
                      </form>
                  </td>
              </tr>
          <?php endforeach; ?>
      </tbody>
  </table>

  <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
