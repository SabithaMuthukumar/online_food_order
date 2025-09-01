 <?php
// auth.php - Authentication check for all admin pages
session_start();
// Add after session_start()
$inactive = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['admin_id'])) {
    // Store the requested URL before redirecting
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: admin_login.php");
    exit();
}

// Optional: Add security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>

