<?php
session_start();

// Minden session változó törlése
$_SESSION = array();

// Session cookie törlése
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session megsemmisítése
session_destroy();

// Átirányítás a login oldalra
header("Location: login.php");
exit();
?>