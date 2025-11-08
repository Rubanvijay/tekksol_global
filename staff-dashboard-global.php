<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Staff Dashboard Global - Session data: " . print_r($_SESSION, true));

// Check if user is logged in as staff
if (!isset($_SESSION['staff_email'])) {
    error_log("No staff session found, redirecting to login");
    header("Location: staff-login.html");
    exit();
}

error_log("Staff user logged in: " . $_SESSION['staff_email']);

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$success_message = "";
$error_message = "";
$today_walkin_count = 0;

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_walkin_report'])) {
        $hr_name = $_SESSION['staff_email']; // Using email as HR name identifier
        $no_of_calls = intval($_POST['no_of_calls']);
        $tomorrow_walkin_count = intval($_POST['tomorrow_walkin_count']);
        $today_walkin_count = intval($_POST['today_walkin_count']);
        $report_date = date('Y-m-d');
        
        // Check if report already exists for today
        $check_sql = "SELECT id FROM daily_walkin_reports WHERE hr_name = ? AND report_date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $hr_name, $report_date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "You have already submitted a walk-in report for today.";
        } else {
            // Insert main report
            $insert_sql = "INSERT INTO daily_walkin_reports (hr_name, no_of_calls, tomorrow_walkin_count, today_walkin_count, report_date) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("siiis", $hr_name, $no_of_calls, $tomorrow_walkin_count, $today_walkin_count, $report_date);
            
            if ($stmt->execute()) {
                $report_id = $stmt->insert_id;
                
                // Insert walk-in details
                if ($today_walkin_count > 0 && isset($_POST['walkin_name'])) {
                    $walkin_sql = "INSERT INTO walkin_details (report_id, name, email, phone_no, location, qualification, status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $walkin_stmt = $conn->prepare($walkin_sql);
                    
                    for ($i = 0; $i < $today_walkin_count; $i++) {
                        if (!empty($_POST['walkin_name'][$i])) {
                            $name = $_POST['walkin_name'][$i];
                            $email = $_POST['walkin_email'][$i] ?? '';
                            $phone = $_POST['walkin_phone'][$i] ?? '';
                            $location = $_POST['walkin_location'][$i] ?? '';
                            $qualification = $_POST['walkin_qualification'][$i] ?? '';
                            $status = $_POST['walkin_status'][$i] ?? '';
                            
                            $walkin_stmt->bind_param("issssss", $report_id, $name, $email, $phone, $location, $qualification, $status);
                            $walkin_stmt->execute();
                        }
                    }
                    $walkin_stmt->close();
                }
                
                $success_message = "Daily walk-in report submitted successfully!";
            } else {
                $error_message = "Error submitting report: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Dashboard Global error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Staff Dashboard - Global Team - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Staff Dashboard, Tekksol Global, Management Platform" name="keywords">
    <meta content="Staff dashboard for Tekksol Global training institute" name="description">

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
        /* Login Dropdown Customization */
        #loginDropdown {
            border: none;
        }

        .dropdown-menu .dropdown-item {
            transition: all 0.3s ease;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #06BBCC;
            color: white;
            padding-left: 1.5rem;
        }

        .dropdown-menu .dropdown-item i {
            color: #06BBCC;
        }

        .dropdown-menu .dropdown-item:hover i {
            color: white;
        }
        
        /* Staff Dashboard Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .search-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        .info-card h5 {
            color: #06BBCC;
            margin-bottom: 15px;
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
        
        .status-badge {
            background: white;
            color: #06BBCC;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .quick-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .quick-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #06BBCC;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .search-bar {
            position: relative;
        }
        
        .search-bar input {
            border-radius: 50px;
            padding: 15px 60px 15px 25px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .search-bar button {
            position: absolute;
            right: 5px;
            top: 5px;
            border-radius: 50px;
            padding: 10px 25px;
        }
        
        .search-results {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .student-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .student-card:hover {
            border-color: #06BBCC;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        
        .student-card h6 {
            color: #06BBCC;
            margin-bottom: 5px;
        }
        
        .student-info {
            font-size: 0.9rem;
            color: #666;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #06BBCC;
            color: #06BBCC;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            display: block;
            text-decoration: none;
            margin-bottom: 15px;
        }
        
        .action-btn:hover {
            background: #06BBCC;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .recent-students-list {
            list-style: none;
            padding: 0;
        }
        
        .recent-students-list li {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        .recent-students-list li:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        
        .status-completed {
            color: #17a2b8;
            font-weight: 600;
        }
        
        .course-badge {
            background: #e9ecef;
            color: #495057;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .trainer-info {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Global Team Specific Styles */
        .walkin-form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .walkin-details-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .walkin-person-form {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        .form-section-title {
            color: #06BBCC;
            border-bottom: 2px solid #06BBCC;
            padding-bottom: 10px;
            margin-bottom: 20px;
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
                 <a href="staff-dashboard-global.php" class="nav-item nav-link">Dashboard</a>
                <a href="mark_attendance.php" class="nav-item nav-link">Checkin</a>
                <a href="edit_walkin_report.php" class="nav-item nav-link ">Edit Report</a>
                <a href="request_leave_approval.php" class="nav-item nav-link">Leave Request</a>
              
                
            </div>
            
            <!-- Desktop Login Dropdown -->
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
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
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
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
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
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 text-white mb-3">Global Team Dashboard</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php 
                          $email = $_SESSION['staff_email'] ?? '';
                          $username = $email ? explode('@', $email)[0] : 'Staff';
                          echo htmlspecialchars($username); 
                        ?></strong>!
                    </p>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="status-badge me-3">Global Team HR</span>
                        <span class="status-badge me-3" style="background: #28a745; color: white;">
                            <i class="fas fa-calendar-day me-1"></i>Daily Walk-in Report
                        </span>
                        <span class="status-badge" style="background: #17a2b8; color: white;">
                            <i class="fas fa-clock me-1"></i><?php echo date('F j, Y'); ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-globe-americas"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Daily Walk-in Report Form -->
                <div class="col-lg-8 mb-4">
                    <div class="walkin-form-container">
                        <h3 class="form-section-title"><i class="fas fa-walking me-2"></i>Daily Walk-in Report</h3>
                        <p class="text-muted mb-4">Please fill out your daily walk-in report before leaving the office.</p>
                        
                        <form method="POST" action="" id="walkinReportForm">
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="no_of_calls" class="form-label"><strong>No. of Calls Today</strong></label>
                                        <input type="number" class="form-control" id="no_of_calls" name="no_of_calls" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tomorrow_walkin_count" class="form-label"><strong>Tomorrow Walk-in Count</strong></label>
                                        <input type="number" class="form-control" id="tomorrow_walkin_count" name="tomorrow_walkin_count" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="today_walkin_count" class="form-label"><strong>Today Walk-in Count</strong></label>
                                        <input type="number" class="form-control" id="today_walkin_count" name="today_walkin_count" min="0" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="walkinDetailsContainer" class="walkin-details-container" style="display: none;">
                                <h5 class="mb-3">Walk-in Details</h5>
                                <div id="walkinFormsContainer">
                                    <!-- Walk-in forms will be dynamically added here -->
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" name="submit_walkin_report" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Daily Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="info-card">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        
                        <a href="mark_attendance.php" class="action-btn">
                            <i class="fas fa-check-circle"></i>
                            <strong>Staff Check-in</strong>
                            <small class="d-block text-muted mt-2">Mark daily attendance</small>
                        </a>

                        <a href="edit_walkin_report.php" class="action-btn">
                            <i class="fas fa-calendar-check"></i>
                            <strong>Edit Report</strong>
                            <small class="d-block text-muted mt-2">Edit Walkin Report</small>
                        </a>

                        <a href="request_leave_approval.php" class="action-btn">
                            <i class="fas fa-calendar-check"></i>
                            <strong>Request Leave</strong>
                            <small class="d-block text-muted mt-2">Request for Leave Approval</small>
                        </a>
                       <a href="logout.php" class="action-btn">
    <i class="fas fa-sign-out-alt"></i>
    <strong>Logout</strong>
    <small class="d-block text-muted mt-2">Logout from your account</small>
</a>
                
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
                    <a class="btn btn-link" href="staff-checkin.php">Student Check-in</a>
                    <a class="btn btn-link" href="add-student.php">Add Student</a>
                    <a class="btn btn-link" href="add-assignment.php">Add Assignment</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help? Contact technical support</p>
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
        document.addEventListener('DOMContentLoaded', function() {
            const todayWalkinCountInput = document.getElementById('today_walkin_count');
            const walkinDetailsContainer = document.getElementById('walkinDetailsContainer');
            const walkinFormsContainer = document.getElementById('walkinFormsContainer');
            
            todayWalkinCountInput.addEventListener('change', function() {
                const count = parseInt(this.value);
                
                if (count > 0) {
                    walkinDetailsContainer.style.display = 'block';
                    walkinFormsContainer.innerHTML = '';
                    
                    for (let i = 0; i < count; i++) {
                        const formHtml = `
                            <div class="walkin-person-form">
                                <h6 class="mb-3">Walk-in Person ${i+1}</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_name_${i}" class="form-label">Name *</label>
                                            <input type="text" class="form-control" id="walkin_name_${i}" name="walkin_name[]" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_email_${i}" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="walkin_email_${i}" name="walkin_email[]">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_phone_${i}" class="form-label">Phone No</label>
                                            <input type="text" class="form-control" id="walkin_phone_${i}" name="walkin_phone[]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_location_${i}" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="walkin_location_${i}" name="walkin_location[]">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_qualification_${i}" class="form-label">Qualification</label>
                                            <input type="text" class="form-control" id="walkin_qualification_${i}" name="walkin_qualification[]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="walkin_status_${i}" class="form-label">Status</label>
                                            <input type="text" class="form-control" id="walkin_status_${i}" name="walkin_status[]" placeholder="e.g., Interested, Follow-up, etc.">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        walkinFormsContainer.innerHTML += formHtml;
                    }
                } else {
                    walkinDetailsContainer.style.display = 'none';
                    walkinFormsContainer.innerHTML = '';
                }
            });
        });
    </script>
</body>

</html>