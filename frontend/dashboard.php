<?php
session_start();
require_once '../security/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get CSRF token
$csrf_token = Security::generateCSRFToken();

$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <style>
    :root {
      --primary-green: #2e7d32;
      --light-green: #4caf50;
      --dark-green: #1b5e20;
      --white: #ffffff;
      --light-gray: #f5f5f5;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--white);
      color: #333;
      margin: 0;
      padding: 20px;
    }
    
    h1 {
      color: var(--primary-green);
      text-align: center;
      margin-bottom: 30px;
    }
    
    .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      max-width: 1200px;
      margin: 0 auto 30px;
    }
    
    .card {
      background-color: var(--light-gray);
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-5px);
    }
    
    .card h2 {
      color: var(--primary-green);
      border-bottom: 2px solid var(--light-green);
      padding-bottom: 10px;
      margin-top: 0;
    }
    
    .card a {
      display: block;
      background-color: var(--primary-green);
      color: var(--white);
      text-decoration: none;
      padding: 12px;
      margin: 10px 0;
      border-radius: 5px;
      text-align: center;
      transition: background-color 0.3s;
    }
    
    .card a:hover {
      background-color: var(--dark-green);
    }
    
    .logout-btn {
      display: block;
      width: 150px;
      margin: 30px auto 0;
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
    
    .logout-btn:hover {
      background-color: var(--primary-green);
      color: var(--white);
    }
  </style>
</head>
<body>
  <h1>Welcome to the Ticketing App!</h1>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'csrf_error'): ?>
    <div class="error-message">
        Security validation failed. Please try again.
    </div>
  <?php endif; ?>

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

  <form action="../backend/logout.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <button type="submit" class="logout-btn">Logout</button>
  </form>
</body>
</html>