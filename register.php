<?php
require_once 'db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors   = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Username or email is already registered.';
        }
        $stmt->close();
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $email, $hash);
        if ($stmt->execute()) {
            $stmt->close();
            flash('success', 'Account created! You can now log in.');
            header('Location: index.php');
            exit;
        }
        $errors[] = 'Something went wrong. Please try again.';
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Expense Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <h2>Create Account</h2>

        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>

            <button type="submit" class="btn primary block">Register</button>
        </form>

        <p class="center muted small">Already have an account? <a href="index.php">Log in</a></p>
    </div>
</body>
</html>
