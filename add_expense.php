<?php
require_once 'db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!csrf_check($_POST['csrf_token'] ?? '')) {
    flash('error', 'Invalid request. Please try again.');
    header('Location: dashboard.php');
    exit;
}

$type     = $_POST['type'] ?? '';
$amount   = $_POST['amount'] ?? '';
$category = trim($_POST['category'] ?? '');
$note     = trim($_POST['note'] ?? '');
$date     = $_POST['trans_date'] ?? date('Y-m-d');

$d = DateTime::createFromFormat('Y-m-d', $date);
$validDate = $d && $d->format('Y-m-d') === $date;

if (!in_array($type, ['income', 'expense'], true)) {
    flash('error', 'Choose income or expense.');
} elseif (!is_numeric($amount) || (float)$amount <= 0 || (float)$amount > 9999999999) {
    flash('error', 'Enter a valid amount greater than 0.');
} elseif ($category === '' || mb_strlen($category) > 50) {
    flash('error', 'Choose a category (max 50 characters).');
} elseif (!$validDate) {
    flash('error', 'Invalid date.');
} elseif (mb_strlen($note) > 255) {
    flash('error', 'Note is too long (max 255 characters).');
} else {
    $uid = (int)$_SESSION['user_id'];
    $amt = round((float)$amount, 2);
    $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, category, note, trans_date) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isdsss', $uid, $type, $amt, $category, $note, $date);
    if ($stmt->execute()) {
        flash('success', ucfirst($type) . ' of ' . money($amt) . ' added.');
    } else {
        flash('error', 'Could not save. Please try again.');
    }
    $stmt->close();
}

header('Location: dashboard.php');
exit;
