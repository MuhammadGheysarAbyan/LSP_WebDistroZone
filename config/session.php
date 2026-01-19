<?php
/**
 * Session Configuration
 * Include this file at the very beginning of scripts that need sessions
 * before session_start() is called, OR use this as a replacement for session_start()
 */

// Set session cookie parameters for better persistence
ini_set('session.cookie_lifetime', 86400 * 7); // 7 days
ini_set('session.gc_maxlifetime', 86400 * 7); // 7 days
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
