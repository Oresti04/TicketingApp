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

// Fetch only tickets created by this user
$stmt = $pdo->prepare("SELECT t.*, u.username AS handler_username
                      FROM tickets t 
                      LEFT JOIN users u ON t.currently_handled_by = u.id
                      WHERE t.created_by = :user_id
                      ORDER BY t.created_at DESC");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>My Tickets</title>
  <style>
    .being-handled {
        color: #f57c00;
        font-weight: bold;
    }
    tr.locked {
        background-color: #fff8e1;
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
  <h1>My Tickets</h1>
  
  <div class="navigation">
    <a href="dashboard.php">Dashboard</a>
    <a href="tickets.php">All Tickets</a>
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
              <th>Created At</th>
              <th>Being Handled By</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($tickets as $ticket): ?>
              <tr class="<?= $ticket['currently_handled_by'] ? 'locked' : '' ?>">
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
                          
                          <!-- Toggle visibility -->
                          <button type="submit" name="action" value="toggle_visibility">
                              Make <?= $ticket['visibility'] == 'public' ? 'Private' : 'Public' ?>
                          </button>
                          
                          <!-- Delete ticket if not being handled -->
                          <?php if (!$ticket['currently_handled_by'] && $ticket['status'] !== 'resolved'): ?>
                              <button type="submit" name="action" value="delete" 
                                      onclick="return confirm('Are you sure you want to delete this ticket?');">
                                  Delete
                              </button>
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
