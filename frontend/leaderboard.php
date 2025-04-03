<?php
require_once '../backend/db.php';
session_start();

// Current user
$currentUser = $_SESSION['username'];

// Fetch all users ordered by points
$stmt = $pdo->prepare("SELECT username, points FROM users ORDER BY points DESC");
$stmt->execute();
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate user's rank
$userRank = array_search($currentUser, array_column($allUsers, 'username'));
$totalUsers = count($allUsers);

// Subset around current user ("My Stats")
$start = max($userRank - 10, 0);
$end = min($start + 20, $totalUsers);
$userSubset = array_slice($allUsers, $start, $end - $start);

// Top Ten Users
$topTenUsers = array_slice($allUsers, 0, 10);

// Percentile calculation (correctly structured)
$percentiles = array_fill(0, 10, ['total_points' => 0, 'user_count' => 0]);

// Populate percentile data
foreach ($allUsers as $index => $user) {
    $percentileIndex = floor(($index / $totalUsers) * 10);
    if ($percentileIndex == 10) $percentileIndex = 9;

    $percentiles[$percentileIndex]['total_points'] += $user['points'];
    $percentiles[$percentileIndex]['user_count']++;
}

// Calculate averages explicitly
foreach ($percentiles as $idx => &$data) {
    $data['average_points'] = $data['user_count'] 
        ? round($data['total_points'] / $data['user_count'], 2) : 0;
}
unset($data);  // break reference explicitly

// User's percentile index
$userPercentileIndex = floor(($userRank / $totalUsers) * 10);
$userPercentileIndex = min(max($userPercentileIndex, 0), 9);

// Find maximum average for scaling bars
$maxAvgPoints = max(array_column($percentiles, 'average_points'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .tabs {
      display: flex;
      justify-content: center;
      margin-bottom: 1rem;
    }
    .tabs button {
      padding: 0.5rem 1rem;
      cursor: pointer;
      border: none;
      background: #ccc;
      border-radius: 4px 4px 0 0;
      margin-right: 2px;
    }
    .tabs button.active {
      background: #0077cc;
      color: white;
    }
    .leaderboard-container {max-width: 700px;margin: auto;}
    .leaderboard-bar {display: flex; align-items: center;margin: 0.5rem 0;}
    .username {width: 120px;font-weight: bold;}
    .bar {flex-grow: 1;height: 20px;background-color: #0077cc;border-radius: 4px;position: relative;}
    .bar.user-highlight {background-color: #00cc77;}
    .points {position: absolute;right: 10px;color: white;font-weight: bold;top: 50%;transform: translateY(-50%);}
  </style>
  <title>Leaderboard</title>
</head>
<body>

<div class="leaderboard-container">
    <div class="tabs">
        <button class="active" onclick="showTab(event, 'mystats')">My Stats</button>
        <button onclick="showTab(event, 'topten')">Top Ten</button>
        <button onclick="showTab(event, 'global')">Global</button>
    </div>

  <!-- My Stats -->
  <div id="mystats" class="tab-content">
    <?php foreach ($userSubset as $user): ?>
      <div class="leaderboard-bar">
        <div class="username"><?= htmlspecialchars($user['username']) ?></div>
        <div class="bar<?= $user['username'] === $currentUser ? ' user-highlight' : '' ?>" style="width: <?= ($user['points'] / $allUsers[0]['points'])*100 ?>%;">
          <div class="points"><?= htmlspecialchars($user['points']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Top Ten -->
  <div id="topten" class="tab-content" style="display:none;">
    <?php foreach ($topTenUsers as $user): ?>
      <div class="leaderboard-bar">
        <div class="username"><?= htmlspecialchars($user['username']) ?></div>
        <div class="bar" style="width: <?= ($user['points'] / $allUsers[0]['points'])*100 ?>%;">
          <div class="points"><?= htmlspecialchars($user['points']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Global Percentiles -->
    <!-- Global Percentiles -->
    <div id="global" class="tab-content" style="display:none;">
    <?php foreach ($percentiles as $idx => $percentile): ?>
      <?php 
        $barWidth = pow(($percentile['average_points'] / $maxAvgPoints), 2) * 100;
        $highlight = ((int)$idx === (int)$userPercentileIndex);
      ?>
      <div class="leaderboard-bar">
        <div class="username"><?= ($idx*10) ?>-<?= (($idx+1)*10) ?>%</div>
        <div class="bar<?= $highlight ? ' user-highlight' : '' ?>" style="width: <?= $barWidth ?>%;">
          <div class="points">
            <?= $highlight ? htmlspecialchars($currentUser) : $percentile['average_points'] . ' pts (avg)' ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <a href="dashboard.php">Back to Dashboard</a>
</div>

<script>
function showTab(evt, tab) {
    // Hide all tab content
    document.querySelectorAll('.tab-content').forEach(div => div.style.display = 'none');

    // Remove 'active' class from all buttons
    document.querySelectorAll('.tabs button').forEach(btn => btn.classList.remove('active'));

    // Show selected tab content
    document.getElementById(tab).style.display = 'block';

    // Mark clicked button as active
    evt.currentTarget.classList.add('active');
}
</script>

</body>
</html>
