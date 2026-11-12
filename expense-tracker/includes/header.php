<?php
// Page layout top. Each page sets $pageTitle and $active before including this file.
$pageTitle = $pageTitle ?? 'Expense Tracker';
$active    = $active ?? '';
$loggedIn  = is_logged_in();

$links = [
    'dashboard' => ['index.php',             'bi-speedometer2', 'Dashboard'],
    'add'       => ['add_expense.php',       'bi-plus-circle',  'Add Expense'],
    'manage'    => ['manage_expenses.php',   'bi-list-ul',      'Manage Expenses'],
    'income'    => ['incomes.php',           'bi-cash-stack',   'Income'],
    'budgets'   => ['budgets.php',           'bi-piggy-bank',   'Budgets'],
    'reports'   => ['view_reports.php',      'bi-bar-chart',    'Reports'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Expense Tracker</title>
  <!-- Bootstrap 5 + icons via CDN, then our own CSS last so it can override Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php if ($loggedIn): ?>
  <!-- Top navbar: always visible, holds the hamburger button on small screens -->
  <nav class="navbar navbar-dark bg-dark fixed-top topbar">
    <div class="container-fluid">
      <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="offcanvas"
              data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
        <i class="bi bi-list"></i>
      </button>
      <a class="navbar-brand ms-2" href="index.php"><i class="bi bi-wallet2"></i> Expense Tracker</a>
      <div class="dropdown ms-auto">
        <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle"></i> <?= e($_SESSION['user_name'] ?? 'Account') ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <form action="logout.php" method="post" class="px-3">
              <?= csrf_field() ?>
              <button class="btn btn-link text-danger p-0 text-decoration-none">
                <i class="bi bi-box-arrow-right"></i> Log out
              </button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Sidebar: position:fixed on desktop (see style.css), slides in as an offcanvas on mobile -->
  <aside class="offcanvas-md offcanvas-start sidebar bg-dark text-white" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-md-none">
      <h5 class="offcanvas-title">Menu</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
    </div>
    <div class="offcanvas-body flex-column p-0">
      <ul class="nav nav-pills flex-column p-3 gap-1">
        <?php foreach ($links as $key => [$href, $icon, $label]): ?>
          <li class="nav-item">
            <a class="nav-link text-white <?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
              <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </aside>
<?php endif; ?>

<main class="<?= $loggedIn ? 'content' : 'container py-5' ?>">
  <?php foreach (pull_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show flash" role="alert">
      <?= e($f['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endforeach; ?>
