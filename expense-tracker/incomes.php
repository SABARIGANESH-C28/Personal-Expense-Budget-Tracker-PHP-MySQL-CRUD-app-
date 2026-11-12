<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid = current_user_id();
$errors = [];
$form = ['source' => '', 'amount' => '', 'income_date' => date('Y-m-d')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {                                   // CREATE
        $form['source']      = trim($_POST['source'] ?? '');
        $form['amount']      = trim($_POST['amount'] ?? '');
        $form['income_date'] = $_POST['income_date'] ?? '';

        if ($form['source'] === '' || mb_strlen($form['source']) > 120) $errors[] = 'Source is required (max 120 characters).';
        if (!valid_amount($form['amount']))                            $errors[] = 'Enter an amount greater than 0.';
        if (!valid_date($form['income_date']))                         $errors[] = 'Enter a valid date.';

        if (!$errors) {
            $amount = (float)$form['amount'];
            $stmt = $conn->prepare('INSERT INTO incomes (user_id, source, amount, income_date) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isds', $uid, $form['source'], $amount, $form['income_date']);   // i s d s
            $stmt->execute();
            $stmt->close();
            flash('success', 'Income added.');
            redirect('incomes.php');
        }
    } elseif ($action === 'delete') {                          // DELETE
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM incomes WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $uid);
        $stmt->execute();
        $stmt->close();
        flash('success', 'Income deleted.');
        redirect('incomes.php');
    }
}

// READ
$stmt = $conn->prepare('SELECT id, source, amount, income_date FROM incomes WHERE user_id = ? ORDER BY income_date DESC, id DESC LIMIT 100');
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Income';
$active = 'income';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Income</h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-body">
      <h2 class="h6">Add income</h2>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="mb-2">
          <label class="form-label" for="source">Source</label>
          <input class="form-control" id="source" name="source" value="<?= e($form['source']) ?>" maxlength="120" required
                 placeholder="Pocket money, stipend, freelance...">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label" for="amount">Amount (₹)</label>
            <input class="form-control" type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?= e($form['amount']) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label" for="income_date">Date</label>
            <input class="form-control" type="date" id="income_date" name="income_date" value="<?= e($form['income_date']) ?>" required>
          </div>
        </div>
        <button class="btn btn-success w-100">Add income</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Date</th><th>Source</th><th class="text-end">Amount</th><th></th></tr></thead>
          <tbody>
            <?php if (!$rows): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No income recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="text-nowrap"><?= e($r['income_date']) ?></td>
                <td><?= e($r['source']) ?></td>
                <td class="text-end amount text-success"><?= money($r['amount']) ?></td>
                <td class="text-end">
                  <form class="d-inline js-confirm" method="post" data-message="Delete this income entry?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i><span class="visually-hidden">Delete</span></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
