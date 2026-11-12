<?php
// Shared helpers: session start, CSRF protection, output escaping, login guard, flash messages.

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,        // JavaScript cannot read the session cookie (limits XSS damage)
        'samesite' => 'Lax',       // helps against CSRF
    ]);
    session_start();
}

require_once __DIR__ . '/../config/db.php';

const CATEGORIES = ['Food', 'Transport', 'Rent', 'Bills', 'Shopping', 'Health', 'Education', 'Entertainment', 'Other'];

/** Escape any value before printing it into HTML (prevents XSS). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Format an amount as Indian rupees. */
function money($amount): string
{
    return '₹' . number_format((float)$amount, 2);
}

/** Redirect and stop. exit() matters: without it PHP keeps running the rest of the page. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Put at the top of every private page. */
function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function current_user_id(): int
{
    return (int)$_SESSION['user_id'];
}

// ---- CSRF ------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Call at the top of every POST handler. Stops the request if the token is wrong. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Go back, refresh the page and try again.');
    }
}

// ---- Flash messages (shown once, then cleared) ------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function pull_flashes(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

// ---- Input validation helpers ----------------------------------------------
function valid_date(string $d): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function valid_amount($a): bool
{
    return is_numeric($a) && (float)$a > 0 && (float)$a < 100000000;
}

// ---- Month helpers ----------------------------------------------------------
/** Validate a "YYYY-MM" string; fall back to the current month. */
function clean_month(?string $ym): string
{
    return ($ym && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym)) ? $ym : date('Y-m');
}

/** Returns [first day of month, first day of next month] as Y-m-d strings. */
function month_bounds(string $ym): array
{
    $start = $ym . '-01';
    $end   = date('Y-m-d', strtotime($start . ' +1 month'));
    return [$start, $end];
}

/** SUM(amount) for one table/date column between two dates, for one user. */
function sum_between(mysqli $conn, string $table, string $dateCol, int $uid, string $from, string $to): float
{
    // $table and $dateCol are never user input (only hard-coded values below), so this is safe.
    $allowed = ['expenses' => 'expense_date', 'incomes' => 'income_date'];
    if (($allowed[$table] ?? null) !== $dateCol) { throw new InvalidArgumentException('Bad table'); }

    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM $table WHERE user_id = ? AND $dateCol >= ? AND $dateCol < ?");
    $stmt->bind_param('iss', $uid, $from, $to);
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    return (float)$total;
}
