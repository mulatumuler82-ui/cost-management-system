<?php
require_once 'db.php';
require_login();

$uid = (int)$_SESSION['user_id'];

// ---------- Period ranges ----------
$today = date('Y-m-d');
$ranges = [
    'day'   => [$today, $today, 'Today'],
    'week'  => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week')), 'This Week'],
    'month' => [date('Y-m-01'), date('Y-m-t'), 'This Month'],
    'year'  => [date('Y-01-01'), date('Y-12-31'), 'This Year'],
    'all'   => ['1970-01-01', '2999-12-31', 'All Time'],
];

$period = $_GET['period'] ?? 'month';
if (!isset($ranges[$period])) {
    $period = 'month';
}

function sums($conn, $uid, $from, $to) {
    $stmt = $conn->prepare("SELECT
            COALESCE(SUM(CASE WHEN type='income'  THEN amount END),0) AS income,
            COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) AS expense
        FROM transactions WHERE user_id = ? AND trans_date BETWEEN ? AND ?");
    $stmt->bind_param('iss', $uid, $from, $to);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['income' => (float)$r['income'], 'expense' => (float)$r['expense'], 'balance' => (float)$r['income'] - (float)$r['expense']];
}

$summary = [];
foreach ($ranges as $key => [$from, $to, $label]) {
    $summary[$key] = sums($conn, $uid, $from, $to);
}

[$from, $to, $periodLabel] = $ranges[$period];

