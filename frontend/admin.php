<?php
session_start();
require_once '../backend/db.php';

// Verify the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Initialize search parameters for advanced search
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$username = isset($_GET['username']) ? $_GET['username'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

// Fetch ticket statistics
$stmt = $pdo->query("SELECT 
                    COUNT(*) as total_tickets,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets
                    FROM tickets");
$ticketStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user statistics
$stmt = $pdo->query("SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
                    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users
                    FROM users");
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch most active users (most points)
$stmt = $pdo->query("SELECT username, points FROM users ORDER BY points DESC LIMIT 5");
$activeUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent tickets
$stmt = $pdo->query("SELECT t.id, t.title, t.status, t.created_at, u.username 
                     FROM tickets t 
                     JOIN users u ON t.created_by = u.id 
                     ORDER BY t.created_at DESC LIMIT 5");
$recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle user role changes if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    if ($newRole === 'admin' || $newRole === 'user') {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $newRole, 'id' => $userId]);
    }
}

// Get all users for management
$stmt = $pdo->query("SELECT id, username, email, role, points FROM users ORDER BY username");
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Advanced search results for tickets
$ticketResults = [];
if (isset($_GET['search']) && $_GET['search'] === 'advanced') {
    // Build the query
    $query = "SELECT t.*, u.username, h.username AS handler_username 
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
    $ticketResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Admin Panel</title>
  <style>
    .dashboard {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }
    .card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      padding: 20px;
      margin-bottom: 20px;
    }
    .stats {
      display: flex;
      justify-content: space-between;
    }
    .stat-item {
      text-align: center;
      padding: 10px;
    }
    .stat-number {
      font-size: 24px;
      font-weight: bold;
    }
    .stat-label {
      color: #666;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .tabs {
      margin-bottom: 20px;
    }
    .tab {
      display: inline-block;
      padding: 10px 15px;
      cursor: pointer;
      background: #f1f1f1;
      border-radius: 4px 4px 0 0;
    }
    .tab.active {
      background: #4CAF50;
      color: white;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
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
  <h1>Admin Panel</h1>
  
  <div class="tabs">
    <div class="tab <?= !isset($_GET['search']) ? 'active' : '' ?>" onclick="switchTab('dashboard')">Dashboard</div>
    <div class="tab" onclick="switchTab('users')">User Management</div>
    <div class="tab <?= isset($_GET['search']) && $_GET['search'] === 'advanced' ? 'active' : '' ?>" onclick="switchTab('tickets')">Ticket Management</div>
  </div>
  
  <div id="dashboard" class="tab-content <?= !isset($_GET['search']) ? 'active' : '' ?>">
    <div class="dashboard">
      <div class="card">
        <h2>Ticket Statistics</h2>
        <div class="stats">
          <div class="stat-item">
            <div class="stat-number"><?= $ticketStats['total_tickets'] ?></div>
            <div class="stat-label">Total</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?= $ticketStats['open_tickets'] ?></div>
            <div class="stat-label">Open</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?= $ticketStats['in_progress_tickets'] ?></div>
            <div class="stat-label">In Progress</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?= $ticketStats['resolved_tickets'] ?></div>
            <div class="stat-label">Resolved</div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <h2>User Statistics</h2>
        <div class="stats">
          <div class="stat-item">
            <div class="stat-number"><?= $userStats['total_users'] ?></div>
            <div class="stat-label">Total Users</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?= $userStats['admin_users'] ?></div>
            <div class="stat-label">Admins</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?= $userStats['regular_users'] ?></div>
            <div class="stat-label">Regular Users</div>
          </div>
        </div>
      </div>
      
      <div class="card">
        <h2>Most Active Users</h2>
        <table>
          <tr>
            <th>Username</th>
            <th>Points</th>
          </tr>
          <?php foreach ($activeUsers as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['points']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      
      <div class="card">
        <h2>Recent Tickets</h2>
        <table>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Created By</th>
          </tr>
          <?php foreach ($recentTickets as $ticket): ?>
          <tr>
            <td><?= htmlspecialchars($ticket['id']) ?></td>
            <td><?= htmlspecialchars($ticket['title']) ?></td>
            <td><?= htmlspecialchars($ticket['status']) ?></td>
            <td><?= htmlspecialchars($ticket['username']) ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
  
  <div id="users" class="tab-content">
    <div class="card">
      <h2>User Management</h2>
      <table>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Points</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($allUsers as $user): ?>
        <tr>
          <td><?= htmlspecialchars($user['id']) ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td><?= htmlspecialchars($user['points']) ?></td>
          <td>
            <form method="post" style="display: inline;">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <input type="hidden" name="new_role" value="<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>">
              <button type="submit"><?= $user['role'] === 'admin' ? 'Demote to User' : 'Promote to Admin' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  
  <div id="tickets" class="tab-content <?= isset($_GET['search']) && $_GET['search'] === 'advanced' ? 'active' : '' ?>">
    <div class="card">
      <h2>Advanced Ticket Search</h2>
      <div class="search-form">
        <form method="get">
          <input type="hidden" name="search" value="advanced">
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
            <button type="reset" onclick="window.location='admin_panel.php'">Reset Filters</button>
          </div>
        </form>
      </div>
      
      <?php if (isset($_GET['search']) && $_GET['search'] === 'advanced'): ?>
        <div class="results-count">
          Found <?= count($ticketResults) ?> ticket(s)
        </div>
        
        <table border="1">
          <thead>
            <tr>
              <th>ID</th>
              <th>Title</th>
              <th>Description</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Created By</th>
              <th>Being Handled By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($ticketResults) > 0): ?>
              <?php foreach ($ticketResults as $ticket): ?>
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
                  <td><?= htmlspecialchars($ticket['username']) ?></td>
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
                      <?php else: ?>
                        <!-- Someone else is handling this ticket -->
                        <span class="being-handled">In progress by other user</span>
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
                <td colspan="8" style="text-align: center;">No tickets found matching your criteria</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
      
      <h3>Bulk Actions</h3>
      <a href="../backend/ticket.php?action=bulk_resolve" onclick="return confirm('Are you sure you want to resolve all open tickets?');">
        Resolve All Open Tickets
      </a>
    </div>
  </div>

  <div style="margin-top: 20px;">
    <a href="dashboard.php">Back to Dashboard</a>
  </div>
  
  <script>
    function switchTab(tabId) {
      // Hide all tab contents
      const tabContents = document.querySelectorAll('.tab-content');
      tabContents.forEach(content => {
        content.classList.remove('active');
      });
      
      // Remove active class from all tabs
      const tabs = document.querySelectorAll('.tab');
      tabs.forEach(tab => {
        tab.classList.remove('active');
      });
      
      // Show the selected tab content
      document.getElementById(tabId).classList.add('active');
      
      // Add active class to the clicked tab
      event.currentTarget.classList.add('active');
    }
    
    // Set active tab from URL if search parameter exists
    window.onload = function() {
      <?php if(isset($_GET['search']) && $_GET['search'] === 'advanced'): ?>
        switchTab('tickets');
      <?php endif; ?>
    };
  </script>
</body>
</html>
