<?php
session_start();
// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if user is logged in
if (!isset($_SESSION['staff_email'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$conn = new mysqli($servername, $dbusername, $dbpassword, $db);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$officeLat = 12.811393;
$officeLon = 80.227807;
$maxDistance = 50; // meters

function getDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3; // Earth's radius in meters
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $deltaPhi = deg2rad($lat2 - $lat1);
    $deltaLambda = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaPhi/2) * sin($deltaPhi/2) + 
         cos($phi1) * cos($phi2) * 
         sin($deltaLambda/2) * sin($deltaLambda/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set JSON header
    header('Content-Type: application/json');
    
    $employee_id = $_SESSION['staff_email'];
    $checkin_type = $_POST['checkin_type'] ?? 'morning';
    
    // Get real coordinates from POST data
    if (!isset($_POST['latitude']) || !isset($_POST['longitude'])) {
        echo json_encode(['success' => false, 'message' => 'Location data not provided. Please enable location services.']);
        exit();
    }
    
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    
    // Validate coordinates
    if ($latitude === 0.0 || $longitude === 0.0 || 
        $latitude < -90 || $latitude > 90 || 
        $longitude < -180 || $longitude > 180) {
        echo json_encode(['success' => false, 'message' => 'Invalid location data. Please try again.']);
        exit();
    }

    $distance = getDistance($latitude, $longitude, $officeLat, $officeLon);

    if ($distance <= $maxDistance) {
        $current_date = date('Y-m-d');
        $check_sql = "SELECT * FROM staff_attendance WHERE employee_id = ? AND DATE(timestamp) = ? AND checkin_type = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $employee_id, $current_date, $checkin_type);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $type_names = [
                'morning' => 'Morning check-in',
                'lunch_out' => 'Lunch break out',
                'lunch_in' => 'Lunch break in', 
                'evening' => 'Evening check-out'
            ];
            echo json_encode(['success' => false, 'message' => $type_names[$checkin_type] . ' already marked for today!']);
        } else {
            $status = 'present';
            if ($checkin_type === 'lunch_out') $status = 'lunch_break';
            elseif ($checkin_type === 'evening') $status = 'left';
            
            $insert_sql = "INSERT INTO staff_attendance (employee_id, latitude, longitude, checkin_type, status) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sddss", $employee_id, $latitude, $longitude, $checkin_type, $status);
            
            if ($insert_stmt->execute()) {
                $type_names = [
                    'morning' => 'Morning check-in',
                    'lunch_out' => 'Lunch break out',
                    'lunch_in' => 'Lunch break in',
                    'evening' => 'Evening check-out'
                ];
                
                echo json_encode([
                    'success' => true, 
                    'message' => $type_names[$checkin_type] . ' marked successfully!',
                    'distance' => round($distance, 2),
                    'time' => date('H:i:s'),
                    'date' => date('F j, Y'),
                    'type' => $checkin_type,
                    'user_lat' => $latitude,
                    'user_lon' => $longitude
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $insert_stmt->error]);
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'You are too far away (' . round($distance,2) . ' m)! Please come within ' . $maxDistance . ' meters of the office.',
            'distance' => round($distance, 2),
            'user_lat' => $latitude,
            'user_lon' => $longitude
        ]);
    }
    
    $conn->close();
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
?>