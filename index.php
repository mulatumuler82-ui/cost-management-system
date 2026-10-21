<?php
require_once 'db.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } elseif ($login === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Incorrect username/email or password.';
    }
}

$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Expense Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <h2>💰 Expense Tracker</h2>
        <p class="muted center">Log in to manage your money</p>

        <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <label for="login">Username or Email</label>
            <input type="text" id="login" name="login" value="<?= e($login) ?>" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn primary block">Log In</button>
        </form>

        <p class="center muted small">No account? <a href="register.php">Register</a></p>
    </div>
</body>
</html>
