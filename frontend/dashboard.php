<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
}
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

  <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="tickets.php">Manage Tickets</a>
  <?php else: ?>
      <a href="create_ticket.php">Create a Ticket</a>
      <a href="tickets.php">View My Tickets</a>
  <?php endif; ?>

  <a href="../backend/logout.php">Logout</a>
</body>
</html>
