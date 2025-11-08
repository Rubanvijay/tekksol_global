<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Edit Walk-in Report - Session data: " . print_r($_SESSION, true));

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
$reports = [];
$selected_report = null;
$walkin_details = [];
$selected_date = date('Y-m-d');

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $hr_name = $_SESSION['staff_email'];
    
    // Get all reports for this HR
    $reports_sql = "SELECT * FROM daily_walkin_reports WHERE hr_name = ? ORDER BY report_date DESC";
    $reports_stmt = $conn->prepare($reports_sql);
    $reports_stmt->bind_param("s", $hr_name);
    $reports_stmt->execute();
    $reports_result = $reports_stmt->get_result();
    
    while ($row = $reports_result->fetch_assoc()) {
        $reports[] = $row;
    }
    $reports_stmt->close();
    
    // Handle date selection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_date'])) {
        $selected_date = $_POST['report_date'];
        
        // Get report for selected date
        $report_sql = "SELECT * FROM daily_walkin_reports WHERE hr_name = ? AND report_date = ?";
        $report_stmt = $conn->prepare($report_sql);
        $report_stmt->bind_param("ss", $hr_name, $selected_date);
        $report_stmt->execute();
        $report_result = $report_stmt->get_result();
        
        if ($report_result->num_rows > 0) {
            $selected_report = $report_result->fetch_assoc();
            
            // Get walk-in details for this report
            $details_sql = "SELECT * FROM walkin_details WHERE report_id = ?";
            $details_stmt = $conn->prepare($details_sql);
            $details_stmt->bind_param("i", $selected_report['id']);
            $details_stmt->execute();
            $details_result = $details_stmt->get_result();
            
            while ($detail = $details_result->fetch_assoc()) {
                $walkin_details[] = $detail;
            }
            $details_stmt->close();
        }
        $report_stmt->close();
    }
    
    // Handle form submission for editing report
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_walkin_report'])) {
        $report_id = intval($_POST['report_id']);
        $no_of_calls = intval($_POST['no_of_calls']);
        $tomorrow_walkin_count = intval($_POST['tomorrow_walkin_count']);
        $today_walkin_count = intval($_POST['today_walkin_count']);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update main report
            $update_sql = "UPDATE daily_walkin_reports SET no_of_calls = ?, tomorrow_walkin_count = ?, today_walkin_count = ? WHERE id = ? AND hr_name = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("iiiss", $no_of_calls, $tomorrow_walkin_count, $today_walkin_count, $report_id, $hr_name);
            
            if ($update_stmt->execute()) {
                // Delete existing walk-in details
                $delete_sql = "DELETE FROM walkin_details WHERE report_id = ?";
                $delete_stmt = $conn->prepare($delete_sql);
                $delete_stmt->bind_param("i", $report_id);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Insert updated walk-in details
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
                
                $conn->commit();
                $success_message = "Walk-in report updated successfully!";
                
                // Refresh the selected report data
                $selected_date = $_POST['report_date'];
                $report_sql = "SELECT * FROM daily_walkin_reports WHERE id = ?";
                $report_stmt = $conn->prepare($report_sql);
                $report_stmt->bind_param("i", $report_id);
                $report_stmt->execute();
                $report_result = $report_stmt->get_result();
                $selected_report = $report_result->fetch_assoc();
                $report_stmt->close();
                
                // Refresh walk-in details
                $walkin_details = [];
                $details_sql = "SELECT * FROM walkin_details WHERE report_id = ?";
                $details_stmt = $conn->prepare($details_sql);
                $details_stmt->bind_param("i", $report_id);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                
                while ($detail = $details_result->fetch_assoc()) {
                    $walkin_details[] = $detail;
                }
                $details_stmt->close();
                
            } else {
                throw new Exception("Error updating report: " . $update_stmt->error);
            }
            $update_stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating report: " . $e->getMessage();
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("Edit Walk-in Report error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Walk-in Report - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Edit Walk-in Report, Tekksol Global, Management Platform" name="keywords">
    <meta content="Edit daily walk-in reports for Tekksol Global" name="description">

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
        
        /* Edit Report Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .date-selection-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
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
            text-align: center;
        }
        
        .report-history-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .report-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .report-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .report-item.active {
            background: #e3f2fd;
            border-left: 4px solid #06BBCC;
        }
        
        .no-report-message {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-report-message i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .stats-summary {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .stat-item {
            margin: 10px 0;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 0;
            }
            
            .dashboard-header h1 {
                font-size: 1.8rem;
            }
            
            .date-selection-card,
            .walkin-form-container {
                padding: 20px;
                margin: 15px;
            }
            
            .walkin-person-form {
                padding: 15px;
            }
            
            .form-section-title {
                font-size: 1.3rem;
            }
            
            .stats-summary {
                padding: 15px;
                margin: 15px;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .btn-lg {
                padding: 12px 24px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .welcome-text {
                font-size: 1rem;
            }
            
            .status-badge {
                font-size: 0.8rem;
                padding: 4px 12px;
                margin: 2px;
            }
            
            .date-selection-card,
            .walkin-form-container {
                padding: 15px;
                margin: 10px;
            }
            
            .walkin-details-container {
                padding: 15px;
            }
            
            .walkin-person-form {
                padding: 12px;
            }
            
            .form-control {
                font-size: 14px;
            }
        }
        
        /* Centered Layout */
        .centered-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
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
                                <i class="fas fa-globe-americas me-2"></i> Checkin
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="edit_walkin_report.php">
                                <i class="fas fa-edit me-2"></i> Edit Report
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
                                <i class="fas fa-globe-americas me-2"></i> Checkin
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="edit_walkin_report.php">
                                <i class="fas fa-edit me-2"></i> Edit Report
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
            <div class="centered-content">
                <h1 class="display-5 text-white mb-3">Edit Walk-in Report</h1>
                <p class="welcome-text text-white mb-4">
                    Welcome back, <strong><?php 
                      $email = $_SESSION['staff_email'] ?? '';
                      $username = $email ? explode('@', $email)[0] : 'Staff';
                      echo htmlspecialchars($username); 
                    ?></strong>!
                </p>
                <div class="d-flex align-items-center flex-wrap justify-content-center">
                    <span class="status-badge me-2 mb-2">Global Team HR</span>
                    <span class="status-badge me-2 mb-2" style="background: #28a745; color: white;">
                        <i class="fas fa-edit me-1"></i>Edit Previous Reports
                    </span>
                    <span class="status-badge mb-2" style="background: #17a2b8; color: white;">
                        <i class="fas fa-clock me-1"></i><?php echo date('F j, Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="container">
        <div class="nav-buttons">
            <a href="staff-dashboard-global.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>New Report
            </a>
            <a href="staff-dashboard-global.php" class="btn btn-outline-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Main Dashboard
            </a>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-xxl py-3">
        <div class="main-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success text-center"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="centered-content">
                
                <!-- Date Selection Section -->
                <div class="date-selection-card" style="width: 100%; max-width: 600px;">
                    <h3 class="form-section-title">
                        <i class="fas fa-calendar-alt me-2"></i>Select Report Date
                    </h3>
                    
                    <form method="POST" action="" class="mb-4">
                        <div class="form-group">
                            <label for="report_date" class="form-label"><strong>Choose Date</strong></label>
                            <input type="date" 
                                   class="form-control form-control-lg text-center" 
                                   id="report_date" 
                                   name="report_date" 
                                   max="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo htmlspecialchars($selected_date); ?>"
                                   required
                                   style="font-size: 1.1rem;">
                        </div>
                        <button type="submit" name="select_date" class="btn btn-primary btn-lg w-100 mt-3">
                            <i class="fas fa-search me-2"></i>Load Report
                        </button>
                    </form>
                    
                    <?php if ($selected_report): ?>
                    <div class="stats-summary">
                        <h5 class="mb-3">Report Summary</h5>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $selected_report['no_of_calls']; ?></span>
                                    <span class="stat-label">Calls</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $selected_report['today_walkin_count']; ?></span>
                                    <span class="stat-label">Today Walk-ins</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $selected_report['tomorrow_walkin_count']; ?></span>
                                    <span class="stat-label">Tomorrow Expected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Report History -->
                    <div class="mt-4">
                        <h6 class="mb-3 text-center">
                            <i class="fas fa-history me-2"></i>Your Report History
                        </h6>
                        <div class="report-history-card">
                            <?php if (!empty($reports)): ?>
                                <?php foreach ($reports as $report): ?>
                                    <div class="report-item <?php echo ($report['report_date'] == $selected_date) ? 'active' : ''; ?>"
                                         onclick="document.getElementById('report_date').value='<?php echo $report['report_date']; ?>'; document.forms[0].submit();">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="text-start">
                                                <strong><?php echo date('M j, Y', strtotime($report['report_date'])); ?></strong>
                                                <div class="small text-muted">
                                                    <i class="fas fa-phone me-1"></i><?php echo $report['no_of_calls']; ?> calls • 
                                                    <i class="fas fa-walking me-1"></i><?php echo $report['today_walkin_count']; ?> walk-ins
                                                </div>
                                            </div>
                                            <i class="fas fa-chevron-right text-muted"></i>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-report-message">
                                    <i class="fas fa-file-alt fa-2x"></i>
                                    <p class="mt-2">No reports found</p>
                                    <small class="text-muted">Start by creating a new report</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Edit Report Form -->
                <?php if ($selected_report): ?>
                    <div class="walkin-form-container" style="width: 100%; max-width: 800px;">
                        <h3 class="form-section-title">
                            <i class="fas fa-edit me-2"></i>
                            Edit Report for <?php echo date('F j, Y', strtotime($selected_report['report_date'])); ?>
                        </h3>
                        
                        <form method="POST" action="" id="editWalkinReportForm">
                            <input type="hidden" name="report_id" value="<?php echo $selected_report['id']; ?>">
                            <input type="hidden" name="report_date" value="<?php echo $selected_report['report_date']; ?>">
                            
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="form-group text-center">
                                        <label for="no_of_calls" class="form-label"><strong>No. of Calls Today</strong></label>
                                        <input type="number" 
                                               class="form-control text-center" 
                                               id="no_of_calls" 
                                               name="no_of_calls" 
                                               min="0" 
                                               value="<?php echo htmlspecialchars($selected_report['no_of_calls']); ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-group text-center">
                                        <label for="tomorrow_walkin_count" class="form-label"><strong>Tomorrow Walk-in Count</strong></label>
                                        <input type="number" 
                                               class="form-control text-center" 
                                               id="tomorrow_walkin_count" 
                                               name="tomorrow_walkin_count" 
                                               min="0" 
                                               value="<?php echo htmlspecialchars($selected_report['tomorrow_walkin_count']); ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-group text-center">
                                        <label for="today_walkin_count" class="form-label"><strong>Today Walk-in Count</strong></label>
                                        <input type="number" 
                                               class="form-control text-center" 
                                               id="today_walkin_count" 
                                               name="today_walkin_count" 
                                               min="0" 
                                               value="<?php echo htmlspecialchars($selected_report['today_walkin_count']); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="walkinDetailsContainer" class="walkin-details-container">
                                <h5 class="mb-3 text-center">Walk-in Details</h5>
                                <div id="walkinFormsContainer">
                                    <?php if (!empty($walkin_details)): ?>
                                        <?php foreach ($walkin_details as $index => $detail): ?>
                                            <div class="walkin-person-form">
                                                <h6 class="mb-3 text-center">Walk-in Person <?php echo $index + 1; ?></h6>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_name_<?php echo $index; ?>" class="form-label">Name *</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="walkin_name_<?php echo $index; ?>" 
                                                                   name="walkin_name[]" 
                                                                   value="<?php echo htmlspecialchars($detail['name']); ?>"
                                                                   required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_email_<?php echo $index; ?>" class="form-label">Email</label>
                                                            <input type="email" 
                                                                   class="form-control" 
                                                                   id="walkin_email_<?php echo $index; ?>" 
                                                                   name="walkin_email[]" 
                                                                   value="<?php echo htmlspecialchars($detail['email']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_phone_<?php echo $index; ?>" class="form-label">Phone No</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="walkin_phone_<?php echo $index; ?>" 
                                                                   name="walkin_phone[]" 
                                                                   value="<?php echo htmlspecialchars($detail['phone_no']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_location_<?php echo $index; ?>" class="form-label">Location</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="walkin_location_<?php echo $index; ?>" 
                                                                   name="walkin_location[]" 
                                                                   value="<?php echo htmlspecialchars($detail['location']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_qualification_<?php echo $index; ?>" class="form-label">Qualification</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="walkin_qualification_<?php echo $index; ?>" 
                                                                   name="walkin_qualification[]" 
                                                                   value="<?php echo htmlspecialchars($detail['qualification']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="form-group">
                                                            <label for="walkin_status_<?php echo $index; ?>" class="form-label">Status</label>
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="walkin_status_<?php echo $index; ?>" 
                                                                   name="walkin_status[]" 
                                                                   value="<?php echo htmlspecialchars($detail['status']); ?>"
                                                                   placeholder="e.g., Interested, Follow-up, etc.">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info text-center">
                                            <i class="fas fa-info-circle me-2"></i>No walk-in details found for this report.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_walkin_report" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Update Report
                                </button>
                                <a href="staff-dashboard-global.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="walkin-form-container" style="width: 100%; max-width: 600px;">
                        <div class="no-report-message">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4>No Report Found</h4>
                            <p class="text-muted">Select a date to load and edit your walk-in report.</p>
                            <p class="text-muted">You can only edit reports for dates up to today.</p>
                        </div>
                    </div>
                <?php endif; ?>
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
                    <a class="btn btn-link" href="staff-dashboard-global.php">Global Team</a>
                    <a class="btn btn-link" href="edit_walkin_report.php">Edit Reports</a>
                    <a class="btn btn-link" href="add-student.php">Add Student</a>
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
            // Set max date to today for date picker
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('report_date').setAttribute('max', today);
            
            // Handle dynamic form generation when today_walkin_count changes
            const todayWalkinCountInput = document.getElementById('today_walkin_count');
            const walkinFormsContainer = document.getElementById('walkinFormsContainer');
            
            if (todayWalkinCountInput) {
                todayWalkinCountInput.addEventListener('change', function() {
                    const count = parseInt(this.value);
                    
                    if (count > 0) {
                        walkinFormsContainer.innerHTML = '';
                        
                        for (let i = 0; i < count; i++) {
                            const formHtml = `
                                <div class="walkin-person-form">
                                    <h6 class="mb-3 text-center">Walk-in Person ${i+1}</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="walkin_name_${i}" class="form-label">Name *</label>
                                                <input type="text" class="form-control" id="walkin_name_${i}" name="walkin_name[]" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="walkin_email_${i}" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="walkin_email_${i}" name="walkin_email[]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="walkin_phone_${i}" class="form-label">Phone No</label>
                                                <input type="text" class="form-control" id="walkin_phone_${i}" name="walkin_phone[]">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="walkin_location_${i}" class="form-label">Location</label>
                                                <input type="text" class="form-control" id="walkin_location_${i}" name="walkin_location[]">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="walkin_qualification_${i}" class="form-label">Qualification</label>
                                                <input type="text" class="form-control" id="walkin_qualification_${i}" name="walkin_qualification[]">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
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
                        walkinFormsContainer.innerHTML = '<div class="alert alert-info text-center"><i class="fas fa-info-circle me-2"></i>No walk-in details to display.</div>';
                    }
                });
            }
        });
    </script>
</body>

</html>