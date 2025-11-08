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
    <title>Global Team Dashboard - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Global Team Dashboard, Tekksol Global, Walk-in Reports" name="keywords">
    <meta content="Global Team dashboard for Tekksol Global - Daily walk-in reporting system" name="description">

    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #06BBCC;
            --primary-dark: #0596a3;
            --secondary-color: #667eea;
            --accent-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-dark: #2d3748;
            --text-light: #718096;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --shadow-hover: 0 15px 40px rgba(0,0,0,0.15);
            --border-radius: 16px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Mobile Login Dropdown */
        .mobile-login-dropdown {
            display: none;
        }

        @media (max-width: 991px) {
            .desktop-login-dropdown {
                display: none;
            }
            
            .mobile-login-dropdown {
                display: block;
                padding: 15px;
                border-top: 1px solid rgba(255,255,255,0.1);
            }
        }

        /* Enhanced Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .navbar-brand img {
            transition: var(--transition);
        }

        .navbar-brand:hover img {
            transform: scale(1.05);
        }

        .nav-item {
            margin: 0 8px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            padding: 10px 20px !important;
            border-radius: 25px;
            transition: var(--transition);
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white !important;
            transform: translateY(-2px);
        }

        /* Modern Dashboard Header */
        .dashboard-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            color: white;
            padding: 60px 0 40px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="1000,0 1000,100 0,100"></polygon></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow);
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .status-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            margin: 5px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        /* Main Content Area */
        .main-content {
            margin-top: -40px;
            position: relative;
            z-index: 3;
        }

        .content-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            border: none;
            transition: var(--transition);
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 20px 30px;
            border: none;
        }

        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 1rem;
            transition: var(--transition);
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(6, 187, 204, 0.1);
            background: white;
        }

        /* Walk-in Forms */
        .walkin-forms-container {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }

        .walkin-person-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: var(--transition);
        }

        .walkin-person-card:hover {
            transform: translateX(5px);
        }

        .person-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }

        .person-number {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }

        .action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            transition: var(--transition);
            border: 2px solid transparent;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            color: var(--text-dark);
            box-shadow: var(--shadow-hover);
        }

        .action-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            color: white;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(6, 187, 204, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(6, 187, 204, 0.4);
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary-color));
        }

        .btn-lg {
            padding: 15px 40px;
            font-size: 1.1rem;
        }

        /* Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, var(--dark-bg), #2d3748);
            color: white;
            margin-top: 80px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-hero {
                padding: 40px 0 30px;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .content-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .action-card {
                padding: 20px;
            }
            
            .walkin-person-card {
                padding: 20px;
            }
            
            .form-control {
                padding: 10px 15px;
            }
        }

        @media (max-width: 576px) {
            .nav-link {
                padding: 8px 15px !important;
                font-size: 0.9rem;
            }
            
            .dashboard-hero {
                padding: 30px 0 20px;
            }
            
            .content-card {
                padding: 15px;
            }
            
            .btn-lg {
                padding: 12px 25px;
                font-size: 1rem;
            }
            
            .profile-avatar {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-left {
            animation: slideInLeft 0.6s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>
    <!-- Spinner -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <!-- Enhanced Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light shadow">
        <div class="container">
            <a href="index.html" class="navbar-brand d-flex align-items-center">
                <img src="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" alt="Tekksol Global Logo" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a href="index.html" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="courses.html" class="nav-link">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a href="staff-dashboard.php" class="nav-link">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="staff-dashboard-global.php" class="nav-link active">Global Team</a>
                    </li>
                    <li class="nav-item">
                        <a href="contact.html" class="nav-link">Contact</a>
                    </li>
                </ul>
                
                <!-- Desktop Login Dropdown -->
                <div class="d-none d-lg-block desktop-login-dropdown ms-3">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-user-tie me-2"></i><?php 
                                $email = $_SESSION['staff_email'] ?? '';
                                $username = $email ? explode('@', $email)[0] : 'Staff';
                                echo htmlspecialchars($username); 
                            ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="staff-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="mark_attendance.php"><i class="fas fa-check-circle me-2"></i>Check-in</a></li>
                            <li><a class="dropdown-item" href="add-student.php"><i class="fas fa-user-plus me-2"></i>Add Student</a></li>
                            <li><a class="dropdown-item" href="add-assignment.php"><i class="fas fa-tasks me-2"></i>Add Assignment</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Login Dropdown -->
    <div class="mobile-login-dropdown d-lg-none">
        <div class="container">
            <div class="dropdown">
                <button class="btn btn-primary w-100 dropdown-toggle" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-user-tie me-2"></i><?php 
                        $email = $_SESSION['staff_email'] ?? '';
                        $username = $email ? explode('@', $email)[0] : 'Staff';
                        echo htmlspecialchars($username); 
                    ?>
                </button>
                <ul class="dropdown-menu w-100">
                    <li><a class="dropdown-item" href="staff-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                    <li><a class="dropdown-item" href="mark_attendance.php"><i class="fas fa-check-circle me-2"></i>Check-in</a></li>
                    <li><a class="dropdown-item" href="add-student.php"><i class="fas fa-user-plus me-2"></i>Add Student</a></li>
                    <li><a class="dropdown-item" href="add-assignment.php"><i class="fas fa-tasks me-2"></i>Add Assignment</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modern Dashboard Hero -->
    <div class="dashboard-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="welcome-card fade-in">
                        <h1 class="display-5 fw-bold mb-3">Global Team Dashboard</h1>
                        <p class="lead mb-4">
                            Welcome back, <strong class="text-warning"><?php 
                                $email = $_SESSION['staff_email'] ?? '';
                                $username = $email ? explode('@', $email)[0] : 'Staff';
                                echo htmlspecialchars($username); 
                            ?></strong>! Ready to track today's progress?
                        </p>
                        <div class="d-flex flex-wrap">
                            <span class="status-badge">
                                <i class="fas fa-globe-americas me-2"></i>Global Team HR
                            </span>
                            <span class="status-badge">
                                <i class="fas fa-calendar-day me-2"></i><?php echo date('F j, Y'); ?>
                            </span>
                            <span class="status-badge pulse">
                                <i class="fas fa-walking me-2"></i>Daily Walk-in Report
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Alerts -->
            <?php if ($success_message): ?>
                <div class="alert alert-success fade-in" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger fade-in" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Daily Walk-in Report Form -->
                <div class="col-lg-8">
                    <div class="content-card slide-in-left">
                        <h3 class="section-title">
                            <i class="fas fa-walking me-2 text-primary"></i>
                            Daily Walk-in Report
                        </h3>
                        <p class="text-muted mb-4">Complete your daily walk-in report before leaving the office to track progress and plan for tomorrow.</p>
                        
                        <form method="POST" action="" id="walkinReportForm">
                            <!-- Statistics Input -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <div class="form-group text-center">
                                        <label for="no_of_calls" class="form-label">
                                            <i class="fas fa-phone text-primary me-2"></i>Today's Calls
                                        </label>
                                        <input type="number" class="form-control text-center" id="no_of_calls" name="no_of_calls" min="0" required placeholder="0">
                                        <small class="text-muted">Total calls made today</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group text-center">
                                        <label for="tomorrow_walkin_count" class="form-label">
                                            <i class="fas fa-calendar-plus text-warning me-2"></i>Tomorrow's Expected
                                        </label>
                                        <input type="number" class="form-control text-center" id="tomorrow_walkin_count" name="tomorrow_walkin_count" min="0" required placeholder="0">
                                        <small class="text-muted">Expected walk-ins tomorrow</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group text-center">
                                        <label for="today_walkin_count" class="form-label">
                                            <i class="fas fa-users text-success me-2"></i>Today's Walk-ins
                                        </label>
                                        <input type="number" class="form-control text-center" id="today_walkin_count" name="today_walkin_count" min="0" required placeholder="0">
                                        <small class="text-muted">Actual walk-ins today</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dynamic Walk-in Details -->
                            <div id="walkinDetailsContainer" class="walkin-forms-container" style="display: none;">
                                <h5 class="mb-4">
                                    <i class="fas fa-list-alt me-2 text-primary"></i>
                                    Walk-in Details
                                </h5>
                                <div id="walkinFormsContainer">
                                    <!-- Walk-in forms will be dynamically added here -->
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="text-center mt-4">
                                <button type="submit" name="submit_walkin_report" class="btn btn-primary btn-lg pulse">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Daily Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="content-card">
                        <h3 class="section-title">
                            <i class="fas fa-bolt me-2 text-warning"></i>
                            Quick Actions
                        </h3>
                        <div class="quick-actions-grid">
                            <a href="mark_attendance.php" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h6 class="fw-bold">Staff Check-in</h6>
                                <small class="text-muted">Mark daily attendance</small>
                            </a>

                            <a href="edit_walkin_report.php" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h6 class="fw-bold">Edit Report</h6>
                                <small class="text-muted">Update walk-in reports</small>
                            </a>

                            <a href="request_leave_approval.php" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h6 class="fw-bold">Request Leave</h6>
                                <small class="text-muted">Leave approval requests</small>
                            </a>

                            <a href="staff-dashboard.php" class="action-card">
                                <div class="action-icon">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <h6 class="fw-bold">Main Dashboard</h6>
                                <small class="text-muted">Back to main dashboard</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer pt-5 mt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Links</h4>
                    <a class="btn btn-link" href="about.html">About Us</a>
                    <a class="btn btn-link" href="contact.html">Contact Us</a>
                    <a class="btn btn-link" href="privacy-policy.html">Privacy Policy</a>
                    <a class="btn btn-link" href="terms-condition.html">Terms & Conditions</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact Info</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Tekksol Global, OMR, Chennai</p>
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
                    <a class="btn btn-link" href="staff-dashboard-global.php">Global Team</a>
                    <a class="btn btn-link" href="edit_walkin_report.php">Edit Reports</a>
                    <a class="btn btn-link" href="add-student.php">Add Student</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support Center</h4>
                    <p>Need technical assistance? Our support team is here to help you.</p>
                    <a href="contact.html" class="btn btn-primary w-100">Get Support</a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright text-center py-4">
                <div class="row">
                    <div class="col-12">
                        &copy; <a class="text-warning" href="#">Tekksol Global</a>, All Rights Reserved 2024.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top">
        <i class="bi bi-arrow-up"></i>
    </a>

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
                            <div class="walkin-person-card fade-in">
                                <div class="person-header">
                                    <div class="person-number">${i+1}</div>
                                    <h6 class="mb-0">Walk-in Person Details</h6>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_name_${i}" class="form-label">Full Name *</label>
                                            <input type="text" class="form-control" id="walkin_name_${i}" name="walkin_name[]" required placeholder="Enter full name">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_email_${i}" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="walkin_email_${i}" name="walkin_email[]" placeholder="email@example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_phone_${i}" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="walkin_phone_${i}" name="walkin_phone[]" placeholder="+91 XXXXX XXXXX">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_location_${i}" class="form-label">Location</label>
                                            <input type="text" class="form-control" id="walkin_location_${i}" name="walkin_location[]" placeholder="City, Area">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_qualification_${i}" class="form-label">Qualification</label>
                                            <input type="text" class="form-control" id="walkin_qualification_${i}" name="walkin_qualification[]" placeholder="Highest qualification">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="walkin_status_${i}" class="form-label">Status</label>
                                            <input type="text" class="form-control" id="walkin_status_${i}" name="walkin_status[]" placeholder="Interested, Follow-up, etc.">
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

            // Add some interactive animations
            const cards = document.querySelectorAll('.content-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>