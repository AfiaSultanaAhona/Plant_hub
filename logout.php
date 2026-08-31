<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all PHP session data.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// Logout must finish on the Account Login page.
header('Location: login.php');
exit;
?>
