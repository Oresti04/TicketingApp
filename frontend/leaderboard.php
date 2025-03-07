<?php
require_once '../backend/db.php';

// Fetch top users by points
$stmt = $pdo->prepare("SELECT username, points FROM users ORDER BY points DESC LIMIT 10");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <title>Leaderboard</title>
</head>
<body>
  <h1>Leaderboard</h1>
  <table border="1">
      <thead>
          <tr>
              <th>Rank</th>
              <th>Username</th>
              <th>Points</th>
          </tr>
      </thead>
      <tbody>
          <?php foreach ($users as $index => $user): ?>
              <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($user['username']) ?></td>
                  <td><?= htmlspecialchars($user['points']) ?></td>
              </tr>
          <?php endforeach; ?>
      </tbody>
  </table>

  <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
