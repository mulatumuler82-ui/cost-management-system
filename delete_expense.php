<?php
require_once 'db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request.');
    header('Location: dashboard.php');
    exit;
}

$id  = (int)($_POST['id'] ?? 0);
$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    flash('success', 'Record deleted.');
} else {
    flash('error', 'Record not found.');
}
$stmt->close();

$back = 'dashboard.php';
if (!empty($_POST['period']) && in_array($_POST['period'], ['day', 'week', 'month', 'year', 'all'], true)) {
    $back .= '?period=' . $_POST['period'];
}
header('Location: ' . $back);
exit;
