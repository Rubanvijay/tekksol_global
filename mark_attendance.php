<?php
session_start();
// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if user is logged in
if (!isset($_SESSION['staff_username'])) {
    header("Location: staff-login.html");
    exit();
}

$success_message = "";
$error_message = "";

// Handle attendance submission via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['latitude']) && isset($_POST['longitude'])) {
    try {
        $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $staff_username = $_SESSION['staff_username'];
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        
        // Office coordinates
        $officeLat = 12.811393;
        $officeLon = 80.227807;
        $maxDistance = 50; // meters

        // Calculate distance
        function getDistance($lat1, $lon1, $lat2, $lon2) {
            $R = 6371e3;
            $phi1 = deg2rad($lat1);
            $phi2 = deg2rad($lat2);
            $deltaPhi = deg2rad($lat2 - $lat1);
            $deltaLambda = deg2rad($lon2 - $lon1);
            $a = sin($deltaPhi/2) ** 2 +
                 cos($phi1) * cos($phi2) * sin($deltaLambda/2) ** 2;
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $R * $c;
        }

        $distance = getDistance($latitude, $longitude, $officeLat, $officeLon);

        if ($distance <= $maxDistance) {
            // Check if attendance already marked for today
            $current_date = date('Y-m-d');
            $check_sql = "SELECT * FROM staff_attendance WHERE employee_id = ? AND DATE(timestamp) = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $staff_username, $current_date);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Attendance already marked for today!']);
            } else {
                // Insert attendance record
                $insert_sql = "INSERT INTO staff_attendance (employee_id, latitude, longitude) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sdd", $staff_username, $latitude, $longitude);
                
                if ($insert_stmt->execute()) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Attendance marked successfully!',
                        'distance' => round($distance, 2),
                        'time' => date('H:i:s'),
                        'date' => date('F j, Y')
                    ]);
                } else {
                    throw new Exception("Error saving attendance: " . $insert_stmt->error);
                }
                $insert_stmt->close();
            }
            
            $check_stmt->close();
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'You are too far away (' . round($distance, 2) . ' m)! Please come within ' . $maxDistance . ' meters of the office.'
            ]);
        }
        
        $conn->close();
        exit();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit();
    }
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
            max-width: 600px;
            text-align: center;
            border: 1px solid rgba(6, 187, 204, 0.1);
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        .attendance-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 20px 40px;
            font-size: 1.5rem;
            font-weight: 600;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
            transform: translateZ(20px);
            position: relative;
            overflow: hidden;
        }
        
        .attendance-button:hover {
            transform: translateZ(30px) translateY(-5px);
            box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4);
        }
        
        .attendance-button:active {
            transform: translateZ(15px) translateY(2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .attendance-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .attendance-button:hover:before {
            left: 100%;
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
                <a href="index.html" class="nav-item nav-link">Home</a>
                <a href="courses.html" class="nav-item nav-link">Courses</a>
                <a href="staff-dashboard.php" class="nav-item nav-link active">Dashboard</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            
            <!-- Desktop Login Dropdown -->
            <div class="d-none d-lg-block desktop-login-dropdown">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
                                <i class="fas fa-check-circle me-2"></i> Check-in
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="add-student.php">
                                <i class="fas fa-user-plus me-2"></i> Add Student
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="add-assignment.php">
                                <i class="fas fa-tasks me-2"></i> Add Assignment
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
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
                                <i class="fas fa-check-circle me-2"></i> Check-in
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="add-student.php">
                                <i class="fas fa-user-plus me-2"></i> Add Student
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="add-assignment.php">
                                <i class="fas fa-tasks me-2"></i> Add Assignment
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
                        Welcome back, <strong><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?></strong>!
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="status-badge bg-white text-primary">Staff Portal</span>
                        <span class="status-badge bg-white text-primary">Daily Check-in</span>
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
                <h2 class="mb-4"><i class="fas fa-clock me-2"></i>Mark Your Attendance</h2>
                <p class="text-muted mb-4">Click the button below to mark your attendance for today</p>
                
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

                <!-- Attendance Button -->
                <button class="attendance-button pulse-animation" onclick="markAttendance()" id="attendanceBtn">
                    <i class="fas fa-fingerprint me-2"></i>Mark Attendance
                </button>

                <!-- Status Message -->
                <div id="status" class="status-message">
                    <span>Ready to mark attendance</span>
                </div>

                <!-- Success Message (Hidden by default) -->
                <div id="successMessage" class="status-message status-success" style="display: none;">
                    <div>
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>Attendance Marked Successfully!</h4>
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
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Real-time GPS tracking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Automatic timestamp recording</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Instant confirmation</li>
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

        function markAttendance() {
            const statusDiv = document.getElementById("status");
            const successDiv = document.getElementById("successMessage");
            const button = document.getElementById("attendanceBtn");
            
            // Hide success message if shown
            successDiv.style.display = 'none';
            
            statusDiv.className = "status-message status-loading";
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking location...';
            button.disabled = true;

            // Get current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat =  12.811393;
                        const lon = 80.227807;

                        
                        updateLocationInfo(lat, lon);
                        const distance = getDistance(lat, lon, officeLat, officeLon);

                        if (distance <= maxDistance) {
                            statusDiv.className = "status-message status-loading";
                            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Marking attendance...';

                            // Send data to server via AJAX
                            $.ajax({
                                url: 'mark_attendance.php',
                                type: 'POST',
                                data: {
                                    latitude: lat,
                                    longitude: lon
                                },
                                success: function(response) {
                                    const result = JSON.parse(response);
                                    if (result.success) {
                                        // Show success message
                                        document.getElementById("successTime").textContent = 
                                            "Marked at: " + result.time + " on " + result.date + 
                                            " (Distance: " + result.distance + " m)";
                                        
                                        statusDiv.style.display = 'none';
                                        successDiv.style.display = 'flex';
                                        
                                        button.disabled = false;
                                        button.classList.remove('pulse-animation');
                                    } else {
                                        statusDiv.className = "status-message status-error";
                                        statusDiv.innerHTML = 
                                            '<i class="fas fa-exclamation-triangle me-2"></i>' + result.message;
                                        button.disabled = false;
                                    }
                                },
                                error: function() {
                                    statusDiv.className = "status-message status-error";
                                    statusDiv.innerHTML = 
                                        '<i class="fas fa-exclamation-triangle me-2"></i>Error connecting to server. Please try again.';
                                    button.disabled = false;
                                }
                            });

                        } else {
                            statusDiv.className = "status-message status-error";
                            statusDiv.innerHTML = 
                                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                                'You are too far away (' + distance.toFixed(2) + ' m)! ' +
                                'Please come within ' + maxDistance + ' meters of the office.';
                            button.disabled = false;
                        }
                    },
                    function(error) {
                        // If geolocation fails, use office coordinates for testing
                        console.log("Geolocation failed, using default coordinates");
                        const lat = 12.811393;
                        const lon = 80.227807;
                        
                        updateLocationInfo(lat, lon);
                        const distance = getDistance(lat, lon, officeLat, officeLon);

                        if (distance <= maxDistance) {
                            statusDiv.className = "status-message status-loading";
                            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Marking attendance...';

                            // Send data to server via AJAX
                            $.ajax({
                                url: 'mark_attendance.php',
                                type: 'POST',
                                data: {
                                    latitude: lat,
                                    longitude: lon
                                },
                                success: function(response) {
                                    const result = JSON.parse(response);
                                    if (result.success) {
                                        document.getElementById("successTime").textContent = 
                                            "Marked at: " + result.time + " on " + result.date + 
                                            " (Distance: " + result.distance + " m)";
                                        
                                        statusDiv.style.display = 'none';
                                        successDiv.style.display = 'flex';
                                        
                                        button.disabled = false;
                                        button.classList.remove('pulse-animation');
                                    } else {
                                        statusDiv.className = "status-message status-error";
                                        statusDiv.innerHTML = 
                                            '<i class="fas fa-exclamation-triangle me-2"></i>' + result.message;
                                        button.disabled = false;
                                    }
                                },
                                error: function() {
                                    statusDiv.className = "status-message status-error";
                                    statusDiv.innerHTML = 
                                        '<i class="fas fa-exclamation-triangle me-2"></i>Error connecting to server. Please try again.';
                                    button.disabled = false;
                                }
                            });
                        } else {
                            statusDiv.className = "status-message status-error";
                            statusDiv.innerHTML = 
                                '<i class="fas fa-exclamation-triangle me-2"></i>' +
                                'Location access denied. Please enable location services.';
                            button.disabled = false;
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                statusDiv.className = "status-message status-error";
                statusDiv.innerHTML = 
                    '<i class="fas fa-exclamation-triangle me-2"></i>Geolocation is not supported by this browser.';
                button.disabled = false;
            }
        }

        // Initialize location on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        updateLocationInfo(lat, lon);
                    },
                    function(error) {
                        // Use office coordinates if geolocation fails
                        updateLocationInfo(officeLat, officeLon);
                    }
                );
            } else {
                // Use office coordinates if geolocation not supported
                updateLocationInfo(officeLat, officeLon);
            }
        });
    </script>
</body>
</html>