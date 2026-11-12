<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid = current_user_id();
[$from, $to] = month_bounds(date('Y-m'));

$income  = sum_between($conn, 'incomes',  'income_date',  $uid, $from, $to);
$expense = sum_between($conn, 'expenses', 'expense_date', $uid, $from, $to);
$balance = $income - $expense;

// Spending by category this month (GROUP BY + SUM)
$stmt = $conn->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date >= ? AND expense_date < ?
     GROUP BY category ORDER BY total DESC'
);
$stmt->bind_param('iss', $uid, $from, $to);
$stmt->execute();
$byCategory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 5 most recent expenses (ORDER BY + LIMIT)
$stmt = $conn->prepare('SELECT title, category, amount, expense_date FROM expenses WHERE user_id = ? ORDER BY expense_date DESC, id DESC LIMIT 5');
$stmt->bind_param('i', $uid);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Budgets that are at 80%+ this month
$stmt = $conn->prepare(
    'SELECT b.category, b.monthly_limit,
            COALESCE((SELECT SUM(e.amount) FROM expenses e
                      WHERE e.user_id = b.user_id AND e.category = b.category
                        AND e.expense_date >= ? AND e.expense_date < ?), 0) AS spent
     FROM budgets b WHERE b.user_id = ?'
);
$stmt->bind_param('ssi', $from, $to, $uid);
$stmt->execute();
$alerts = array_filter(
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
    fn($b) => $b['monthly_limit'] > 0 && $b['spent'] / $b['monthly_limit'] >= 0.8
);
$stmt->close();

$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Hello, <?= e($_SESSION['user_name']) ?></h1>
<p class="text-muted"><?= e(date('F Y')) ?> overview</p>

<?php foreach ($alerts as $a):
    $over = $a['spent'] >= $a['monthly_limit']; ?>
  <div class="alert alert-<?= $over ? 'danger' : 'warning' ?> py-2">
    <i class="bi bi-exclamation-triangle"></i>
    <strong><?= e($a['category']) ?></strong>:
    <?= $over ? 'over budget' : 'near the limit' ?> (<?= money($a['spent']) ?> of <?= money($a['monthly_limit']) ?>).
  </div>
<?php endforeach; ?>

<div class="row g-3 mb-3">
  <div class="col-12 col-md-4">
    <div class="card stat-card income card-body">
      <div class="text-muted small">Income</div>
      <div class="stat-value text-success"><?= money($income) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card stat-card expense card-body">
      <div class="text-muted small">Expenses</div>
      <div class="stat-value text-danger"><?= money($expense) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card stat-card balance card-body">
      <div class="text-muted small">Balance</div>
      <div class="stat-value <?= $balance < 0 ? 'text-danger' : '' ?>"><?= money($balance) ?></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white fw-semibold">Spending by category</div>
      <div class="card-body">
        <?php if (!$byCategory): ?>
          <p class="text-muted mb-0">No expenses this month yet. <a href="add_expense.php">Add one</a>.</p>
        <?php endif; ?>
        <?php foreach ($byCategory as $c):
            $pct = $expense > 0 ? ($c['total'] / $expense) * 100 : 0; ?>
          <div class="mb-2">
            <div class="d-flex justify-content-between small">
              <span><?= e($c['category']) ?></span><span class="amount"><?= money($c['total']) ?></span>
            </div>
            <div class="progress" style="height: 8px;"><div class="progress-bar" style="width: <?= round($pct) ?>%"></div></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header bg-white d-flex justify-content-between">
        <span class="fw-semibold">Recent expenses</span>
        <a href="manage_expenses.php" class="small">View all</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <tbody>
            <?php if (!$recent): ?>
              <tr><td class="text-muted p-3">Nothing here yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $r): ?>
              <tr>
                <td class="ps-3">
                  <?= e($r['title']) ?>
                  <div class="small text-muted"><?= e($r['expense_date']) ?> · <?= e($r['category']) ?></div>
                </td>
                <td class="text-end pe-3 amount"><?= money($r['amount']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
