<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {                                  // CREATE / UPDATE in one query
        $category = $_POST['category'] ?? '';
        $limit    = trim($_POST['monthly_limit'] ?? '');

        if (!in_array($category, CATEGORIES, true)) $errors[] = 'Choose a valid category.';
        if (!valid_amount($limit))                  $errors[] = 'Enter a limit greater than 0.';

        if (!$errors) {
            $limitNum = (float)$limit;
            // UNIQUE(user_id, category) means a second save for the same category updates the old row.
            $stmt = $conn->prepare(
                'INSERT INTO budgets (user_id, category, monthly_limit) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE monthly_limit = VALUES(monthly_limit)'
            );
            $stmt->bind_param('isd', $uid, $category, $limitNum);   // i s d
            $stmt->execute();
            $stmt->close();
            flash('success', "Budget for $category saved.");
            redirect('budgets.php');
        }
    } elseif ($action === 'delete') {                          // DELETE
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM budgets WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Budget removed.');
        redirect('budgets.php');
    }
}

// READ: each budget joined with this month's spending for that category.
[$from, $to] = month_bounds(date('Y-m'));
$stmt = $conn->prepare(
    'SELECT b.id, b.category, b.monthly_limit,
            COALESCE((SELECT SUM(e.amount) FROM expenses e
                      WHERE e.user_id = b.user_id AND e.category = b.category
                        AND e.expense_date >= ? AND e.expense_date < ?), 0) AS spent
     FROM budgets b
     WHERE b.user_id = ?
     ORDER BY b.category'
);
$stmt->bind_param('ssi', $from, $to, $uid);
$stmt->execute();
$budgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Budgets';
$active = 'budgets';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-1">Budgets</h1>
<p class="text-muted">Monthly limits per category. Progress below is for <?= e(date('F Y')) ?>.</p>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-body">
      <h2 class="h6">Set a budget</h2>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <div class="mb-2">
          <label class="form-label" for="category">Category</label>
          <select class="form-select" id="category" name="category">
            <?php foreach (CATEGORIES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" for="monthly_limit">Monthly limit (₹)</label>
          <input class="form-control" type="number" step="0.01" min="0.01" id="monthly_limit" name="monthly_limit" required>
        </div>
        <button class="btn btn-primary w-100">Save budget</button>
        <div class="form-text">Saving an existing category updates its limit.</div>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if (!$budgets): ?>
      <div class="alert alert-info">No budgets yet. Set one on the left to start tracking.</div>
    <?php endif; ?>
    <?php foreach ($budgets as $b):
        $pct = $b['monthly_limit'] > 0 ? ($b['spent'] / $b['monthly_limit']) * 100 : 0;
        $bar = $pct >= 100 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
    ?>
      <div class="card card-body mb-3">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <strong><?= e($b['category']) ?></strong>
            <div class="small text-muted"><?= money($b['spent']) ?> of <?= money($b['monthly_limit']) ?></div>
          </div>
          <form class="js-confirm" method="post" data-message="Remove the <?= e($b['category']) ?> budget?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i><span class="visually-hidden">Remove</span></button>
          </form>
        </div>
        <div class="progress mt-2" role="progressbar" aria-valuenow="<?= (int)min($pct, 100) ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar <?= $bar ?>" style="width: <?= min($pct, 100) ?>%"><?= round($pct) ?>%</div>
        </div>
        <?php if ($pct >= 100): ?>
          <div class="small text-danger mt-1">Over budget by <?= money($b['spent'] - $b['monthly_limit']) ?>.</div>
        <?php elseif ($pct >= 80): ?>
          <div class="small text-warning-emphasis mt-1">Close to the limit: <?= money($b['monthly_limit'] - $b['spent']) ?> left.</div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
