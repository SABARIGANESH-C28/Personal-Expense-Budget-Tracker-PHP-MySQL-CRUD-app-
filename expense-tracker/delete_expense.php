<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

// Deleting changes data, so it only accepts POST (never a plain link) and checks the CSRF token.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('manage_expenses.php');
}
csrf_check();

$id  = (int)($_POST['id'] ?? 0);
$uid = current_user_id();

$stmt = $conn->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

flash($deleted ? 'success' : 'danger', $deleted ? 'Expense deleted.' : 'Expense not found.');

// Return to the same filtered view the user was on, but only to a local page.
$back = $_POST['back'] ?? '';
redirect(preg_match('/^manage_expenses\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $back) ? $back : 'manage_expenses.php');
