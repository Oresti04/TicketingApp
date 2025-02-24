<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../views/login.php');
        exit;
    }

    $title = $_POST['title'];
    $description = $_POST['description'];
    $priority = $_POST['priority'];
    $created_by = $_SESSION['user_id'];

    // Insert ticket into database
    $stmt = $pdo->prepare("INSERT INTO tickets (title, description, priority, created_by) VALUES (:title, :description, :priority, :created_by)");
    
    try {
        $stmt->execute(['title' => $title, 'description' => $description, 'priority' => $priority, 'created_by' => $created_by]);
        header('Location: ../views/tickets.php');
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
