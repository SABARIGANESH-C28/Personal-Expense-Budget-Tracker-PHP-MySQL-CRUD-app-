<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$errors = [];
$form = ['title' => '', 'amount' => '', 'category' => 'Food', 'expense_date' => date('Y-m-d'), 'note' => ''];

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
        $uid = current_user_id();
        $amount = (float)$form['amount'];
        // Types: i = integer, s = string, d = double (decimal number)  ->  "issdss"
        $stmt = $conn->prepare(
            'INSERT INTO expenses (user_id, title, category, amount, expense_date, note) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issdss', $uid, $form['title'], $form['category'], $amount, $form['expense_date'], $form['note']);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Expense added.');
        redirect('manage_expenses.php');
    }
}

$pageTitle = 'Add Expense';
$active = 'add';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-3">Add Expense</h1>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger py-2"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card p-3 p-md-4">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <?php $submitLabel = 'Save expense'; include __DIR__ . '/includes/expense_form_fields.php'; ?>
  </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
