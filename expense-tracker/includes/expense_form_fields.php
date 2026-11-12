<?php
// Shared by add_expense.php and edit_expense.php. Expects $form (array), and optional $submitLabel.
?>
<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label" for="title">Title</label>
    <input class="form-control" id="title" name="title" value="<?= e($form['title']) ?>" maxlength="120" required>
  </div>
  <div class="col-md-3 col-6">
    <label class="form-label" for="amount">Amount (₹)</label>
    <input class="form-control" type="number" step="0.01" min="0.01" id="amount" name="amount"
           value="<?= e($form['amount']) ?>" required>
  </div>
  <div class="col-md-3 col-6">
    <label class="form-label" for="expense_date">Date</label>
    <input class="form-control" type="date" id="expense_date" name="expense_date"
           value="<?= e($form['expense_date']) ?>" required>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="category">Category</label>
    <select class="form-select" id="category" name="category" required>
      <?php foreach (CATEGORIES as $c): ?>
        <option value="<?= e($c) ?>" <?= $form['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-8">
    <label class="form-label" for="note">Note <span class="text-muted">(optional)</span></label>
    <input class="form-control" id="note" name="note" value="<?= e($form['note']) ?>" maxlength="255">
  </div>
</div>
<div class="mt-4 d-flex gap-2">
  <button class="btn btn-primary"><?= e($submitLabel ?? 'Save') ?></button>
  <a class="btn btn-outline-secondary" href="manage_expenses.php">Cancel</a>
</div>
