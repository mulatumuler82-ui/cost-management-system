<?php
session_start();
require_once 'db.php';

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = '';
$success = '';

// Handle Adding Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $title = trim($_POST['title']);
    $amount = trim($_POST['amount']);
    $category = trim($_POST['category']);
    $expense_date = trim($_POST['expense_date']);

    if (!empty($title) && !empty($amount) && !empty($category) && !empty($expense_date)) {
        if (is_numeric($amount) && $amount > 0) {
            $stmt = $pdo->prepare("INSERT INTO expenses (user_id, title, amount, category, expense_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $title, $amount, $category, $expense_date])) {
                $success = "Expense added successfully!";
            } else {
                $error = "Failed to add expense.";
            }
        } else {
            $error = "Please enter a valid amount.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Handle Deleting Expense
if (isset($_GET['delete'])) {
    $expense_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
    header("Location: dashboard.php");
    exit();
}

// Fetch total spending
$stmtTotal = $pdo->prepare("SELECT SUM(amount) AS total FROM expenses WHERE user_id = ?");
$stmtTotal->execute([$user_id]);
$totalRow = $stmtTotal->fetch();
$totalSpending = $totalRow['total'] ? $totalRow['total'] : 0.00;

// Fetch all expenses for this user
$stmtExpenses = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ? ORDER BY expense_date DESC");
$stmtExpenses->execute([$user_id]);
$expenses = $stmtExpenses->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cost Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">Cost Manager</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?= htmlspecialchars($username) ?></span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Add Expense Form & Summary -->
            <div class="col-md-4">
                <!-- Total Spending Card -->
                <div class="card shadow-sm border-0 rounded-4 p-3 mb-4 bg-white">
                    <h6 class="text-muted text-uppercase fw-semibold mb-1">Total Spending</h6>
                    <h3 class="fw-bold text-primary mb-0">$<?= number_format($totalSpending, 2) ?></h3>
                </div>

                <!-- Add Expense Form -->
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h4 class="fw-bold mb-3">Add New Expense</h4>
                    <form action="dashboard.php" method="POST">
                        <div class="mb-3">
                            <label for="title" class="form-label">Expense Title</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Grocery shopping" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount ($)</label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="0.00" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="" selected disabled>Choose category...</option>
                                <option value="Food">Food</option>
                                <option value="Transport">Transport</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Entertainment">Entertainment</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="expense_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date" required>
                        </div>
                        <button type="submit" name="add_expense" class="btn btn-primary w-100 py-2 fw-semibold">Add Expense</button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Expense History Table -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
                    <h4 class="fw-bold mb-3">Expense History</h4>
                    
                    <?php if (count($expenses) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expenses as $exp): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($exp['title']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($exp['category']) ?></span></td>
                                            <td class="text-danger fw-bold">$<?= number_format($exp['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($exp['expense_date']) ?></td>
                                            <td>
                                                <a href="dashboard.php?delete=<?= $exp['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No expenses recorded yet. Start by adding one using the form!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</body>
</html>