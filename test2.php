<?php
// Enable error reporting at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Basic validation
    if (empty($username) || empty($password)) {
        die("Please fill in all fields.");
    }

    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    if($conn->connect_error) {
        die("Connection Failed: " . $conn->connect_error);
    }

    // Check only the specific user
    $sql = "SELECT username, password FROM staff WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // For testing - remove this in production
        error_log("Login attempt - Username: $username, DB Username: " . $row["username"] . ", Password match: " . ($password == $row["password"] ? 'YES' : 'NO'));
        
        if($username == $row["username"] && $password == $row["password"]) {
            // Set CORRECT session variables for STAFF
            $_SESSION['staff_username'] = $username;
            $_SESSION['staff_logged_in'] = true;
            
            $stmt->close();
            $conn->close();
            
            // Redirect without any output
            header("Location: staff-dashboard.php");
            exit();
        } else {
            $stmt->close();
            $conn->close();
            header("Location: staff-login.html?error=invalid_password");
            exit();
        }
    } else {
        $stmt->close();
        $conn->close();
        header("Location: staff-login.html?error=user_not_found");
        exit();
    }
} else {
    header("Location: staff-login.html");
    exit();
}
?>