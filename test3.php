<?php
// Enable error reporting at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Hardcoded admin credentials 
$admin_username = "admin";
$admin_password = "admin";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        header("Location: admin-login.html?error=empty_fields");
        exit();
    }

    // Check credentials against hardcoded values
    if ($username === $admin_username && $password === $admin_password) {
        // Set admin session variables
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_role'] = 'admin';
        
        // Log the login attempt
        error_log("Admin login successful - Username: $username");
        
        // Redirect to add_careers.php (changed from admin_dashboard.php)
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Log failed attempt
        error_log("Admin login failed - Username: $username, IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Redirect back with error
        header("Location: admin-login.html?error=invalid_credentials");
        exit();
    }
} else {
    // If not POST request, redirect to login
    header("Location: admin-login.html");
    exit();
}
?>