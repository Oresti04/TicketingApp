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
  <title>Create Ticket</title>
  <style>
    :root {
      --primary-green: #2e7d32;
      --light-green: #4caf50;
      --dark-green: #1b5e20;
      --white: #ffffff;
      --light-gray: #f5f5f5;
      --medium-gray: #e0e0e0;
      --red: #f44336;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: var(--white);
      color: #333;
      margin: 0;
      padding: 20px;
      line-height: 1.6;
      max-width: 800px;
      margin: 0 auto;
    }
    
    h1 {
      color: var(--primary-green);
      text-align: center;
      margin-bottom: 30px;
    }
    
    form {
      background-color: var(--light-gray);
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
      color: var(--primary-green);
    }
    
    input[type="text"],
    textarea,
    select {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--medium-gray);
      border-radius: 4px;
      font-family: inherit;
      font-size: 16px;
      margin-bottom: 20px;
      box-sizing: border-box;
      transition: border-color 0.3s;
    }
    
    input[type="text"]:focus,
    textarea:focus,
    select:focus {
      border-color: var(--primary-green);
      outline: none;
    }
    
    textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    button[type="submit"] {
      background-color: var(--primary-green);
      color: var(--white);
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s;
      width: 100%;
      font-weight: bold;
    }
    
    button[type="submit"]:hover {
      background-color: var(--dark-green);
    }
    
    .back-link {
      display: block;
      width: 200px;
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
    
    .back-link:hover {
      background-color: var(--primary-green);
      color: var(--white);
    }
    
    /* Priority color indicators */
    .priority-indicator {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 8px;
    }
    
    .priority-low { background-color: #4CAF50; }
    .priority-medium { background-color: #FFC107; }
    .priority-high { background-color: #F44336; }
  </style>
</head>
<body>
  <h1>Create a New Ticket</h1>

  <form action="../backend/ticket.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <input type="hidden" name="action" value="create">
    
    <label for="title">Title:</label>
    <input type="text" id="title" name="title" required maxlength="100" placeholder="Enter ticket title...">
    
    <label for="description">Description:</label>
    <textarea id="description" name="description" required maxlength="1000" placeholder="Describe the issue in detail..."></textarea>
    
    <label for="priority">Priority:</label>
    <select id="priority" name="priority">
      <option value="low"><span class="priority-indicator priority-low"></span> Low</option>
      <option value="medium"><span class="priority-indicator priority-medium"></span> Medium</option>
      <option value="high"><span class="priority-indicator priority-high"></span> High</option>
    </select>
    
    <label for="visibility">Visibility:</label>
    <select id="visibility" name="visibility">
      <option value="private">Private (Only you and admins)</option>
      <option value="public">Public (Visible to all users)</option>
    </select>
    
    <button type="submit">Create Ticket</button>
  </form>

  <a href="dashboard.php" class="back-link">Back to Dashboard</a>

  <script>
    // Add color indicators to select options (fallback for browsers that don't support CSS in options)
    document.addEventListener('DOMContentLoaded', function() {
      const prioritySelect = document.getElementById('priority');
      const updatePriorityIndicator = () => {
        const selectedOption = prioritySelect.options[prioritySelect.selectedIndex];
        prioritySelect.style.backgroundImage = `url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><circle cx="6" cy="6" r="6" fill="${getPriorityColor(selectedOption.value)}"/></svg>')`;
        prioritySelect.style.backgroundRepeat = 'no-repeat';
        prioritySelect.style.backgroundPosition = 'right 12px center';
        prioritySelect.style.paddingRight = '30px';
      };
      
      const getPriorityColor = (priority) => {
        switch(priority) {
          case 'low': return '%234CAF50';
          case 'medium': return '%23FFC107';
          case 'high': return '%23F44336';
          default: return '%234CAF50';
        }
      };
      
      prioritySelect.addEventListener('change', updatePriorityIndicator);
      updatePriorityIndicator();
    });
  </script>
</body>
</html>