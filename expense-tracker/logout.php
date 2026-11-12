<?php
require_once __DIR__ . '/includes/auth.php';

// Logout is a POST with a CSRF token so another site can't log you out with a hidden image/link.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $_SESSION = [];                                   // 1. empty the session data
    if (ini_get('session.use_cookies')) {             // 2. expire the session cookie in the browser
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();                                // 3. destroy the server-side session
}
redirect('login.php');
