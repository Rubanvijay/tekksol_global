<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

header('Content-Type: application/json');

if (!isset($_SESSION['student_username'])) {
    echo json_encode(['hasNewAssignments' => false]);
    exit();
}

$student_username = $_SESSION['student_username'];

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if (!$conn->connect_error) {
        // Get the count of assignments that were created after the last check
        $sql = "SELECT COUNT(*) as new_count FROM student_task 
                WHERE username = ? AND assigned_date > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $student_username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode(['hasNewAssignments' => $row['new_count'] > 0]);
        
        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['hasNewAssignments' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['hasNewAssignments' => false]);
}
?>