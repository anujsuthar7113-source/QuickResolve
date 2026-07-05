<?php
// logout.php — Session Destroy & Logout
// Destroys the session and redirects to login page.
// Works for ALL roles: admin, department, user.

session_start();
require_once 'config/db_18.php';

// Capture name for goodbye message before destroying
$name = $_SESSION['name'] ?? 'User';

// ── Destroy session completely 
$_SESSION = [];  // Clear all session variables

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Finally destroy the session
session_destroy();

// Redirect to login page with a goodbye message
redirect(SITE_URL . '/login.php?msg=' . urlencode('You have been logged out successfully. Goodbye, ' . $name . '!'));
?>
