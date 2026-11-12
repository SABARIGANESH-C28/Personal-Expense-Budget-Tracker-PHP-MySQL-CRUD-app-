<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) { redirect('index.php'); }

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Never trust user input: validate every field on the server.
    if ($name === '' || mb_strlen($name) > 100)            $errors[] = 'Enter your name (max 100 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8)                             $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                            $errors[] = 'Passwords do not match.';

    if (!$errors) {
        // Is the email already taken? (prepared statement: "s" = string)
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $taken = $stmt->num_rows > 0;
        $stmt->close();

        if ($taken) {
            $errors[] = 'That email is already registered. Try logging in.';
        } else {
            // password_hash adds a random salt and uses a slow algorithm (bcrypt by default).
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            $stmt->execute();
            $stmt->close();

            flash('success', 'Account created. Please log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
  <div class="card p-4">
    <h1 class="h4 mb-3"><i class="bi bi-person-plus"></i> Create account</h1>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger py-2"><?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
      </div>
      <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" minlength="8" required>
        <div class="form-text">At least 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="confirm">Confirm password</label>
        <input class="form-control" type="password" id="confirm" name="confirm" required>
      </div>
      <button class="btn btn-primary w-100">Register</button>
    </form>
    <p class="mt-3 mb-0 text-center">Already registered? <a href="login.php">Log in</a></p>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
