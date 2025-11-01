<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$dbusername = "root";
$dbpassword = "ruban";
$db = "tekksol_global";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        echo json_encode(['available' => false, 'error' => 'Database connection failed']);
        exit();
    }
    
    $sql = "SELECT username FROM students WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $available = $result->num_rows === 0;
    
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json');
    echo json_encode(['available' => $available]);
    exit();
}

echo json_encode(['available' => false]);
?>