<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Request Leave Approval - Session data: " . print_r($_SESSION, true));

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

$staff_email = $_SESSION['staff_email'];
$success = "";
$error = "";
$leave_history = [];
$current_month_leaves = [];

// Create staff_leave_request table if it doesn't exist
try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create table if not exists
    $create_table_sql = "CREATE TABLE IF NOT EXISTS staff_leave_request (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_email VARCHAR(255) NOT NULL,
        leave_dates TEXT NOT NULL,
        leave_reason TEXT NOT NULL,
        request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_notes TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($create_table_sql)) {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave_request'])) {
        $leave_dates = isset($_POST['leave_dates']) ? $_POST['leave_dates'] : '';
        $leave_reason = isset($_POST['leave_reason']) ? trim($_POST['leave_reason']) : '';
        
        // Validation
        if (empty($leave_dates)) {
            $error = "Please select at least one leave date.";
        } elseif (empty($leave_reason)) {
            $error = "Please provide a reason for your leave.";
        } else {
            // Insert leave request
            $sql = "INSERT INTO staff_leave_request (staff_email, leave_dates, leave_reason) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $staff_email, $leave_dates, $leave_reason);
            
            if ($stmt->execute()) {
                $success = "Leave request submitted successfully! Your request is pending approval.";
                // Clear form
                $_POST = array();
            } else {
                $error = "Error submitting leave request: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // Get leave history for this staff member
    $sql = "SELECT * FROM staff_leave_request WHERE staff_email = ? ORDER BY request_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $staff_email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $leave_history[] = $row;
    }
    $stmt->close();
    
    // Get current month leaves
    $current_month = date('Y-m');
    $sql = "SELECT * FROM staff_leave_request WHERE staff_email = ? AND DATE_FORMAT(request_date, '%Y-%m') = ? ORDER BY request_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $staff_email, $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $current_month_leaves[] = $row;
    }
    $stmt->close();
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Leave request error: " . $e->getMessage());
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'approved':
            return 'status-approved';
        case 'rejected':
            return 'status-rejected';
        default:
            return 'status-pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Request Leave Approval - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Leave Request, Tekksol Global, Staff Portal" name="keywords">
    <meta content="Request leave approval for Tekksol Global staff" name="description">

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
    /* Improved calendar styling */
    .calendar-container {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1050;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        padding: 15px;
        width: 300px;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        gap: 10px;
    }

    .calendar-close-btn {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #06BBCC;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .calendar-close-btn:hover {
        background-color: #f8f9fa;
    }

    .calendar-day {
        text-align: center;
        padding: 8px;
        border-radius: 5px;
        cursor: pointer;
        min-height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .calendar-day.empty {
        background: transparent;
        cursor: default;
        border: none;
    }

    .calendar-day:not(.empty):not(.disabled):hover {
        background: #e9ecef;
        transform: scale(1.05);
        border-color: #06BBCC;
    }

    .calendar-day.selected {
        background: #06BBCC !important;
        color: white !important;
        font-weight: bold;
        border-color: #0596a3;
    }

    .calendar-day.disabled {
        color: #ccc;
        cursor: not-allowed;
        background: #ffe6e6;
        border-color: #ffcccc;
    }

    .calendar-day.sunday {
        color: #dc3545;
        background: #ffe6e6;
        border-color: #ffcccc;
    }

    /* Make the date input more prominent when calendar is open */
    .date-picker-container input:focus {
        border-color: #06BBCC;
        box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
    }
</style>
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
        
        /* Leave Request Styles */
        .page-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .leave-form-card {
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
        
        .form-control:disabled {
            background-color: #f8f9fa;
            opacity: 1;
        }
        
        .date-picker-container {
            position: relative;
        }
        
        .selected-dates {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            min-height: 50px;
        }
        
        .date-badge {
            background: #06BBCC;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            margin: 3px;
            display: inline-block;
            font-size: 0.9rem;
        }
        
        .date-badge .remove-date {
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .calendar-container {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 15px;
            width: 300px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .calendar-day {
            text-align: center;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .calendar-day:hover {
            background: #e9ecef;
        }
        
        .calendar-day.selected {
            background: #06BBCC;
            color: white;
        }
        
        .calendar-day.disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        .calendar-day.sunday {
            color: #dc3545;
            background: #ffe6e6;
        }
        
        .calendar-day.sunday.disabled {
            background: #ffcccc;
            color: #a6a6a6;
        }
        
        .calendar-nav {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #06BBCC;
        }
        
        .btn-primary {
            background: #06BBCC;
            border-color: #06BBCC;
            padding: 12px 30px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #0596a3;
            border-color: #0596a3;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d1edff;
            color: #004085;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Leave History Styles */
        .leave-history-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leave-history-table th,
        .leave-history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .leave-history-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .leave-dates-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leave-dates-list li {
            padding: 2px 0;
            font-size: 0.9rem;
        }
        
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #06BBCC;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 12px 25px;
        }
        
        .nav-tabs .nav-link.active {
            background: #06BBCC;
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
        }
        
        .nav-tabs .nav-link:hover {
            border: none;
            color: #06BBCC;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        /* Check Status Button */
        .check-status-btn {
            background: white;
            border: 2px solid #06BBCC;
            color: #06BBCC;
            border-radius: 10px;
            padding: 15px 25px;
            text-align: center;
            transition: all 0.3s;
            display: block;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .check-status-btn:hover {
            background: #06BBCC;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .check-status-btn i {
            margin-right: 8px;
        }
        
        /* Month Calendar */
        .month-calendar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .month-calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .month-calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .month-calendar-day {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .month-calendar-day.header {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .month-calendar-day.leave {
            background: #06BBCC;
            color: white;
        }
        
        .month-calendar-day.sunday {
            background: #ffe6e6;
            color: #dc3545;
        }
        
        .month-calendar-day.today {
            border: 2px solid #06BBCC;
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 text-white mb-3">Request Leave Approval</h1>
                    <p class="text-white mb-0">
                        Submit your leave request for admin approval. All Sundays are automatically disabled as holidays.
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Request Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Leave Form & Status -->
                <div class="col-lg-12">
                    <!-- Check Status Button -->
                    <a href="#leaveHistory" class="check-status-btn" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="leaveHistory">
                        <i class="fas fa-history"></i> Check Leave Status & History
                    </a>
                    
                    <!-- Leave History Section -->
                    <div class="collapse" id="leaveHistory">
                        <div class="info-card mb-4">
                            <h5><i class="fas fa-history me-2"></i>Your Leave History</h5>
                            
                            <!-- Tabs -->
                            <ul class="nav nav-tabs" id="leaveHistoryTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="current-month-tab" data-bs-toggle="tab" data-bs-target="#current-month" type="button" role="tab">Current Month</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="all-history-tab" data-bs-toggle="tab" data-bs-target="#all-history" type="button" role="tab">All History</button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="leaveHistoryTabsContent">
                                <!-- Current Month Tab -->
                                <div class="tab-pane fade show active" id="current-month" role="tabpanel">
                                    <?php if (!empty($current_month_leaves)): ?>
                                        <div class="table-responsive">
                                            <table class="leave-history-table">
                                                <thead>
                                                    <tr>
                                                        <th>Request Date</th>
                                                        <th>Leave Dates</th>
                                                        <th>Reason</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($current_month_leaves as $leave): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y', strtotime($leave['request_date'])); ?></td>
                                                            <td>
                                                                <ul class="leave-dates-list">
                                                                    <?php 
                                                                    $dates = explode(',', $leave['leave_dates']);
                                                                    foreach ($dates as $date): 
                                                                    ?>
                                                                        <li><?php echo date('M d, Y', strtotime($date)); ?></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($leave['leave_reason']); ?></td>
                                                            <td>
                                                                <span class="status-badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                                                    <?php echo ucfirst($leave['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-4">No leave requests found for current month.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- All History Tab -->
                                <div class="tab-pane fade" id="all-history" role="tabpanel">
                                    <?php if (!empty($leave_history)): ?>
                                        <div class="table-responsive">
                                            <table class="leave-history-table">
                                                <thead>
                                                    <tr>
                                                        <th>Request Date</th>
                                                        <th>Leave Dates</th>
                                                        <th>Reason</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($leave_history as $leave): ?>
                                                        <tr>
                                                            <td><?php echo date('M d, Y', strtotime($leave['request_date'])); ?></td>
                                                            <td>
                                                                <ul class="leave-dates-list">
                                                                    <?php 
                                                                    $dates = explode(',', $leave['leave_dates']);
                                                                    foreach ($dates as $date): 
                                                                    ?>
                                                                        <li><?php echo date('M d, Y', strtotime($date)); ?></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($leave['leave_reason']); ?></td>
                                                            <td>
                                                                <span class="status-badge <?php echo getStatusBadgeClass($leave['status']); ?>">
                                                                    <?php echo ucfirst($leave['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-4">No leave history found.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Month Calendar -->
                        
                        </div>
                    </div>
                    
                    <!-- Leave Request Form -->
                    <div class="leave-form-card">
                        <h4 class="mb-4"><i class="fas fa-calendar-alt me-2"></i>Leave Request Form</h4>
                        
                        <form method="POST" action="">
                            <!-- Staff Email (Auto-filled and disabled) -->
                            <div class="mb-4">
                                <label for="staff_email" class="form-label fw-bold">Staff Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="staff_email" 
                                       value="<?php echo htmlspecialchars($staff_email); ?>" 
                                       disabled>
                                <small class="text-muted">This field is automatically filled with your login email.</small>
                            </div>
                            
                            <!-- Leave Dates Selection -->
                            <div class="mb-4">
                                <label for="leave_dates_input" class="form-label fw-bold">Select Leave Dates</label>
                                <div class="date-picker-container">
                                    <input type="text" 
                                           class="form-control" 
                                           id="leave_dates_input" 
                                           placeholder="Click to select leave dates" 
                                           readonly
                                           onclick="toggleCalendar()">
                                    <div class="calendar-container" id="calendarContainer">
                                        <div class="calendar-header">
                                            <button type="button" class="calendar-nav" onclick="changeMonth(-1)">‹</button>
                                            <h6 id="calendarMonthYear" class="mb-0"></h6>
                                            <button type="button" class="calendar-nav" onclick="changeMonth(1)">›</button>
                                        </div>
                                        <div class="calendar-days" id="calendarDays">
                                            <!-- Calendar days will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                                <div class="selected-dates mt-3" id="selectedDates">
                                    <small class="text-muted">No dates selected yet. Click the field above to select dates.</small>
                                </div>
                                <input type="hidden" name="leave_dates" id="leave_dates" value="">
                            </div>
                            
                            <!-- Leave Reason -->
                            <div class="mb-4">
                                <label for="leave_reason" class="form-label fw-bold">Reason for Leave</label>
                                <textarea class="form-control" 
                                          id="leave_reason" 
                                          name="leave_reason" 
                                          rows="5" 
                                          placeholder="Please provide a detailed reason for your leave request..."
                                          required><?php echo isset($_POST['leave_reason']) ? htmlspecialchars($_POST['leave_reason']) : ''; ?></textarea>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" name="submit_leave_request" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Leave Request
                                </button>
                            </div>
                        </form>
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
                    <a class="btn btn-link" href="staff-dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="request_leave_approval.php">Leave Request</a>
                    <a class="btn btn-link" href="mark_attendance.php">Check-in</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help with leave requests? Contact admin support</p>
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
    
    <!-- Custom Calendar Script -->
<script>
    let currentDate = new Date();
    let selectedDates = [];
    let calendarDate = new Date();
    
    function toggleCalendar() {
        const calendar = document.getElementById('calendarContainer');
        if (calendar.style.display === 'block') {
            calendar.style.display = 'none';
        } else {
            calendar.style.display = 'block';
            renderCalendar(currentDate);
        }
    }
    
    function renderCalendar(date) {
        const calendarDays = document.getElementById('calendarDays');
        const calendarMonthYear = document.getElementById('calendarMonthYear');
        
        // Clear previous calendar
        calendarDays.innerHTML = '';
        
        // Set month and year
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];
        calendarMonthYear.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        
        // Get first day of month and total days
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
        const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day empty';
            emptyCell.textContent = '';
            calendarDays.appendChild(emptyCell);
        }
        
        // Add days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;
            
            const currentDay = new Date(date.getFullYear(), date.getMonth(), day);
            const dayOfWeek = currentDay.getDay();
            const dateString = formatDate(currentDay);
            
            // Check if it's Sunday (dayOfWeek === 0)
            if (dayOfWeek === 0) {
                dayElement.classList.add('sunday', 'disabled');
                dayElement.title = 'Sunday - Holiday';
            } else {
                // Check if date is selected
                if (selectedDates.includes(dateString)) {
                    dayElement.classList.add('selected');
                }
                
                // Add click event listener with event propagation prevention
                dayElement.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent event from bubbling up
                    toggleDateSelection(currentDay);
                });
            }
            
            calendarDays.appendChild(dayElement);
        }
    }
    
    function changeMonth(direction) {
        currentDate.setMonth(currentDate.getMonth() + direction);
        renderCalendar(currentDate);
    }
    
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    function toggleDateSelection(date) {
        const dateString = formatDate(date);
        const index = selectedDates.indexOf(dateString);
        
        if (index === -1) {
            selectedDates.push(dateString);
        } else {
            selectedDates.splice(index, 1);
        }
        
        updateSelectedDatesDisplay();
        renderCalendar(currentDate);
        
        // Don't close the calendar after date selection
        // Calendar will remain open for multiple date selections
    }
    
    function updateSelectedDatesDisplay() {
        const selectedDatesContainer = document.getElementById('selectedDates');
        const leaveDatesInput = document.getElementById('leave_dates');
        const leaveDatesDisplay = document.getElementById('leave_dates_input');
        
        if (selectedDates.length === 0) {
            selectedDatesContainer.innerHTML = '<small class="text-muted">No dates selected yet. Click the field above to select dates.</small>';
            leaveDatesDisplay.value = '';
        } else {
            selectedDatesContainer.innerHTML = '';
            const sortedDates = selectedDates.sort();
            sortedDates.forEach(date => {
                const dateBadge = document.createElement('span');
                dateBadge.className = 'date-badge';
                dateBadge.innerHTML = `${formatDisplayDate(date)} <span class="remove-date" onclick="removeDate('${date}')">×</span>`;
                selectedDatesContainer.appendChild(dateBadge);
            });
            
            // Update the display input field
            leaveDatesDisplay.value = sortedDates.map(date => formatDisplayDate(date)).join(', ');
        }
        
        leaveDatesInput.value = selectedDates.join(',');
    }
    
    function formatDisplayDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            weekday: 'short', 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    function removeDate(dateString) {
        const index = selectedDates.indexOf(dateString);
        if (index !== -1) {
            selectedDates.splice(index, 1);
            updateSelectedDatesDisplay();
            renderCalendar(currentDate);
        }
    }
    
    // Month Calendar Functions
    function renderMonthCalendar(date) {
        const monthCalendar = document.getElementById('monthCalendar');
        const currentMonthYear = document.getElementById('currentMonthYear');
        
        // Clear previous calendar
        monthCalendar.innerHTML = '';
        
        // Set month and year
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"];
        currentMonthYear.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
        
        // Add day headers
        const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayHeaders.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = 'month-calendar-day header';
            dayElement.textContent = day;
            monthCalendar.appendChild(dayElement);
        });
        
        // Get first day of month and total days
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
        const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'month-calendar-day empty';
            emptyCell.textContent = '';
            monthCalendar.appendChild(emptyCell);
        }
        
        // Add days of the month
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'month-calendar-day';
            dayElement.textContent = day;
            
            const currentDay = new Date(date.getFullYear(), date.getMonth(), day);
            const dayOfWeek = currentDay.getDay();
            
            // Check if it's Sunday
            if (dayOfWeek === 0) {
                dayElement.classList.add('sunday');
            }
            
            // Check if it's today
            if (currentDay.toDateString() === today.toDateString()) {
                dayElement.classList.add('today');
            }
            
            // Check if this date is in any approved leave
            <?php 
            $approved_dates = [];
            foreach ($current_month_leaves as $leave) {
                if ($leave['status'] === 'approved') {
                    $dates = explode(',', $leave['leave_dates']);
                    foreach ($dates as $date) {
                        $approved_dates[] = date('Y-m-d', strtotime($date));
                    }
                }
            }
            if (!empty($approved_dates)) {
                echo "const approvedDates = ['" . implode("', '", $approved_dates) . "'];";
                echo "if (approvedDates.includes(formatDate(currentDay))) {";
                echo "    dayElement.classList.add('leave');";
                echo "    dayElement.title = 'Leave Day';";
                echo "}";
            } else {
                echo "const approvedDates = [];";
            }
            ?>
            
            monthCalendar.appendChild(dayElement);
        }
    }
    
    function changeCalendarMonth(direction) {
        calendarDate.setMonth(calendarDate.getMonth() + direction);
        renderMonthCalendar(calendarDate);
    }
    
    // Close calendar when clicking outside - but not when clicking on calendar elements
    document.addEventListener('click', function(event) {
        const calendar = document.getElementById('calendarContainer');
        const dateInput = document.getElementById('leave_dates_input');
        const calendarDays = document.getElementById('calendarDays');
        
        // Check if click is outside both the calendar and the input field
        if (calendar && 
            !calendar.contains(event.target) && 
            !dateInput.contains(event.target) &&
            event.target !== calendarDays) {
            calendar.style.display = 'none';
        }
    });

    // Add event listener to prevent calendar from closing when clicking inside it
    document.addEventListener('DOMContentLoaded', function() {
        const calendarContainer = document.getElementById('calendarContainer');
        if (calendarContainer) {
            calendarContainer.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent click from bubbling to document
            });
        }
        
        // Initialize the date picker calendar
        if (document.getElementById('calendarDays')) {
            renderCalendar(currentDate);
        }
        
        // Initialize the month view calendar
        if (document.getElementById('monthCalendar')) {
            renderMonthCalendar(calendarDate);
        }
    });

    // Add a close button to the calendar for better UX
    function addCloseButton() {
        const calendarHeader = document.querySelector('.calendar-header');
        if (calendarHeader && !document.querySelector('.calendar-close-btn')) {
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'calendar-close-btn';
            closeBtn.innerHTML = '&times;';
            closeBtn.style.background = 'none';
            closeBtn.style.border = 'none';
            closeBtn.style.fontSize = '1.5rem';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.color = '#06BBCC';
            closeBtn.style.marginLeft = 'auto';
            closeBtn.onclick = function() {
                document.getElementById('calendarContainer').style.display = 'none';
            };
            calendarHeader.appendChild(closeBtn);
        }
    }

    // Call addCloseButton after calendar is rendered
    const originalRenderCalendar = renderCalendar;
    renderCalendar = function(date) {
        originalRenderCalendar(date);
        addCloseButton();
    };
</script>
</body>

</html>