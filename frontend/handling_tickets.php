<?php
session_start();
require_once '../backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch tickets being handled by this user
$stmt = $pdo->prepare("SELECT t.*, u.username AS creator_username
                      FROM tickets t 
                      JOIN users u ON t.created_by = u.id
                      WHERE t.currently_handled_by = :user_id
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
  <title>Tickets I'm Handling</title>
  <style>
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
  <h1>Tickets I'm Handling</h1>
  
  <div class="navigation">
    <a href="dashboard.php">Dashboard</a>
    <a href="tickets.php">All Tickets</a>
    <a href="my_tickets.php">My Tickets</a>
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
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($tickets as $ticket): ?>
              <tr class="locked-by-me">
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
                      <form action="../backend/ticket.php" method="POST">
                          <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                          
                          <button type="submit" name="action" value="release">
                              Release Ticket
                          </button>
                          
                          <?php if ($ticket['status'] !== 'resolved'): ?>
                              <button type="submit" name="action" value="resolve">
                                  Mark as Resolved
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
