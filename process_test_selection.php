<?php
session_start();

// Check if student is registered
if (!isset($_SESSION['registered']) || !$_SESSION['registered']) {
    header("Location: test_login.html");
    exit();
}

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
    $_SESSION['error'] = "Database connection failed";
    header("Location: test_selection.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course = trim($_POST['course']);
    $test = trim($_POST['test']);
    $student_id = $_SESSION['student_id'];

    // Validate selection
    if (empty($course) || empty($test)) {
        $_SESSION['error'] = "Please select both course and test";
        header("Location: test_selection.php");
        exit();
    }

    // Update student record with course and test selection
    $sql = "UPDATE test_student_registration_info SET selected_course = ?, selected_test = ?, status = 'test_selected' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $course, $test, $student_id);

    if ($stmt->execute()) {
        $_SESSION['selected_course'] = $course;
        $_SESSION['selected_test'] = $test;
        
        // Redirect to appropriate test page
        if ($course== "Java" && $test === 'Test A') {
            header("Location: Test_A.html");
        } else if ($course== "Java" && $test === 'Test B') {
            header("Location: Test_B.html");
        } else if ($course== "Python" && $test === 'Test A') {
            header("Location: Test_C.html");
        }else if ($course== "Python" && $test === 'Test B') {
            header("Location: Test_D.html");
        }
        else {
            $_SESSION['error'] = "Invalid test selection";
            header("Location: test_selection.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Failed to save test selection. Please try again.";
        header("Location: test_selection.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: test_selection.php");
    exit();
}
?>