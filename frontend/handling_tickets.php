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
  <title>Tickets I'm Handling</title>
  <style>
    :root {
      --primary-green: #2e7d32;
      --light-green: #4caf50;
      --dark-green: #1b5e20;
      --white: #ffffff;
      --light-gray: #f5f5f5;
      --medium-gray: #e0e0e0;
      --orange: #f57c00;
      --red: #f44336;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--white);
      color: #333;
      margin: 0;
      padding: 20px;
      line-height: 1.6;
    }
    
    h1 {
      color: var(--primary-green);
      text-align: center;
      margin-bottom: 20px;
    }
    
    .navigation {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    
    .navigation a {
      display: inline-block;
      padding: 10px 15px;
      background-color: var(--primary-green);
      color: var(--white);
      text-decoration: none;
      border-radius: 5px;
      transition: background-color 0.3s;
    }
    
    .navigation a:hover {
      background-color: var(--dark-green);
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    th, td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid var(--medium-gray);
    }
    
    th {
      background-color: var(--primary-green);
      color: var(--white);
      font-weight: bold;
    }
    
    tr:hover {
      background-color: var(--light-gray);
    }
    
    tr.locked-by-me {
      background-color: #e8f5e9;
      border-left: 4px solid var(--light-green);
    }
    
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      text-transform: uppercase;
    }
    
    .badge-public {
      background: var(--light-green);
      color: white;
    }
    
    .badge-private {
      background: var(--red);
      color: white;
    }
    
    .actions-form {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    
    button {
      padding: 8px 12px;
      background-color: var(--primary-green);
      color: var(--white);
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 14px;
    }
    
    button:hover {
      background-color: var(--dark-green);
    }
    
    button[value="release"] {
      background-color: var(--orange);
    }
    
    button[value="release"]:hover {
      background-color: #e65100;
    }
    
    .back-link {
      display: block;
      width: 200px;
      margin: 0 auto;
      padding: 10px;
      background-color: transparent;
      color: var(--primary-green);
      border: 2px solid var(--primary-green);
      border-radius: 5px;
      text-align: center;
      text-decoration: none;
      font-weight: bold;
      transition: all 0.3s;
    }
    
    .back-link:hover {
      background-color: var(--primary-green);
      color: var(--white);
    }
    
    /* Priority indicators */
    .priority-high {
      color: var(--red);
      font-weight: bold;
    }
    
    .priority-medium {
      color: var(--orange);
    }
    
    /* Responsive table */
    @media (max-width: 768px) {
      table {
        display: block;
        overflow-x: auto;
      }
      
      .actions-form {
        flex-direction: column;
      }
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
  
  <?php if (empty($tickets)): ?>
    <div style="text-align: center; margin: 40px 0; color: var(--primary-green); font-size: 18px;">
      You're not currently handling any tickets.
    </div>
  <?php else: ?>
    <table>
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
            <td><?= htmlspecialchars(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : '') ?></td>
            <td class="priority-<?= strtolower($ticket['priority']) ?>">
              <?= htmlspecialchars($ticket['priority']) ?>
            </td>
            <td><?= htmlspecialchars($ticket['status']) ?></td>
            <td>
              <span class="badge badge-<?= $ticket['visibility'] ?>">
                <?= htmlspecialchars($ticket['visibility']) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($ticket['creator_username']) ?></td>
            <td><?= date('M j, Y g:i a', strtotime($ticket['created_at'])) ?></td>
            <td>
              <form action="../backend/ticket.php" method="POST" class="actions-form">
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
  <?php endif; ?>

  <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</body>
</html>