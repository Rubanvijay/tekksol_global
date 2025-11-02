<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in as student
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$error = "";
$success = "";
$attendance_records = [];
$student_info = [];
$current_student_username = $_SESSION['student_username'];

// Initialize statistics variables
$total_days = 0;
$present_count = 0;
$absent_count = 0;
$leave_count = 0;
$attendance_percentage = 0;

$selected_month = date('Y-m');

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle month selection
    if (isset($_POST['selected_month'])) {
        $selected_month = $_POST['selected_month'];
    }
    
    // Get student information - using username column instead of student_username
    $student_sql = "SELECT student_id, name, course_domain, Status FROM student_details WHERE username = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bind_param("s", $current_student_username);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        $student_info = $student_result->fetch_assoc();
        $student_id = $student_info['student_id'];
        
        // Get attendance records for the selected month
        $start_date = $selected_month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $attendance_sql = "SELECT attendance_date, status, marked_by FROM student_attendance 
                          WHERE student_id = ? AND attendance_date BETWEEN ? AND ? 
                          ORDER BY attendance_date DESC";
        $attendance_stmt = $conn->prepare($attendance_sql);
        $attendance_stmt->bind_param("sss", $student_id, $start_date, $end_date);
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result();
        
        while ($row = $attendance_result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
        $attendance_stmt->close();
        
        // Calculate attendance statistics
        $total_days = count($attendance_records);
        $present_count = 0;
        $absent_count = 0;
        $leave_count = 0;
        
        foreach ($attendance_records as $record) {
            if ($record['status'] === 'Present') $present_count++;
            elseif ($record['status'] === 'Absent') $absent_count++;
            elseif ($record['status'] === 'Leave') $leave_count++;
        }
        
        $attendance_percentage = $total_days > 0 ? round(($present_count / $total_days) * 100, 2) : 0;
    } else {
        $error = "Student information not found.";
    }
    
    $student_stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Attendance error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Attendance - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
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
        .attendance-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .attendance-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .stats-present .stats-number {
            color: #28a745;
        }
        
        .stats-absent .stats-number {
            color: #dc3545;
        }
        
        .stats-leave .stats-number {
            color: #ffc107;
        }
        
        .stats-percentage .stats-number {
            color: #06BBCC;
        }
        
        .attendance-row {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .attendance-row:hover {
            border-color: #06BBCC;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .student-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .attendance-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-present {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-leave {
            background: #fff3cd;
            color: #856404;
        }
        
        .date-selector {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }

        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .attendance-header {
                padding: 25px 0;
                margin-bottom: 20px;
            }
            
            .attendance-header h1 {
                font-size: 1.8rem;
            }
            
            .attendance-card {
                padding: 20px 15px;
                margin-bottom: 20px;
                border-radius: 10px;
            }
            
            .stats-card {
                padding: 15px 10px;
                margin-bottom: 15px;
            }
            
            .stats-number {
                font-size: 1.8rem;
            }
            
            .stats-label {
                font-size: 0.8rem;
            }
            
            .student-info-card {
                padding: 15px;
            }
            
            .attendance-row {
                padding: 12px;
                margin-bottom: 8px;
            }
            
            .attendance-badge {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .mobile-stats-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .mobile-stats-grid .stats-card {
                margin-bottom: 0;
            }
        }
        
        @media (max-width: 576px) {
            .attendance-header {
                padding: 20px 0;
            }
            
            .attendance-header h1 {
                font-size: 1.5rem;
            }
            
            .stats-number {
                font-size: 1.5rem;
            }
        }

        /* Enhanced mobile navigation */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e0e0e0;
            padding: 10px 0;
            display: none;
            z-index: 1000;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        
        .mobile-nav-item {
            flex: 1;
            text-align: center;
            padding: 8px 5px;
            color: #666;
            text-decoration: none;
            font-size: 0.75rem;
        }
        
        .mobile-nav-item.active {
            color: #06BBCC;
        }
        
        .mobile-nav-icon {
            font-size: 1.2rem;
            margin-bottom: 3px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .mobile-bottom-nav {
                display: flex;
            }
            
            /* Add padding to bottom of content to account for fixed nav */
            body {
                padding-bottom: 70px;
            }
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
                <a href="student-dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="student_attendance.php" class="nav-item nav-link active">My Attendance</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($current_student_username); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li><a class="dropdown-item" href="student-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="student_attendance.php"><i class="fas fa-calendar-check me-2"></i> My Attendance</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar End -->

    <!-- Attendance Header -->
    <div class="attendance-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 text-white mb-3"><i class="fas fa-calendar-check me-3"></i>My Attendance</h1>
                    <p class="text-white mb-0">View your attendance records and statistics</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <h3 class="text-white"><?php echo date('F Y', strtotime($selected_month)); ?></h3>
                    <p class="text-white mb-0"><?php echo count($attendance_records); ?> records found</p>
                </div>
                <div class="col-12 d-md-none text-center">
                    <h3 class="text-white mb-1"><?php echo date('F Y', strtotime($selected_month)); ?></h3>
                    <p class="text-white mb-0"><?php echo count($attendance_records); ?> records</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Student Information -->
            <?php if (!empty($student_info)): ?>
            <div class="student-info-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2"><?php echo htmlspecialchars($student_info['name']); ?></h4>
                        <div class="d-flex flex-wrap gap-3">
                            <span><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($student_info['student_id']); ?></span>
                            <span><i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($student_info['course_domain']); ?></span>
                            <span><i class="fas fa-circle me-1 text-success"></i> <?php echo htmlspecialchars($student_info['Status']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-inline-block bg-primary text-white px-3 py-2 rounded">
                            <small>Attendance Rate</small>
                            <h4 class="mb-0"><?php echo $attendance_percentage; ?>%</h4>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4 d-none d-md-flex">
                <div class="col-md-3">
                    <div class="stats-card stats-present">
                        <div class="stats-number"><?php echo $present_count; ?></div>
                        <div class="stats-label">Present Days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-absent">
                        <div class="stats-number"><?php echo $absent_count; ?></div>
                        <div class="stats-label">Absent Days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-leave">
                        <div class="stats-number"><?php echo $leave_count; ?></div>
                        <div class="stats-label">Leave Days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-percentage">
                        <div class="stats-number"><?php echo $attendance_percentage; ?>%</div>
                        <div class="stats-label">Attendance Rate</div>
                    </div>
                </div>
            </div>

            <!-- Mobile Statistics -->
            <div class="mobile-stats-grid d-md-none mb-4">
                <div class="stats-card stats-present">
                    <div class="stats-number"><?php echo $present_count; ?></div>
                    <div class="stats-label">Present</div>
                </div>
                <div class="stats-card stats-absent">
                    <div class="stats-number"><?php echo $absent_count; ?></div>
                    <div class="stats-label">Absent</div>
                </div>
                <div class="stats-card stats-leave">
                    <div class="stats-number"><?php echo $leave_count; ?></div>
                    <div class="stats-label">Leave</div>
                </div>
                <div class="stats-card stats-percentage">
                    <div class="stats-number"><?php echo $attendance_percentage; ?>%</div>
                    <div class="stats-label">Rate</div>
                </div>
            </div>

            <!-- Month Filter -->
            <div class="date-selector">
                <form method="POST" action="" id="filterForm">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><i class="fas fa-calendar me-2"></i>Select Month</label>
                            <input type="month" 
                                   class="form-control" 
                                   name="selected_month" 
                                   value="<?php echo $selected_month; ?>"
                                   max="<?php echo date('Y-m'); ?>"
                                   onchange="document.getElementById('filterForm').submit();">
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0 text-muted">
                                Showing records for <strong><?php echo date('F Y', strtotime($selected_month)); ?></strong>
                            </p>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Attendance Records -->
            <?php if (!empty($attendance_records)): ?>
            <div class="attendance-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4><i class="fas fa-history me-2"></i>Attendance History</h4>
                    <span class="badge bg-primary"><?php echo count($attendance_records); ?> Records</span>
                </div>

                <div class="attendance-list">
                    <?php foreach ($attendance_records as $record): 
                        $status_class = '';
                        if ($record['status'] === 'Present') $status_class = 'badge-present';
                        elseif ($record['status'] === 'Absent') $status_class = 'badge-absent';
                        elseif ($record['status'] === 'Leave') $status_class = 'badge-leave';
                    ?>
                    <div class="attendance-row">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded p-2 me-3">
                                        <i class="fas fa-calendar-day text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1"><?php echo date('l', strtotime($record['attendance_date'])); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <span class="attendance-badge <?php echo $status_class; ?>">
                                    <i class="fas fa-<?php echo $record['status'] === 'Present' ? 'check' : ($record['status'] === 'Absent' ? 'times' : 'calendar-times'); ?> me-1"></i>
                                    <?php echo htmlspecialchars($record['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <small class="text-muted">
                                    Marked by: <strong><?php echo htmlspecialchars($record['marked_by']); ?></strong>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="attendance-card">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>No Attendance Records Found</h4>
                    <p class="text-muted">
                        No attendance records found for <?php echo date('F Y', strtotime($selected_month)); ?>.
                        <?php if ($selected_month === date('Y-m')): ?>
                            Attendance might not have been marked yet for this month.
                        <?php endif; ?>
                    </p>
                    <a href="student-dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="mobile-bottom-nav">
        <a href="student-dashboard.php" class="mobile-nav-item">
            <i class="fas fa-tachometer-alt mobile-nav-icon"></i>
            Dashboard
        </a>
        <a href="student_attendance.php" class="mobile-nav-item active">
            <i class="fas fa-calendar-check mobile-nav-icon"></i>
            Attendance
        </a>
        <a href="contact.html" class="mobile-nav-item">
            <i class="fas fa-envelope mobile-nav-icon"></i>
            Contact
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt mobile-nav-icon"></i>
            Logout
        </a>
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
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Tekksol Global, OMR, Chennai</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+91 9042527746</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@tekksolglobal.com</p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Student Resources</h4>
                    <a class="btn btn-link" href="student-dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="student_attendance.php">My Attendance</a>
                    <a class="btn btn-link" href="courses.html">Courses</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <a href="contact.html" class="btn btn-primary w-100">Contact Support</a>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">Tekksol Global</a>, All Rights Reserved 2024.
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
        // Mobile-specific optimizations
        function optimizeForMobile() {
            if (window.innerWidth < 768) {
                // Ensure content is easily scrollable
                document.querySelectorAll('.attendance-row').forEach(row => {
                    row.style.padding = '12px 10px';
                });
            }
        }

        // Run mobile optimizations on load and resize
        window.addEventListener('load', optimizeForMobile);
        window.addEventListener('resize', optimizeForMobile);
    </script>
</body>

</html>