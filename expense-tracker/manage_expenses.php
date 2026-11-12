<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid   = current_user_id();
$month = clean_month($_GET['month'] ?? null);
$cat   = $_GET['category'] ?? '';
$q     = trim($_GET['q'] ?? '');
[$from, $to] = month_bounds($month);

// Build the WHERE clause step by step. Values always go through placeholders (?), never into the SQL string.
$sql    = 'SELECT id, title, category, amount, expense_date, note FROM expenses
           WHERE user_id = ? AND expense_date >= ? AND expense_date < ?';
$types  = 'iss';
$params = [$uid, $from, $to];

if (in_array($cat, CATEGORIES, true)) {
    $sql .= ' AND category = ?';
    $types .= 's';
    $params[] = $cat;
} else {
    $cat = '';
}
if ($q !== '') {
    $sql .= ' AND (title LIKE ? OR note LIKE ?)';
    $types .= 'ss';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
$sql .= ' ORDER BY expense_date DESC, id DESC LIMIT 200';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = array_sum(array_column($rows, 'amount'));
$backUrl = 'manage_expenses.php?' . http_build_query(array_filter(['month' => $month, 'category' => $cat, 'q' => $q]));

$pageTitle = 'Manage Expenses';
$active = 'manage';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <h1 class="h3 mb-0">Manage Expenses</h1>
  <a class="btn btn-primary" href="add_expense.php"><i class="bi bi-plus-lg"></i> Add expense</a>
</div>

<form class="card card-body mb-3" method="get">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-3">
      <label class="form-label small mb-1" for="month">Month</label>
      <input class="form-control" type="month" id="month" name="month" value="<?= e($month) ?>">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label small mb-1" for="category">Category</label>
      <select class="form-select" id="category" name="category">
        <option value="">All</option>
        <?php foreach (CATEGORIES as $c): ?>
          <option <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-md-4">
      <label class="form-label small mb-1" for="q">Search</label>
      <input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="title or note">
    </div>
    <div class="col-12 col-md-2 d-grid">
      <button class="btn btn-outline-primary">Filter</button>
    </div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>Date</th><th>Title</th><th>Category</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No expenses found for this filter.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="text-nowrap"><?= e($r['expense_date']) ?></td>
            <td>
              <?= e($r['title']) ?>
              <?php if ($r['note']): ?><div class="small text-muted"><?= e($r['note']) ?></div><?php endif; ?>
            </td>
            <td><span class="badge text-bg-secondary"><?= e($r['category']) ?></span></td>
            <td class="text-end amount"><?= money($r['amount']) ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-secondary" href="edit_expense.php?id=<?= (int)$r['id'] ?>">
                <i class="bi bi-pencil"></i><span class="visually-hidden">Edit</span>
              </a>
              <form class="d-inline js-confirm" method="post" action="delete_expense.php"
                    data-message="Delete this expense? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                <button class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash"></i><span class="visually-hidden">Delete</span>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if ($rows): ?>
        <tfoot class="table-light">
          <tr><th colspan="3">Total (<?= count($rows) ?> items)</th><th class="text-end amount"><?= money($total) ?></th><th></th></tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
