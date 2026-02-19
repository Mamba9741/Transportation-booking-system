<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check session timeout (30 minutes)
$timeout = 30 * 60;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
    session_destroy();
    header("Location: admin_login.php?timeout=1");
    exit();
}

// Update session time
$_SESSION['login_time'] = time();

// Function to check permissions
function hasPermission($required_role) {
    $allowed_roles = ['super_admin', 'admin'];
    
    if ($required_role === 'super_admin') {
        return $_SESSION['admin_role'] === 'super_admin';
    }
    
    return in_array($_SESSION['admin_role'], $allowed_roles);
}
?>