// ---------- Transactions in selected period ----------
$stmt = $conn->prepare('SELECT id, type, amount, category, note, trans_date
    FROM transactions WHERE user_id = ? AND trans_date BETWEEN ? AND ?
    ORDER BY trans_date DESC, id DESC LIMIT 300');
$stmt->bind_param('iss', $uid, $from, $to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---------- Expense by category ----------
$stmt = $conn->prepare("SELECT category, SUM(amount) AS total
    FROM transactions WHERE user_id = ? AND type='expense' AND trans_date BETWEEN ? AND ?
    GROUP BY category ORDER BY total DESC");
$stmt->bind_param('iss', $uid, $from, $to);
$stmt->execute();
$cats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$catTotal = array_sum(array_column($cats, 'total'));

// ---------- Last 7 days chart data ----------
$stmt = $conn->prepare("SELECT trans_date, SUM(amount) AS total FROM transactions
    WHERE user_id = ? AND type='expense' AND trans_date >= DATE_SUB(?, INTERVAL 6 DAY)
    GROUP BY trans_date");
$stmt->bind_param('is', $uid, $today);
$stmt->execute();
$byDay = [];
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $byDay[$r['trans_date']] = (float)$r['total'];
}
$stmt->close();
$last7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $last7[] = ['label' => date('D', strtotime($d)), 'total' => $byDay[$d] ?? 0];
}
$max7 = max(1, max(array_column($last7, 'total')));

$success = flash('success');
$error   = flash('error');
$token   = csrf_token();
$sel     = $summary[$period];
$categories = ['Food', 'Transport', 'Books & School', 'Clothes', 'Health', 'Entertainment', 'Airtime & Internet', 'Other'];
$incomeCats = ['Allowance', 'Family Support', 'Salary', 'Gift', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Expense Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">💰 Expense Tracker</div>
    <div class="user">
        Hi, <strong><?= e($_SESSION['username']) ?></strong>
        <a href="logout.php" class="btn small">Logout</a>
    </div>
</header>

<main class="container">
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <!-- Quick overview cards -->
    <section class="grid four">
        <?php foreach (['day', 'week', 'month', 'year'] as $k): ?>
            <a class="card stat <?= $period === $k ? 'active' : '' ?>" href="?period=<?= $k ?>">
                <div class="muted small"><?= e($ranges[$k][2]) ?></div>
                <div class="big <?= $summary[$k]['balance'] >= 0 ? 'pos' : 'neg' ?>"><?= money($summary[$k]['balance']) ?></div>
                <div class="small"><span class="pos">+<?= number_format($summary[$k]['income'], 2) ?></span> /
                    <span class="neg">-<?= number_format($summary[$k]['expense'], 2) ?></span></div>
            </a>
        <?php endforeach; ?>
    </section>

    <section class="grid two">
        <!-- Add form -->
        <div class="card">
            <h3>Add Record</h3>
            <form method="POST" action="add_expense.php" id="addForm">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">

                <div class="toggle">
                    <label><input type="radio" name="type" value="expense" checked> Expense</label>
                    <label><input type="radio" name="type" value="income"> Income</label>
                </div>

                <label for="amount">Amount (<?= CURRENCY ?>)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>

                <label for="category">Category</label>
                <select id="category" name="category" required></select>

                <label for="trans_date">Date</label>
                <input type="date" id="trans_date" name="trans_date" value="<?= e($today) ?>" required>

                <label for="note">Note (optional)</label>
                <input type="text" id="note" name="note" maxlength="255" placeholder="e.g. lunch at cafeteria">

                <button type="submit" class="btn primary block">Save</button>
            </form>
        </div>

        <!-- Charts / breakdown -->
        <div class="card">
            <h3>Spending – Last 7 Days</h3>
            <div class="bars">
                <?php foreach ($last7 as $b): ?>
                    <div class="bar-col" title="<?= e(money($b['total'])) ?>">
                        <div class="bar" style="height: <?= round($b['total'] / $max7 * 100) ?>%"></div>
                        <div class="small muted"><?= e($b['label']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 style="margin-top:22px">Expenses by Category – <?= e($periodLabel) ?></h3>
            <?php if (!$cats): ?>
                <p class="muted">No expenses in this period.</p>
            <?php else: foreach ($cats as $c): $pct = $catTotal > 0 ? $c['total'] / $catTotal * 100 : 0; ?>
                <div class="cat-row">
                    <div class="cat-head"><span><?= e($c['category']) ?></span><span><?= money($c['total']) ?> (<?= round($pct) ?>%)</span></div>
                    <div class="meter"><div style="width: <?= round($pct) ?>%"></div></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </section>

    <!-- Records -->
    <section class="card">
        <div class="row-between">
            <h3><?= e($periodLabel) ?> – Records</h3>
            <div class="tabs">
                <?php foreach ($ranges as $k => $r): ?>
                    <a href="?period=<?= $k ?>" class="<?= $period === $k ? 'on' : '' ?>"><?= e($k === 'day' ? 'Day' : ($k === 'all' ? 'All' : ucfirst($k))) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="grid three small-gap">
            <div class="mini"><span class="muted small">Income</span><div class="pos"><?= money($sel['income']) ?></div></div>
            <div class="mini"><span class="muted small">Expense</span><div class="neg"><?= money($sel['expense']) ?></div></div>
            <div class="mini"><span class="muted small">Balance</span><div class="<?= $sel['balance'] >= 0 ? 'pos' : 'neg' ?>"><?= money($sel['balance']) ?></div></div>
        </div>

        <?php if (!$rows): ?>
            <p class="muted">No records yet for this period.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Note</th><th class="right">Amount</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e($r['trans_date']) ?></td>
                            <td><span class="badge <?= e($r['type']) ?>"><?= e($r['type']) ?></span></td>
                            <td><?= e($r['category']) ?></td>
                            <td><?= e($r['note']) ?></td>
                            <td class="right <?= $r['type'] === 'income' ? 'pos' : 'neg' ?>">
                                <?= $r['type'] === 'income' ? '+' : '-' ?><?= number_format($r['amount'], 2) ?>
                            </td>
                            <td class="right">
                                <form method="POST" action="delete_expense.php" onsubmit="return confirm('Delete this record?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <input type="hidden" name="period" value="<?= e($period) ?>">
                                    <button class="btn small danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
    const expenseCats = <?= json_encode($categories) ?>;
    const incomeCats  = <?= json_encode($incomeCats) ?>;
    const select = document.getElementById('category');

    function fillCategories() {
        const type = document.querySelector('input[name="type"]:checked').value;
        const list = type === 'income' ? incomeCats : expenseCats;
        select.innerHTML = list.map(c => `<option value="${c}">${c}</option>`).join('');
    }
    document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', fillCategories));
    fillCategories();
</script>
</body>
</html>
