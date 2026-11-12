<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$uid = current_user_id();
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Load the expense, but ONLY if it belongs to the logged-in user (AND user_id = ?).
// Without this check, anyone could edit other people's rows by changing the id in the URL.
$stmt = $conn->prepare('SELECT id, title, amount, category, expense_date, note FROM expenses WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash('danger', 'Expense not found.');
    redirect('manage_expenses.php');
}

$errors = [];
$form = [
    'title' => $row['title'], 'amount' => $row['amount'], 'category' => $row['category'],
    'expense_date' => $row['expense_date'], 'note' => $row['note'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $form['title']        = trim($_POST['title'] ?? '');
    $form['amount']       = trim($_POST['amount'] ?? '');
    $form['category']     = $_POST['category'] ?? '';
    $form['expense_date'] = $_POST['expense_date'] ?? '';
    $form['note']         = trim($_POST['note'] ?? '');

    if ($form['title'] === '' || mb_strlen($form['title']) > 120) $errors[] = 'Title is required (max 120 characters).';
    if (!valid_amount($form['amount']))                          $errors[] = 'Enter an amount greater than 0.';
    if (!in_array($form['category'], CATEGORIES, true))          $errors[] = 'Choose a valid category.';
    if (!valid_date($form['expense_date']))                      $errors[] = 'Enter a valid date.';
    if (mb_strlen($form['note']) > 255)                          $errors[] = 'Note is too long (max 255 characters).';

    if (!$errors) {
        $amount = (float)$form['amount'];
        $stmt = $conn->prepare(
            'UPDATE expenses SET title = ?, category = ?, amount = ?, expense_date = ?, note = ? WHERE id = ? AND user_id = ?'
        );
        // s s d s s i i
        $stmt->bind_param('ssdssii', $form['title'], $form['category'], $amount, $form['expense_date'], $form['note'], $id, $uid);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Expense updated.');
        redirect('manage_expenses.php');
    }
}

$pageTitle = 'Edit Expense';
$active = 'manage';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Edit Expense</h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card p-3 p-md-4">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php $submitLabel = 'Update expense'; include __DIR__ . '/includes/expense_form_fields.php'; ?>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
