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
        echo json_encode(['exists' => false, 'error' => 'Database connection failed']);
        exit();
    }
    
    // Check if student exists and get their name
    $sql = "SELECT s.username, sd.name 
            FROM students s 
            LEFT JOIN student_details sd ON s.username = sd.username 
            WHERE s.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'name' => $row['name'] ?? 'Name not available'
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

echo json_encode(['exists' => false]);
?>