<?php
// test_enrollment.php - Simple test version without email
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log that script is running
error_log("Test enrollment script started");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $fullName = $_POST["fullName"] ?? '';
    $email = $_POST["email"] ?? '';
    $phone = $_POST["phone"] ?? '';
    $qualification = $_POST["qualification"] ?? '';
    $address = $_POST["address"] ?? '';
    $mode = $_POST["mode"] ?? '';
    $message = $_POST["message"] ?? '';
    $selectedCourses = $_POST["courses"] ?? [];
    
    // Log received data
    error_log("Received data: " . json_encode($_POST));
    
    // Validate required fields
    if (empty($fullName) || empty($email) || empty($phone) || empty($mode)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please fill in all required fields.',
            'debug' => 'Missing: ' . (!empty($fullName) ? '' : 'name ') . 
                       (!empty($email) ? '' : 'email ') . 
                       (!empty($phone) ? '' : 'phone ') . 
                       (!empty($mode) ? '' : 'mode')
        ]);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please provide a valid email address.'
        ]);
        exit;
    }
    
    // Validate courses
    if (empty($selectedCourses)) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please select at least one course.'
        ]);
        exit;
    }
    
    // Format courses list
    $coursesList = implode(', ', $selectedCourses);
    
    // Success response
    echo json_encode([
        'status' => 'success',
        'message' => '<p><strong>Test Enrollment Successful!</strong></p>' .
                    '<p>Name: ' . htmlspecialchars($fullName) . '</p>' .
                    '<p>Email: ' . htmlspecialchars($email) . '</p>' .
                    '<p>Phone: ' . htmlspecialchars($phone) . '</p>' .
                    '<p>Courses: ' . htmlspecialchars($coursesList) . '</p>' .
                    '<p>Mode: ' . htmlspecialchars($mode) . '</p>' .
                    '<p><small>This is a test response. Emails are not being sent.</small></p>',
        'data' => [
            'name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'courses' => $selectedCourses,
            'mode' => $mode
        ]
    ]);
    
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method. Use POST.',
        'method' => $_SERVER["REQUEST_METHOD"]
    ]);
}
?>