<?php
session_start();
require_once 'db.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if an expense ID was passed in the URL
if (isset($_GET['id'])) {
    $expense_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Delete the expense only if it belongs to the logged-in user (security check)
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
}

// Redirect back to the dashboard
header("Location: dashboard.php");
exit();
?>