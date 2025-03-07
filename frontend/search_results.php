<?php
session_start();
require_once '../backend/db.php';

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Only admins should access this page
if ($role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Initialize search parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$username = isset($_GET['username']) ? $_GET['username'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Build the search query
$query = "SELECT t.*, u.username AS creator_username, h.username AS handler_username 
          FROM tickets t 
          LEFT JOIN users u ON t.created_by = u.id
          LEFT JOIN users h ON t.currently_handled_by = h.id
          WHERE 1=1";
$params = [];

// Add filters based on search criteria
if (!empty($status)) {
    $query .= " AND t.status = :status";
    $params['status'] = $status;
}

if (!empty($priority)) {
    $query .= " AND t.priority = :priority";
    $params['priority'] = $priority;
}

if (!empty($username)) {
    $query .= " AND u.username LIKE :username";
    $params['username'] = "%$username%";
}

if (!empty($date_from)) {
    $query .= " AND t.created_at >= :date_from";
    $params['date_from'] = $date_from . " 00:00:00";
}

if (!empty($date_to)) {
    $query .= " AND t.created_at <= :date_to";
    $params['date_to'] = $date_to . " 23:59:59";
}

if (!empty($keyword)) {
    $query .= " AND (t.title LIKE :keyword OR t.description LIKE :keyword)";
    $params['keyword'] = "%$keyword%";
}

// Order by most recent tickets first
$query .= " ORDER BY t.created_at DESC";

// Execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Search Results</title>
  <style>
    .search-form {
      background: #f9f9f9;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 5px;
    }
    .search-form .form-row {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-bottom: 15px;
    }
    .search-form label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .search-form input, .search-form select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    .search-form button {
      padding: 10px 15px;
      background: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .search-form button:hover {
      background: #45a049;
    }
    .results-count {
      margin-bottom: 15px;
      font-weight: bold;
    }
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
  </style>
</head>
<body>
  <h1>Search Results</h1>

  <!-- Search Form -->
  <div class="search-form">
    <form method="get">
      <div class="form-row">
        <div>
          <label for="status">Status:</label>
          <select name="status" id="status">
            <option value="">All Statuses</option>
            <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
          </select>
        </div>
        
        <div>
          <label for="priority">Priority:</label>
          <select name="priority" id="priority">
            <option value="">All Priorities</option>
            <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
            <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
            <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
          </select>
        </div>
        
        <div>
          <label for="username">Created By:</label>
          <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" placeholder="Enter username...">
        </div>
      </div>
      
      <div class="form-row">
        <div>
          <label for="date_from">From Date:</label>
          <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>">
        </div>
        
        <div>
          <label for="date_to">To Date:</label>
          <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>">
        </div>
        
        <div>
          <label for="keyword">Keyword:</label>
          <input type="text" name="keyword" id="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Search in title and description...">
        </div>
      </div>
      
      <div>
        <button type="submit">Search</button>
        <button type="reset" onclick="window.location='search_results.php'">Reset Filters</button>
      </div>
    </form>
  </div>
  
  <!-- Results Count -->
  <div class="results-count">
    Found <?= count($tickets) ?> ticket(s)
  </div>
  
  <!-- Results Table -->
  <table border="1">
    <thead>
      <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Description</th>
        <th>Priority</th>
        <th>Status</th>
        <th>Created By</th>
        <th>Created At</th>
        <th>Being Handled By</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($tickets) > 0): ?>
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
                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                
                <?php if ($ticket['currently_handled_by'] === null): ?>
                  <!-- Ticket is not being handled -->
                  <button type="submit" name="action" value="handle">Handle Ticket</button>
                  
                <?php elseif ($ticket['currently_handled_by'] == $_SESSION['user_id']): ?>
                  <!-- Admin is handling this ticket -->
                  <button type="submit" name="action" value="release">Release Ticket</button>
                  
                  <?php if ($ticket['status'] !== 'resolved'): ?>
                    <button type="submit" name="action" value="resolve">Mark as Resolved</button>
                  <?php endif; ?>
                  
                  <!-- Delete button that opens modal -->
                  <button type="button" onclick="openDeleteModal(<?= $ticket['id'] ?>, '<?= htmlspecialchars($ticket['title']) ?>')" class="delete-btn">Delete Ticket</button>
                  
                <?php else: ?>
                  <!-- Someone else is handling this ticket -->
                  <span class="being-handled">In progress by another user</span>
                <?php endif; ?>
                
                <!-- Admin can always resolve regardless of who's handling -->
                <?php if ($ticket['status'] !== 'resolved'): ?>
                  <button type="submit" name="action" value="admin_resolve">Admin Resolve</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="9" style="text-align: center;">No tickets found matching your criteria</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Navigation Links -->
  <div style="margin-top: 20px;">
    <a href="admin_panel.php">Back to Admin Panel</a> | 
    <a href="dashboard.php">Back to Dashboard</a>
  </div>
  
  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeDeleteModal()">&times;</span>
      <h2>Delete Ticket</h2>
      <p>You are about to delete ticket: <span id="ticketTitle"></span></p>
      <p>This action cannot be undone. For security, please enter your password to confirm:</p>
      
      <form id="deleteTicketForm" action="../backend/ticket.php" method="POST">
        <input type="hidden" name="action" value="delete_ticket">
        <input type="hidden" name="ticket_id" id="deleteTicketId">
        <input type="hidden" name="return_to" value="search_results.php">
        
        <div class="form-group">
          <label for="admin_password">Your Password:</label>
          <input type="password" name="admin_password" id="admin_password" required>
        </div>
        
        <div class="form-actions">
          <button type="button" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="delete-confirm">Delete Permanently</button>
        </div>
      </form>
    </div>
  </div>
  
  <style>
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    
    .modal-content {
      background-color: #fefefe;
      margin: 15% auto;
      padding: 20px;
      border-radius: 8px;
      width: 50%;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    
    .close:hover {
      color: black;
    }
    
    .delete-btn {
      background-color: #f44336;
      color: white;
    }
    
    .delete-confirm {
      background-color: #f44336;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-actions {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
    }
  </style>
  
  <script>
    // Modal functionality
    function openDeleteModal(ticketId, ticketTitle) {
      document.getElementById('deleteModal').style.display = 'block';
      document.getElementById('ticketTitle').textContent = ticketTitle;
      document.getElementById('deleteTicketId').value = ticketId;
    }
    
    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
      document.getElementById('admin_password').value = '';
    }
    
    // Close modal if clicking outside of it
    window.onclick = function(event) {
      var modal = document.getElementById('deleteModal');
      if (event.target == modal) {
        closeDeleteModal();
      }
    }
  </script>
</body>
</html>
