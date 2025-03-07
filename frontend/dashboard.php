<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['role'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Dashboard</title>
</head>
<body>
  <h1>Welcome to the Ticketing App!</h1>

  <div class="card-container">
      <div class="card">
          <h2>Tickets</h2>
          <a href="tickets.php">View All Tickets</a>
          <a href="my_tickets.php">My Tickets</a>
          <a href="handling_tickets.php">Tickets I'm Handling</a>
      </div>
      
      <div class="card">
          <h2>Actions</h2>
          <a href="create_ticket.php">Create a Ticket</a>
          <?php if ($role === 'admin'): ?>
              <a href="admin.php">Admin Panel</a>
          <?php endif; ?>
      </div>
      
      <div class="card">
          <h2>Gamification</h2>
          <a href="leaderboard.php">View Leaderboard</a>
      </div>
  </div>

  <a href="../backend/logout.php">Logout</a>
</body>
</html>
