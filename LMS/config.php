<?php
include 'env.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed. Please check your configuration.");
}

// Global Setting Variables
$site_url = SITE_URL;
$country_code = COUNTRY_CODE;

/* ================= SECURITY HELPERS ================= */

// 1. Centralized Access Control
function require_role($allowed_roles) {
    if(!is_array($allowed_roles)) $allowed_roles = [$allowed_roles];
    if(!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], $allowed_roles)) {
        header("Location: index.php");
        exit();
    }
}

// 2. XSS Protection: Shortcut for htmlspecialchars
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// 3. CSRF Token Generation
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 4. CSRF Token Verification
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>