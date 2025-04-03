<?php
session_start();
require_once '../backend/db.php';
require_once '../security/security.php';
require_once '../security/auth.php';

// Require authentication
$user = requireAuth();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Create Ticket</title>
</head>
<body>
  <h1>Create a New Ticket</h1>

  <form action="../backend/ticket.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="action" value="create">
      
      <label for="title">Title:</label><br />
      <input type="text" id="title" name="title" required maxlength="100"><br /><br />

      <label for="description">Description:</label><br />
      <textarea id="description" name="description" rows="5" required maxlength="1000"></textarea><br /><br />

      <label for="priority">Priority:</label><br />
      <select id="priority" name="priority">
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
      </select><br /><br />
      
      <label for="visibility">Visibility:</label><br />
      <select id="visibility" name="visibility">
          <option value="private">Private</option>
          <option value="public">Public</option>
      </select><br /><br />

      <button type="submit">Create Ticket</button><br /><br />
  </form>

  <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
