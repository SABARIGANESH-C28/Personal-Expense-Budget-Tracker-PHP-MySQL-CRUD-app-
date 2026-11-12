<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid   = current_user_id();
$month = clean_month($_GET['month'] ?? null);
[$from, $to] = month_bounds($month);

// 1) Category totals for the chosen month -> doughnut chart
$stmt = $conn->prepare(
    'SELECT category, SUM(amount) AS total FROM expenses
     WHERE user_id = ? AND expense_date >= ? AND expense_date < ?
     GROUP BY category ORDER BY total DESC'
);
$stmt->bind_param('iss', $uid, $from, $to);
$stmt->execute();
$cats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2) Last 6 months of income vs expenses -> bar chart
$labels = $incomeData = $expenseData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime(date('Y-m-01') . " -$i month"));
    [$f, $t] = month_bounds($m);
    $labels[]      = date('M Y', strtotime($m . '-01'));
    $incomeData[]  = sum_between($conn, 'incomes',  'income_date',  $uid, $f, $t);
    $expenseData[] = sum_between($conn, 'expenses', 'expense_date', $uid, $f, $t);
}

$total = array_sum(array_column($cats, 'total'));

// json_encode with these flags is safe to print inside a <script> tag (no </script> breakouts).
$flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  var catLabels   = ' . json_encode(array_column($cats, 'category'), $flags) . ';
  var catTotals   = ' . json_encode(array_map(fn($c) => (float)$c['total'], $cats), $flags) . ';
  var monthLabels = ' . json_encode($labels, $flags) . ';
  var incomeData  = ' . json_encode($incomeData, $flags) . ';
  var expenseData = ' . json_encode($expenseData, $flags) . ';
</script>
<script src="assets/js/reports.js"></script>';

$pageTitle = 'Reports';
$active = 'reports';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Reports</h1>
  <form method="get" class="d-flex gap-2">
    <input class="form-control" type="month" name="month" value="<?= e($month) ?>" aria-label="Month">
    <button class="btn btn-outline-primary">Go</button>
  </form>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header bg-white fw-semibold">Spending by category (<?= e(date('F Y', strtotime($from))) ?>)</div>
      <div class="card-body">
        <?php if (!$cats): ?>
          <p class="text-muted">No expenses in this month.</p>
        <?php else: ?>
          <div class="chart-box"><canvas id="categoryChart"></canvas></div>
          <p class="text-center mt-2 mb-0 fw-semibold">Total: <?= money($total) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header bg-white fw-semibold">Income vs expenses (last 6 months)</div>
      <div class="card-body"><div class="chart-box"><canvas id="trendChart"></canvas></div></div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
