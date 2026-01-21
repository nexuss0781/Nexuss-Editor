<?php
/**
 * Middleware: Authentication & Session Watchdog
 */
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates if the user is logged in and session hasn't expired
 */
function checkAuth() {
    // 1. Check if authenticated flag exists
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        header("Location: login.php");
        exit;
    }

    // 2. Check for 30-minute inactivity
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_EXPIRY) {
            logoutUser("session_expired");
        }
    }

    // 3. Update activity timestamp
    $_SESSION['last_activity'] = time();
}

/**
 * Handles Logout and Redirection
 */
function logoutUser($reason = "") {
    session_unset();
    session_destroy();
    $url = "login.php";
    if ($reason) $url .= "?reason=" . $reason;
    header("Location: $url");
    exit;
}
