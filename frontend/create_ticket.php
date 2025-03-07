<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
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
      <label for="title">Title:</label><br />
      <input type="text" id="title" name="title" required><br /><br />

      <label for="description">Description:</label><br />
      <textarea id="description" name="description" rows="5" required></textarea><br /><br />

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
