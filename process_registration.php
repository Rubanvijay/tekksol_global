<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$dbname = "bzbnom7tqqucjcivbuxo";

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    header("Location: test_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);

    // Store old form values in session
    $_SESSION['old_name'] = $name;
    $_SESSION['old_email'] = $email;
    $_SESSION['old_phone'] = $phone;
    $_SESSION['old_location'] = $location;

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($location)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: test_login.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: test_login.php");
        exit();
    }

    // Clean phone number
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean_phone) != 10) {
        $_SESSION['error'] = "Phone number must be 10 digits";
        header("Location: test_login.php");
        exit();
    }

    // Check if email already exists
    $check_sql = "SELECT id FROM test_student_registration_info WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if ($check_stmt === false) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: test_login.php");
        exit();
    }
    
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['error'] = "Email already registered.";
        header("Location: test_login.php");
        exit();
    }
    $check_stmt->close();

    // Insert new registration
    $sql = "INSERT INTO test_student_registration_info (name, email, phone_number, location) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: test_login.php");
        exit();
    }
    
    $stmt->bind_param("ssss", $name, $email, $clean_phone, $location);

    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        
        // Clear old form values from session
        unset($_SESSION['old_name']);
        unset($_SESSION['old_email']);
        unset($_SESSION['old_phone']);
        unset($_SESSION['old_location']);
        
        // Store student info in session
        $_SESSION['student_id'] = $student_id;
        $_SESSION['student_name'] = $name;
        $_SESSION['student_email'] = $email;
        $_SESSION['registered'] = true;
        
        $_SESSION['success'] = "Registration successful! Please select your course and test.";
        header("Location: test_selection.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: test_login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: test_login.php");
    exit();
}
?>