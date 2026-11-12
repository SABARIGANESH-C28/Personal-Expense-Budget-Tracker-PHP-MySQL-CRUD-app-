<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) { redirect('index.php'); }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT id, name, password_hash FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Same message for "no such email" and "wrong password" so attackers can't probe which emails exist.
    if ($user && password_verify($password, $user['password_hash'])) {
        // New session ID after login prevents session fixation attacks.
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        redirect('index.php');
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="card p-4">
    <h1 class="h4 mb-3"><i class="bi bi-box-arrow-in-right"></i> Log in</h1>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" required>
      </div>
      <button class="btn btn-primary w-100">Log in</button>
    </form>
    <p class="mt-3 mb-0 text-center">New here? <a href="register.php">Create an account</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
