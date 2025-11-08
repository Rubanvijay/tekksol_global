<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Admin Dashboard - Session data: " . print_r($_SESSION, true));

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    error_log("No admin session found, redirecting to login");
    header("Location: admin-login.html");
    exit();
}

error_log("Admin user logged in: " . $_SESSION['admin_username']);

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$error = "";
$success = "";
$current_admin_username = $_SESSION['admin_username'];

// Statistics variables
$total_students = 0;
$active_students = 0;
$completed_students = 0;
$total_staff = 0;
$recent_students = [];
$recent_staff = [];
$course_distribution = [];

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get total students count
    $sql = "SELECT COUNT(*) as count FROM student_details";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $total_students = $row['count'];
    }
    
    // Get active students count
    $sql = "SELECT COUNT(*) as count FROM student_details WHERE Status = 'Active'";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $active_students = $row['count'];
    }
    
    // Get completed students count
    $sql = "SELECT COUNT(*) as count FROM student_details WHERE Status = 'Completed'";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $completed_students = $row['count'];
    }
    
    // Get total staff count
    $sql = "SELECT COUNT(*) as count FROM staff";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $total_staff = $row['count'];
    }
    
    // Get recent students
    $sql = "SELECT student_id, name, course_domain, start_date, Status, trainer_name FROM student_details ORDER BY start_date DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_students[] = $row;
        }
    }
    
    // Get recent staff
    $sql = "SELECT email FROM staff ORDER BY email DESC LIMIT 5";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_staff[] = $row;
        }
    }
    
    // Get course distribution
    $sql = "SELECT course_domain, COUNT(*) as count FROM student_details GROUP BY course_domain";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $course_distribution[] = $row;
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Admin Dashboard, Tekksol Global, Management Platform" name="keywords">
    <meta content="Admin dashboard for Tekksol Global training institute" name="description">

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
        /* Fix horizontal overflow */
        html, body {
            overflow-x: hidden !important;
            max-width: 100vw !important;
            width: 100% !important;
        }

        .container, .container-fluid, .container-xxl {
            overflow-x: hidden !important;
            max-width: 100% !important;
        }

        * {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Login Dropdown Customization - FIXED */
        #loginDropdown {
            border: none;
        }

        /* Force dropdown to accommodate full text */
        .dropdown-menu {
            min-width: 320px !important;
            width: max-content !important;
            max-width: none !important;
            white-space: nowrap !important;
        }

        .dropdown-menu .dropdown-item {
            transition: all 0.3s ease;
            white-space: nowrap !important;
            overflow: visible !important;
            text-overflow: clip !important;
            padding: 0.65rem 1.5rem !important;
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
        }

        .dropdown-menu .dropdown-item span {
            white-space: nowrap !important;
            overflow: visible !important;
            display: inline-block !important;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: #06BBCC;
            color: white;
        }

        .dropdown-menu .dropdown-item i {
            color: #06BBCC;
            width: 20px;
            flex-shrink: 0;
        }

        .dropdown-menu .dropdown-item:hover i {
            color: white;
        }
        
        /* Override Bootstrap dropdown constraints */
        .dropdown-menu-end {
            right: 0 !important;
            left: auto !important;
        }
        
        /* Admin Dashboard Styles */
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 30px 0 20px;
            margin-bottom: 20px;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        .info-card h5 {
            color: #06BBCC;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .welcome-text {
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .profile-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 15px;
        }
        
        .status-badge {
            background: white;
            color: #06BBCC;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: inline-block;
        }
        
        .quick-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s;
            margin-bottom: 15px;
        }
        
        .quick-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #06BBCC;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.85rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #06BBCC;
            color: #06BBCC;
            border-radius: 10px;
            padding: 15px;
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
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .recent-list {
            list-style: none;
            padding: 0;
        }
        
        .recent-list li {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s;
        }
        
        .recent-list li:hover {
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
        
        .admin-badge {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
        }
        
        .btn-outline-primary {
            border-color: #06BBCC;
            color: #06BBCC;
        }
        
        .btn-outline-primary:hover {
            background-color: #06BBCC;
            color: white;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 0 15px;
                margin-bottom: 15px;
            }
            
            .dashboard-header h1 {
                font-size: 1.8rem;
            }
            
            .profile-icon {
                font-size: 2.5rem;
                margin-bottom: 10px;
            }
            
            .info-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .quick-stats {
                padding: 12px;
                margin-bottom: 10px;
            }
            
            .stat-number {
                font-size: 1.3rem;
            }
            
            .action-btn {
                padding: 12px;
            }
            
            .action-btn i {
                font-size: 1.3rem;
            }
            
            .recent-list li {
                padding: 8px 5px;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 4px 10px;
            }
            
            .navbar-brand img {
                height: 50px;
                width: 80px;
            }
            
            .dropdown-menu {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .welcome-text {
                font-size: 0.9rem;
            }
            
            .info-card h5 {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 1.2rem;
            }
            
            .action-btn {
                padding: 10px;
            }
            
            .action-btn strong {
                font-size: 0.9rem;
            }
            
            .action-btn small {
                font-size: 0.8rem;
            }
            
            .course-badge {
                font-size: 0.75rem;
                padding: 2px 6px;
            }
        }
        
        /* Improve mobile navigation */
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        /* Mobile dropdown improvements */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: white;
                padding: 10px;
                border-radius: 0 0 10px 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .dropdown-menu {
                border: none;
                box-shadow: none;
                padding-left: 15px;
            }
        }
        
        /* Mobile table improvements */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        /* Mobile button improvements */
        .btn {
            white-space: nowrap;
        }
        
        /* Mobile card improvements */
        .card-body {
            padding: 15px;
        }
        
        /* Mobile form improvements */
        .form-control {
            font-size: 16px;
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
        <a href="index.html" class="navbar-brand d-flex align-items-center px-3 px-lg-4">
            <img src="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" alt="Tekksol Global Logo" height="50px" width="80px" class="img-fluid">
        </a>
        <button type="button" class="navbar-toggler me-3" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav ms-auto p-3 p-lg-0">
                <a href="" class="nav-item nav-link">Dashboard</a>
                <a href="view-all-students.php" class="nav-item nav-link">Students</a>
                <a href="view_staff.php" class="nav-item nav-link">Staff</a>
                <a href="request_leave_approval_admin.php" class="nav-item nav-link">Leave Approval</a>
                <a href="add_careers.php" class="nav-item nav-link">Add Careers</a>
                <a href="attendance_reports.php" class="nav-item nav-link">Attendance Report</a>
            </div>
           
            <!-- Desktop Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-3 px-4 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars((String)$_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li>
                            <a class="dropdown-item" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-3"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="view-all-students.php">
                                <i class="fas fa-users me-3"></i><span>View Students</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="view_staff.php">
                                <i class="fas fa-user-tie me-3"></i><span>View Staff</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="generate_staff_credentials.php">
                                <i class="fas fa-key me-3"></i><span>Generate Staff Credentials</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="attendance_reports.php">
                                <i class="fas fa-chart-bar me-3"></i><span>Attendance Reports</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-3"></i><span>Logout</span>
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
                    <h1 class="display-6 text-white mb-3">Admin Dashboard</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php echo htmlspecialchars((String)$_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                    </p>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="status-badge me-2" style="background: #28a745; color: white;">
                            <i class="fas fa-users me-1"></i><?php echo $total_students; ?> Students
                        </span>
                        <span class="status-badge" style="background: #17a2b8; color: white;">
                            <i class="fas fa-user-tie me-1"></i><?php echo $total_staff; ?> Staff
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-xxl py-4">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-6 col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $active_students; ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $completed_students; ?></div>
                        <div class="stat-label">Completed Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $total_staff; ?></div>
                        <div class="stat-label">Total Staff</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left Column - Information -->
                <div class="col-lg-8 mb-4">
                    <!-- Course Distribution -->
                    <?php if (!empty($course_distribution)): ?>
                    <div class="info-card mb-4">
                        <h5><i class="fas fa-chart-pie me-2"></i>Course Distribution</h5>
                        <div class="mt-3">
                            <?php foreach ($course_distribution as $course): ?>
                                <span class="course-badge">
                                    <?php echo htmlspecialchars((String)$course['course_domain']); ?>: <?php echo $course['count']; ?> students
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Students -->
                    <?php if (!empty($recent_students)): ?>
                    <div class="info-card mb-4">
                        <h5><i class="fas fa-users me-2"></i>Recent Student Enrollments</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars((String)$student['name']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars((String)$student['student_id']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars((String)$student['course_domain']); ?></td>
                                            <td>
                                                <span class="<?php 
                                                    echo $student['Status'] == 'Active' ? 'status-active' : 
                                                          ($student['Status'] == 'Completed' ? 'status-completed' : 'status-inactive'); 
                                                ?>">
                                                    <i class="fas fa-circle me-1"></i><?php echo htmlspecialchars((String)$student['Status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($student['start_date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="view-all-students.php" class="btn btn-outline-primary btn-sm">View All Students</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Staff -->
                    <?php if (!empty($recent_staff)): ?>
                    <div class="info-card">
                        <h5><i class="fas fa-user-tie me-2"></i>Staff Members</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_staff as $staff): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user-circle text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars((String)$staff['email']); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="view_staff.php" class="btn btn-outline-primary btn-sm">View All Staff</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column - Quick Actions -->
                <div class="col-lg-4 mb-4">
                    <div class="info-card">
                        <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        
                        <div class="row g-2">
                            <div class="col-6 col-lg-12">
                                <a href="view-all-students.php" class="action-btn">
                                    <i class="fas fa-users"></i>
                                    <strong>View All Students</strong>
                                    <small class="d-block text-muted mt-1">Manage student records</small>
                                </a>
                            </div>
                            <div class="col-6 col-lg-12">
                                <a href="upload_certificate2.php" class="action-btn">
                                    <i class="fas fa-medal"></i>
                                    <strong>Upload Certificate</strong>
                                    <small class="d-block text-muted mt-1">Upload Course Completion Certificate</small>
                                </a>
                            </div>
                            
                            <div class="col-6 col-lg-12">
                                <a href="view_staff.php" class="action-btn">
                                    <i class="fas fa-user-tie"></i>
                                    <strong>View Staff Details</strong>
                                    <small class="d-block text-muted mt-1">Manage staff members</small>
                                </a>
                            </div>
                              <div class="col-6 col-lg-12">
                                <a href="view_global_team_record.php" class="action-btn">
                                    <i class="fas fa-users"></i>
                                    <strong>Global Record</strong>
                                    <small class="d-block text-muted mt-1">View Global Team Record</small>
                                </a>
                            </div>
                            
                            <div class="col-6 col-lg-12">
                                <a href="generate_staff_credentials.php" class="action-btn">
                                    <i class="fas fa-key"></i>
                                    <strong>Create New Staff</strong>
                                    <small class="d-block text-muted mt-1">Create username & password</small>
                                </a>
                            </div>

                            <div class="col-6 col-lg-12">
                                <a href="delete_staff_accounts.php" class="action-btn">
                                    <i class="fas fa-user-slash"></i>
                                    <strong>Delete Staff</strong>
                                    <small class="d-block text-muted mt-1">Remove Staff Account</small>
                                </a>
                            </div>
                            <div class="col-6 col-lg-12">
                                <a href="request_leave_approval_admin.php" class="action-btn">
                                    <i class="fas fa-calendar-check"></i>
                                    <strong>Leave Approval</strong>
                                    <small class="d-block text-muted mt-1">Staff Leave Request</small>
                                </a>
                            </div>

                            <div class="col-6 col-lg-12">
                                <a href="add_careers.php" class="action-btn">
                                    <i class="fas fa-bullhorn"></i>
                                    <strong>Post Vacancy</strong>
                                    <small class="d-block text-muted mt-1">Add Jobs in Careers page</small>
                                </a>
                            </div>
                            
                            <div class="col-6 col-lg-12">
                                <a href="attendance_reports.php" class="action-btn">
                                    <i class="fas fa-chart-bar"></i>
                                    <strong>Attendance Report</strong>
                                    <small class="d-block text-muted mt-1">View student & staff attendance</small>
                                </a>
                            </div>
                             <div class="col-6 col-lg-12">
                                <a href="sent_pay_slip.php" class="action-btn">
                                    <i class="fas fa-money-check-alt"></i>
                                    <strong>Pay Slip</strong>
                                    <small class="d-block text-muted mt-1">Sent Pay Slip</small>
                                </a>
                            </div>

                            <div class="col-12">
                                <a href="logout.php" class="action-btn">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    <strong>Logout</strong>
                                    <small class="d-block text-muted mt-1">Logout from Your Account</small>
                                </a>
                            </div>
                        </div>
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
                    <h4 class="text-white mb-3">Admin Resources</h4>
                    <a class="btn btn-link" href="view_all_students.php">Student Management</a>
                    <a class="btn btn-link" href="view_staff.php">Staff Management</a>
                    <a class="btn btn-link" href="attendance_reports.php">Reports</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Administrative support and assistance</p>
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
                            <a href="admin_dashboard.php">Dashboard</a>
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
        // Mobile-specific enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Improve mobile dropdown behavior
            const dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('click', function(e) {
                    if (window.innerWidth < 992) {
                        e.preventDefault();
                        const menu = this.nextElementSibling;
                        menu.classList.toggle('show');
                    }
                });
            });
            
            // Close dropdowns when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && !e.target.matches('.dropdown-toggle')) {
                    const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                    openDropdowns.forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                }
            });
        });
    </script>
</body>

</html>