<?php
session_start();

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if user is logged in
if (!isset($_SESSION['staff_email'])) {
    // If AJAX request, return JSON
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    } else {
        // If regular request, redirect to login
        header("Location: staff-login.html");
        exit();
    }
}

// Handle AJAX attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin_type'])) {
    // Set JSON header
    header('Content-Type: application/json');
    
    // Turn off error display to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    $response = [];
    
    try {
        $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $staff_email = $_SESSION['staff_email'];
        $checkin_type = $_POST['checkin_type'];
        
        // Office coordinates
        $officeLat = 12.811393;
        $officeLon = 80.227807;
        
        // Hardcoded coordinates for testing - ALWAYS USE OFFICE LOCATION
        $latitude = 12.811393;
        $longitude = 80.227807;

        // Check if already marked for today
        $current_date = date('Y-m-d');
        $check_sql = "SELECT * FROM staff_attendance WHERE employee_id = ? AND DATE(timestamp) = ? AND checkin_type = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("sss", $staff_email, $current_date, $checkin_type);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $type_names = [
                'morning' => 'Morning check-in',
                'lunch_out' => 'Lunch break out',
                'lunch_in' => 'Lunch break in', 
                'evening' => 'Evening check-out'
            ];
            $response = ['success' => false, 'message' => $type_names[$checkin_type] . ' already marked for today!'];
        } else {
            // Determine status
            $status = 'present';
            if ($checkin_type === 'lunch_out') $status = 'lunch_break';
            elseif ($checkin_type === 'evening') $status = 'left';
            
            // Insert record
            $insert_sql = "INSERT INTO staff_attendance (employee_id, latitude, longitude, checkin_type, status) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
           $insert_stmt->bind_param("sddss", $staff_email, $latitude, $longitude, $checkin_type, $status);
            
            if ($insert_stmt->execute()) {
                $type_names = [
                    'morning' => 'Morning check-in',
                    'lunch_out' => 'Lunch break out',
                    'lunch_in' => 'Lunch break in',
                    'evening' => 'Evening check-out'
                ];
                
                $response = [
                    'success' => true, 
                    'message' => $type_names[$checkin_type] . ' marked successfully!',
                    'distance' => 0,
                    'time' => date('H:i:s'),
                    'date' => date('F j, Y'),
                    'type' => $checkin_type
                ];
            } else {
                throw new Exception("Database insert failed: " . $insert_stmt->error);
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}

// If not an AJAX request, show the HTML page
// Get today's attendance status for display
function getTodayAttendanceStatus($conn, $staff_email) {
    $current_date = date('Y-m-d');
    $status_sql = "SELECT checkin_type, timestamp FROM staff_attendance WHERE employee_id = ? AND DATE(timestamp) = ? ORDER BY timestamp";
    $status_stmt = $conn->prepare($status_sql);
    $status_stmt->bind_param("ss", $staff_email, $current_date);
    $status_stmt->execute();
    $result = $status_stmt->get_result();
    
    $attendance = [
        'morning' => false,
        'lunch_out' => false,
        'lunch_in' => false,
        'evening' => false
    ];
    
    $timestamps = [];
    
    while ($row = $result->fetch_assoc()) {
        $attendance[$row['checkin_type']] = true;
        $timestamps[$row['checkin_type']] = date('H:i', strtotime($row['timestamp']));
    }
    
    $status_stmt->close();
    return ['attendance' => $attendance, 'timestamps' => $timestamps];
}

// Get today's status for display
try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    if (!$conn->connect_error) {
        $today_status = getTodayAttendanceStatus($conn, $_SESSION['staff_email']);
        $conn->close();
    } else {
        $today_status = ['attendance' => [], 'timestamps' => []];
    }
} catch (Exception $e) {
    $today_status = ['attendance' => [], 'timestamps' => []];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Mark Attendance - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Employee Attendance, Tekksol Global, Staff Check-in" name="keywords">
    <meta content="Mark your daily attendance at Tekksol Global training institute" name="description">

    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-size: 12px;
            color: #6c757d;
        }
        /* Mobile Login Dropdown Styles */
        .mobile-login-dropdown {
            display: none;
            padding: 10px 15px;
            border-top: 1px solid #dee2e6;
        }

        @media (max-width: 991px) {
            .desktop-login-dropdown {
                display: none;
            }
            
            .mobile-login-dropdown {
                display: block;
            }
            
            .mobile-login-dropdown .dropdown-menu {
                position: static !important;
                transform: none !important;
                border: none;
                box-shadow: none;
                background-color: transparent;
                width: 100% !important;
            }
            
            .mobile-login-dropdown .dropdown-item {
                padding: 12px 0;
                color: #333;
                border-bottom: 1px solid #f1f1f1;
                font-weight: 500;
            }
            
            .mobile-login-dropdown .dropdown-item:last-child {
                border-bottom: none;
            }
            
            .mobile-login-dropdown .dropdown-item:hover {
                background-color: transparent;
                color: #06BBCC;
            }
            
            .mobile-login-dropdown .dropdown-item i {
                color: #06BBCC;
                width: 20px;
                text-align: center;
            }
            
            .mobile-login-dropdown .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 1rem;
            }
        }
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .attendance-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            padding: 40px;
            margin: 30px auto;
            max-width: 800px;
            text-align: center;
            border: 1px solid rgba(6, 187, 204, 0.1);
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .attendance-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .attendance-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 20px 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            transform: translateZ(20px);
            position: relative;
            overflow: hidden;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .attendance-button.morning {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .attendance-button.lunch_out {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            box-shadow: 0 10px 25px rgba(255, 193, 7, 0.3);
        }
        
        .attendance-button.lunch_in {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            box-shadow: 0 10px 25px rgba(23, 162, 184, 0.3);
        }
        
        .attendance-button.evening {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.3);
        }
        
        .attendance-button:hover {
            transform: translateZ(30px) translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .attendance-button:active {
            transform: translateZ(15px) translateY(2px);
        }
        
        .attendance-button:disabled {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            cursor: not-allowed;
            transform: translateZ(0);
            box-shadow: none;
        }
        
        .attendance-button:disabled:hover {
            transform: translateZ(0);
            box-shadow: none;
        }
        
        .button-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .button-text {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .button-time {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .status-message {
            margin-top: 30px;
            padding: 20px;
            border-radius: 15px;
            font-weight: 500;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            transform-style: preserve-3d;
        }
        
        .status-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
            transform: translateZ(15px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.2);
        }
        
        .status-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
            transform: translateZ(15px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.2);
        }
        
        .status-loading {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border: 2px solid #17a2b8;
            transform: translateZ(15px);
            box-shadow: 0 8px 20px rgba(23, 162, 184, 0.2);
        }
        
        .location-info {
            background: linear-gradient(135deg, #e7f3ff 0%, #d1ecf1 100%);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #06BBCC;
            transform: translateZ(10px);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(6, 187, 204, 0.2);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #06BBCC;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        .welcome-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .profile-icon {
            font-size: 4rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .office-location {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            border: 2px solid #ffc107;
        }
        
        .today-status {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #dee2e6;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-completed {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-pending {
            color: #6c757d;
        }
        
        .testing-notice {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: 2px solid #17a2b8;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

     <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">
        <a href="index.html" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
            <img src="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" alt="Tekksol Global Logo" height="60px" width="100px">
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-4 p-lg-0">
                  
                <a href="mark_attendance.php" class="nav-item nav-link ">Checkin</a>
                <a href="request_leave_approval.php" class="nav-item nav-link ">Leave Request</a>
               
            </div>
            
            <div class="d-none d-lg-block desktop-login-dropdown">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i><?php 
                          $email = $_SESSION['staff_email'] ?? '';
                          $username = $email ? explode('@', $email)[0] : 'Staff';
                          echo htmlspecialchars($username); 
                        ?> 
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
                                <i class="fas fa-check-circle me-2"></i> Check-in
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="edit_walkin_report.php">
                                <i class="fas fa-user-plus me-2"></i> Edit Report
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="request_leave_approval.php">
                                <i class="fas fa-tasks me-2"></i> Request Leave
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Mobile Login Dropdown -->
            <div class="mobile-login-dropdown d-lg-none">
                <div class="dropdown">
                    <button class="btn btn-primary w-100 dropdown-toggle" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i><?php 
                          $email = $_SESSION['staff_email'] ?? '';
                          $username = $email ? explode('@', $email)[0] : 'Staff';
                          echo htmlspecialchars($username); 
                        ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
                       
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
                                <i class="fas fa-check-circle me-2"></i> Check-in
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="edit_walkin_report.php">
                                <i class="fas fa-user-plus me-2"></i> Edit Report
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="request_leave_approval.php">
                                <i class="fas fa-tasks me-2"></i> Request Leave
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 text-white mb-3">Staff Attendance</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php 
                        $email = $_SESSION['staff_email'] ?? '';
                        $username = $email ? explode('@', $email)[0] : 'Staff';
                        echo htmlspecialchars($username); 
                        ?></strong>!
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="status-badge bg-white text-primary">Staff Portal</span>
                        <span class="status-badge bg-white text-primary">Daily Check-in</span>
                        <span class="status-badge bg-white text-primary">Multiple Sessions</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="attendance-card">
                <h2 class="mb-4"><i class="fas fa-clock me-2"></i>Daily Attendance</h2>
                <p class="text-muted mb-4">Mark your attendance for different sessions throughout the day</p>
                
                <!-- Testing Notice -->
                <div class="testing-notice">
                    <h5><i class="fas fa-vial me-2"></i>Testing Mode Active</h5>
                    <p class="mb-0">Using hardcoded office coordinates (12.811393, 80.227807) for testing. All check-ins will work regardless of actual location.</p>
                </div>
                
                <!-- Today's Status -->
                <div class="today-status">
                    <h5><i class="fas fa-calendar-check me-2"></i>Today's Status - <?php echo date('F j, Y'); ?></h5>
                    <div class="status-item">
                        <span>Morning Check-in:</span>
                        <span class="<?php echo $today_status['attendance']['morning'] ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo $today_status['attendance']['morning'] ? '✓ Completed at ' . $today_status['timestamps']['morning'] : '✗ Pending'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Lunch Break Out:</span>
                        <span class="<?php echo $today_status['attendance']['lunch_out'] ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo $today_status['attendance']['lunch_out'] ? '✓ Completed at ' . $today_status['timestamps']['lunch_out'] : '✗ Pending'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Lunch Break In:</span>
                        <span class="<?php echo $today_status['attendance']['lunch_in'] ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo $today_status['attendance']['lunch_in'] ? '✓ Completed at ' . $today_status['timestamps']['lunch_in'] : '✗ Pending'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span>Evening Check-out:</span>
                        <span class="<?php echo $today_status['attendance']['evening'] ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo $today_status['attendance']['evening'] ? '✓ Completed at ' . $today_status['timestamps']['evening'] : '✗ Pending'; ?>
                        </span>
                    </div>
                </div>

                <!-- Office Location Info -->
                <div class="office-location">
                    <h5><i class="fas fa-map-marker-alt me-2"></i>Office Location</h5>
                    <p class="mb-1"><strong>Tekksol Global</strong></p>
                    <p class="mb-1">OMR, Rajiv Gandhi Salai, Chennai</p>
                    <p class="mb-0"><small>Allowed Range: 50 meters from office</small></p>
                </div>

                <!-- Current Location Info -->
                <div class="location-info">
                    <h5><i class="fas fa-location-arrow me-2"></i>Current Location</h5>
                    <div class="info-item">
                        <span class="info-label">Latitude:</span>
                        <span class="info-value" id="currentLat">Detecting...</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Longitude:</span>
                        <span class="info-value" id="currentLon">Detecting...</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Distance:</span>
                        <span class="info-value" id="distanceInfo">Calculating...</span>
                    </div>
                </div>

                <!-- Attendance Buttons -->
                <div class="attendance-buttons">
                    <button class="attendance-button morning <?php echo $today_status['attendance']['morning'] ? '' : 'pulse-animation'; ?>" 
                            onclick="markAttendance('morning')" 
                            id="morningBtn"
                            <?php echo $today_status['attendance']['morning'] ? 'disabled' : ''; ?>>
                        <div class="button-icon"><i class="fas fa-sun"></i></div>
                        <div class="button-text">Morning Check-in</div>
                        <?php if ($today_status['attendance']['morning']): ?>
                            <div class="button-time">✓ <?php echo $today_status['timestamps']['morning']; ?></div>
                        <?php endif; ?>
                    </button>

                    <button class="attendance-button lunch_out" 
                            onclick="markAttendance('lunch_out')" 
                            id="lunchOutBtn"
                            <?php echo $today_status['attendance']['lunch_out'] ? 'disabled' : ''; ?>>
                        <div class="button-icon"><i class="fas fa-utensils"></i></div>
                        <div class="button-text">Lunch Break Out</div>
                        <?php if ($today_status['attendance']['lunch_out']): ?>
                            <div class="button-time">✓ <?php echo $today_status['timestamps']['lunch_out']; ?></div>
                        <?php endif; ?>
                    </button>

                    <button class="attendance-button lunch_in" 
                            onclick="markAttendance('lunch_in')" 
                            id="lunchInBtn"
                            <?php echo $today_status['attendance']['lunch_in'] ? 'disabled' : ''; ?>>
                        <div class="button-icon"><i class="fas fa-undo"></i></div>
                        <div class="button-text">Lunch Break In</div>
                        <?php if ($today_status['attendance']['lunch_in']): ?>
                            <div class="button-time">✓ <?php echo $today_status['timestamps']['lunch_in']; ?></div>
                        <?php endif; ?>
                    </button>

                    <button class="attendance-button evening" 
                            onclick="markAttendance('evening')" 
                            id="eveningBtn"
                            <?php echo $today_status['attendance']['evening'] ? 'disabled' : ''; ?>>
                        <div class="button-icon"><i class="fas fa-moon"></i></div>
                        <div class="button-text">Evening Check-out</div>
                        <?php if ($today_status['attendance']['evening']): ?>
                            <div class="button-time">✓ <?php echo $today_status['timestamps']['evening']; ?></div>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- Status Message -->
                <div id="status" class="status-message">
                    <span>Select a check-in type above</span>
                </div>

                <!-- Success Message (Hidden by default) -->
                <div id="successMessage" class="status-message status-success" style="display: none;">
                    <div>
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 id="successTitle">Attendance Marked Successfully!</h4>
                        <p class="mb-2" id="successTime"></p>
                        <p class="mb-0">Have a productive day!</p>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="row mt-5">
                <div class="col-md-6">
                    <div class="attendance-card">
                        <h4><i class="fas fa-info-circle me-2"></i>How It Works</h4>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Location verification within 50m</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Four check-in types per day</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time GPS tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Automatic timestamp recording</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Duplicate prevention</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="attendance-card">
                        <h4><i class="fas fa-question-circle me-2"></i>Need Help?</h4>
                        <p>If you're having trouble marking attendance:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-wifi me-2 text-primary"></i>Check your internet connection</li>
                            <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Ensure location services are enabled</li>
                            <li class="mb-2"><i class="fas fa-building me-2 text-primary"></i>Make sure you're within office premises</li>
                            <li class="mb-2"><i class="fas fa-phone me-2 text-primary"></i>Contact admin if issues persist</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Link</h4>
                    <a class="btn btn-link" href="about.html">About Us</a>
                    <a class="btn btn-link" href="contact.html">Contact Us</a>
                    <a class="btn btn-link" href="privacy-policy.html">Privacy Policy</a>
                    <a class="btn btn-link" href="terms-condition.html">Terms & Condition</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Tekksol Global, OMR, Rajiv Gandhi Salai, Chennai</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+91 9042527746</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@tekksolglobal.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href="https://www.facebook.com/teksolglobal/"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href="https://www.linkedin.com/company/tekksol-global/"><i class="fab fa-linkedin-in"></i></a>
                        <a class="btn btn-outline-light btn-social" href="https://www.instagram.com/tekksol_global/"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Staff Resources</h4>
                    <a class="btn btn-link" href="mark_attendance.php">Attendance</a>
                    <a class="btn btn-link" href="add-student.php">Add Student</a>
                    <a class="btn btn-link" href="view-all-students.php">All Students</a>
                    <a class="btn btn-link" href="reports.php">Reports</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help with attendance? Contact admin</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <a href="contact.html" class="btn btn-primary w-100">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">Tekksol Global</a>, All Rights Reserved 2024.
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="footer-menu">
                            <a href="index.html">Home</a>
                            <a href="staff-dashboard.php">Dashboard</a>
                            <a href="contact.html">Help</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>

    <script>
        const officeLat = 12.811393;
        const officeLon = 80.227807;
        const maxDistance = 50; // meters

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const toRad = x => x * Math.PI / 180;
            const φ1 = toRad(lat1);
            const φ2 = toRad(lat2);
            const Δφ = toRad(lat2 - lat1);
            const Δλ = toRad(lon2 - lon1);
            const a = Math.sin(Δφ/2) ** 2 +
                      Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ/2) ** 2;
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function updateLocationInfo(lat, lon) {
            document.getElementById("currentLat").textContent = lat.toFixed(6);
            document.getElementById("currentLon").textContent = lon.toFixed(6);
            
            const distance = getDistance(lat, lon, officeLat, officeLon);
            document.getElementById("distanceInfo").textContent = distance.toFixed(2) + " meters";
            
            if (distance <= maxDistance) {
                document.getElementById("distanceInfo").innerHTML = 
                    '<span class="text-success">' + distance.toFixed(2) + ' meters ✓</span>';
            } else {
                document.getElementById("distanceInfo").innerHTML = 
                    '<span class="text-danger">' + distance.toFixed(2) + ' meters ✗</span>';
            }
        }

        function markAttendance(checkinType) {
    console.log("=== DEBUG START ===");
    console.log("Marking attendance for:", checkinType);
    
    const statusDiv = document.getElementById("status");
    const successDiv = document.getElementById("successMessage");
    const button = document.getElementById(checkinType + 'Btn');
    
    // Hide success message if shown
    successDiv.style.display = 'none';
    
    const typeNames = {
        'morning': 'Morning check-in',
        'lunch_out': 'Lunch break out', 
        'lunch_in': 'Lunch break in',
        'evening': 'Evening check-out'
    };
    
    statusDiv.className = "status-message status-loading";
    statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Marking ' + typeNames[checkinType] + '...';
    
    // Disable all buttons during processing
    document.querySelectorAll('.attendance-button').forEach(btn => {
        btn.disabled = true;
    });

    // Use hardcoded office coordinates
    const lat = 12.811393;
    const lon = 80.227807;
    
    console.log("Sending data:", {
        checkin_type: checkinType,
        latitude: lat,
        longitude: lon
    });

    // Send data to server via AJAX
    $.ajax({
        url: 'validate_attendance.php',
        type: 'POST',
        data: {
            latitude: lat,
            longitude: lon,
            checkin_type: checkinType
        },
        success: function(response) {
            console.log("=== AJAX SUCCESS ===");
            console.log("Response:", response);
            
            // jQuery automatically parses JSON, so use response directly
            if (response.success) {
                console.log("SUCCESS: Attendance marked");
                // Show success message
                document.getElementById("successTitle").textContent = response.message;
                document.getElementById("successTime").textContent = 
                    "Marked at: " + response.time + " on " + response.date;
                
                statusDiv.style.display = 'none';
                successDiv.style.display = 'flex';
                
                // Update button status
                button.innerHTML = `
                    <div class="button-icon"><i class="fas fa-check"></i></div>
                    <div class="button-text">${typeNames[checkinType]}</div>
                    <div class="button-time">✓ ${response.time}</div>
                `;
                button.disabled = true;
                button.classList.remove('pulse-animation');
                
                // Reload page after 2 seconds to update status
                setTimeout(() => {
                    location.reload();
                }, 2000);
                
            } else {
                console.log("FAILED:", response.message);
                statusDiv.className = "status-message status-error";
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + response.message;
                
                // Re-enable all buttons
                document.querySelectorAll('.attendance-button').forEach(btn => {
                    btn.disabled = false;
                });
            }
        },
        error: function(xhr, status, error) {
            console.log("=== AJAX ERROR ===");
            console.log("Status:", status);
            console.log("Error:", error);
            console.log("XHR response:", xhr.responseText);
            
            statusDiv.className = "status-message status-error";
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Network error - please try again';
            
            // Re-enable all buttons
            document.querySelectorAll('.attendance-button').forEach(btn => {
                btn.disabled = false;
            });
        }
    });
}

        // Initialize location on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Use hardcoded office coordinates
            const officeLat = 12.811393;
            const officeLon = 80.227807;
            document.getElementById("currentLat").textContent = officeLat.toFixed(6);
            document.getElementById("currentLon").textContent = officeLon.toFixed(6);
            document.getElementById("distanceInfo").innerHTML = '<span class="text-success">0 meters ✓</span>';
            
            console.log("Page loaded successfully");
        });
    </script>
</body>
</html>