<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        header("Location: staff-login.html?error=empty_fields");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: staff-login.html?error=invalid_email");
        exit;
    }

    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    if($conn->connect_error) {
        header("Location: staff-login.html?error=db_connection");
        exit;
    }

    // Updated SQL to include team field
    $sql = "SELECT email, password, team FROM staff WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        header("Location: staff-login.html?error=db_error");
        exit;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        if($password === $row["password"]) {
            $_SESSION['staff_email'] = $email;
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_team'] = $row["team"]; // Store team in session
            
            $stmt->close();
            $conn->close();
            ob_end_clean();
            
            // Redirect based on team
            if ($row["team"] === 'Global') {
                header("Location: staff-dashboard-global.php");
            } else {
                // For Innovation team or any other team, redirect to original dashboard
                header("Location: staff-dashboard.php");
            }
            exit;
        } else {
            $stmt->close();
            $conn->close();
            ob_end_clean();
            header("Location: staff-login.html?error=invalid_password");
            exit;
        }
    } else {
        $stmt->close();
        $conn->close();
        ob_end_clean();
        header("Location: staff-login.html?error=user_not_found");
        exit;
    }
} else {
    header("Location: staff-login.html");
    exit;
}
?>