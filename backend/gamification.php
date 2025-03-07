<?php
function awardPoints($userId, $points) {
    global $pdo;

    // Update user's points
    $stmt = $pdo->prepare("UPDATE users SET points = points + :points WHERE id = :id");
    try {
        $stmt->execute(['points' => $points, 'id' => $userId]);
    } catch (PDOException $e) {
        echo "Error awarding points: " . $e->getMessage();
    }
}

// Example usage in ticket resolution:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ticket_id']) && $_POST['action'] === 'resolve') {
    awardPoints($_SESSION['user_id'], 10); // Award 10 points for resolving a ticket.
}
?>
