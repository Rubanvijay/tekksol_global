<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if user is logged in as student
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

$student_username = $_SESSION['student_username'];
$student_data = [];
$assignment_stats = [];
$course_progress = [];
$recent_assignments = [];

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get student basic information
    $student_sql = "SELECT sd.*, s.username 
                   FROM student_details sd 
                   JOIN students s ON sd.username = s.username 
                   WHERE s.username = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bind_param("s", $student_username);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
    }
    $student_stmt->close();

    // Get assignment statistics
    $stats_sql = "SELECT 
                  COUNT(*) as total_assignments,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_assignments,
                  SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_assignments,
                  SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as graded_assignments,
                  AVG(CASE WHEN status = 'graded' THEN CAST(grade AS DECIMAL) ELSE NULL END) as average_grade,
                  MIN(assigned_date) as first_assignment_date,
                  MAX(assigned_date) as last_assignment_date
                  FROM student_task 
                  WHERE username = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("s", $student_username);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    
    if ($stats_result->num_rows > 0) {
        $assignment_stats = $stats_result->fetch_assoc();
    }
    $stats_stmt->close();

    // Get course progress by domain
    $course_sql = "SELECT 
                  course_domain,
                  COUNT(*) as total_assignments,
                  SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as completed_assignments,
                  AVG(CASE WHEN status = 'graded' THEN CAST(grade AS DECIMAL) ELSE NULL END) as domain_average
                  FROM student_task 
                  WHERE username = ? 
                  GROUP BY course_domain 
                  ORDER BY total_assignments DESC";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("s", $student_username);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    
    while ($row = $course_result->fetch_assoc()) {
        $course_progress[] = $row;
    }
    $course_stmt->close();

    // Get recent assignments with grades
    $recent_sql = "SELECT * FROM student_task 
                  WHERE username = ? 
                  ORDER BY assigned_date DESC 
                  LIMIT 5";
    $recent_stmt = $conn->prepare($recent_sql);
    $recent_stmt->bind_param("s", $student_username);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    
    while ($row = $recent_result->fetch_assoc()) {
        $recent_assignments[] = $row;
    }
    $recent_stmt->close();

    $conn->close();

} catch (Exception $e) {
    $error_message = "Error loading progress report: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Progress Report - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Student Progress Report, Tekksol Global, Performance Tracking" name="keywords">
    <meta content="Student progress report and performance tracking for Tekksol Global training institute" name="description">

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
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .progress-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .progress-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
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
        
        .progress-bar-custom {
            height: 20px;
            border-radius: 10px;
            background: #f0f0f0;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #06BBCC, #0596a3);
            transition: width 0.5s ease-in-out;
        }
        
        .course-progress-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .course-progress-item:hover {
            border-color: #06BBCC;
            box-shadow: 0 3px 10px rgba(6, 187, 204, 0.1);
        }
        
        .grade-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .grade-excellent {
            background: #d4edda;
            color: #155724;
        }
        
        .grade-good {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .grade-average {
            background: #fff3cd;
            color: #856404;
        }
        
        .grade-poor {
            background: #f8d7da;
            color: #721c24;
        }
        
        .assignment-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .assignment-item:hover {
            border-color: #06BBCC;
            transform: translateX(5px);
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
        
        .performance-chart {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #06BBCC;
            font-weight: 500;
        }
        
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-data i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
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
                <a href="view_assignment.php" class="nav-item nav-link">Assignments</a>
                <a href="progress_report.php" class="nav-item nav-link active">Progress Report</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['student_username'] ?? 'Student'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="student-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="view_assignment.php">
                                <i class="fas fa-tasks me-2"></i> Assignments
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="progress_report.php">
                                <i class="fas fa-chart-line me-2"></i> Progress Report
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="student-profile.php">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
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
                    <h1 class="display-5 text-white mb-3">Progress Report</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php echo htmlspecialchars($student_data['name'] ?? $_SESSION['student_username']); ?></strong>!
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="status-badge bg-white text-primary">Student Portal</span>
                        <span class="status-badge bg-white text-primary">Performance Tracking</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Report Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Student Information -->
            <div class="progress-card">
                <h3 class="mb-4"><i class="fas fa-user-graduate me-2"></i>Student Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Student Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['username'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Course Domain:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['course_domain'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Trainer:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['trainer_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Start Date:</span>
                        <span class="info-value"><?php echo isset($student_data['start_date']) ? date('M d, Y', strtotime($student_data['start_date'])) : 'N/A'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span>
                        <span class="info-value"><?php echo htmlspecialchars($student_data['Status'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $assignment_stats['total_assignments'] ?? 0; ?></div>
                        <div class="stat-label">Total Assignments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $assignment_stats['submitted_assignments'] ?? 0; ?></div>
                        <div class="stat-label">Submitted</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $assignment_stats['graded_assignments'] ?? 0; ?></div>
                        <div class="stat-label">Graded</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number">
                            <?php 
                            $avg_grade = $assignment_stats['average_grade'] ?? 0;
                            echo $avg_grade > 0 ? number_format($avg_grade, 1) . '%' : 'N/A';
                            ?>
                        </div>
                        <div class="stat-label">Average Grade</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Course Progress -->
                <div class="col-lg-6 mb-4">
                    <div class="progress-card">
                        <h4 class="mb-4"><i class="fas fa-book me-2"></i>Course Progress by Domain</h4>
                        <?php if (!empty($course_progress)): ?>
                            <?php foreach ($course_progress as $course): ?>
                                <div class="course-progress-item">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($course['course_domain'] ?: 'General'); ?></h6>
                                        <span class="text-muted">
                                            <?php echo $course['completed_assignments']; ?>/<?php echo $course['total_assignments']; ?> completed
                                        </span>
                                    </div>
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="width: <?php echo ($course['completed_assignments'] / $course['total_assignments']) * 100; ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">
                                            Progress: <?php echo number_format(($course['completed_assignments'] / $course['total_assignments']) * 100, 1); ?>%
                                        </small>
                                        <?php if ($course['domain_average']): ?>
                                            <span class="grade-badge <?php 
                                                echo $course['domain_average'] >= 90 ? 'grade-excellent' : 
                                                     ($course['domain_average'] >= 80 ? 'grade-good' : 
                                                     ($course['domain_average'] >= 70 ? 'grade-average' : 'grade-poor')); 
                                            ?>">
                                                Avg: <?php echo number_format($course['domain_average'], 1); ?>%
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-book-open"></i>
                                <p>No course progress data available yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Assignments -->
                <div class="col-lg-6 mb-4">
                    <div class="progress-card">
                        <h4 class="mb-4"><i class="fas fa-history me-2"></i>Recent Assignments</h4>
                        <?php if (!empty($recent_assignments)): ?>
                            <?php foreach ($recent_assignments as $assignment): ?>
                                <div class="assignment-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">Assignment #<?php echo $assignment['id']; ?></h6>
                                        <span class="status-badge status-<?php echo $assignment['status']; ?>">
                                            <?php echo ucfirst($assignment['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-muted mb-2 small">
                                        <?php echo date('M d, Y', strtotime($assignment['assigned_date'])); ?>
                                        <?php if ($assignment['course_domain']): ?>
                                            • <?php echo htmlspecialchars($assignment['course_domain']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($assignment['status'] == 'graded' && $assignment['grade']): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="grade-badge <?php 
                                                echo $assignment['grade'] >= 90 ? 'grade-excellent' : 
                                                     ($assignment['grade'] >= 80 ? 'grade-good' : 
                                                     ($assignment['grade'] >= 70 ? 'grade-average' : 'grade-poor')); 
                                            ?>">
                                                Grade: <?php echo $assignment['grade']; ?>%
                                            </span>
                                            <?php if ($assignment['feedback']): ?>
                                                <small class="text-muted">Feedback Available</small>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($assignment['status'] == 'submitted'): ?>
                                        <span class="text-success small">
                                            <i class="fas fa-check-circle me-1"></i>Submitted - Awaiting Grade
                                        </span>
                                    <?php else: ?>
                                        <span class="text-warning small">
                                            <i class="fas fa-clock me-1"></i>Pending Submission
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-tasks"></i>
                                <p>No assignments found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="progress-card">
                <h4 class="mb-4"><i class="fas fa-chart-bar me-2"></i>Performance Summary</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="performance-chart">
                            <h6 class="text-center mb-3">Assignment Status Distribution</h6>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Pending:</span>
                                    <span class="info-value"><?php echo $assignment_stats['pending_assignments'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Submitted:</span>
                                    <span class="info-value"><?php echo $assignment_stats['submitted_assignments'] ?? 0; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Graded:</span>
                                    <span class="info-value"><?php echo $assignment_stats['graded_assignments'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="performance-chart">
                            <h6 class="text-center mb-3">Study Timeline</h6>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">First Assignment:</span>
                                    <span class="info-value">
                                        <?php echo isset($assignment_stats['first_assignment_date']) ? 
                                            date('M d, Y', strtotime($assignment_stats['first_assignment_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Latest Assignment:</span>
                                    <span class="info-value">
                                        <?php echo isset($assignment_stats['last_assignment_date']) ? 
                                            date('M d, Y', strtotime($assignment_stats['last_assignment_date'])) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Completion Rate:</span>
                                    <span class="info-value">
                                        <?php 
                                        $total = $assignment_stats['total_assignments'] ?? 0;
                                        $graded = $assignment_stats['graded_assignments'] ?? 0;
                                        echo $total > 0 ? number_format(($graded / $total) * 100, 1) . '%' : '0%';
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="view_assignment.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-tasks me-2"></i>View All Assignments
                </a>
                <a href="student-dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
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
                    <h4 class="text-white mb-3">Student Resources</h4>
                    <a class="btn btn-link" href="course-materials.html">Course Materials</a>
                    <a class="btn btn-link" href="view_assignment.php">Assignments</a>
                    <a class="btn btn-link" href="progress_report.php">Progress Reports</a>
                    <a class="btn btn-link" href="placement.html">Placement Support</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help? Contact your trainer</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <a href="contact.html" class="btn btn-primary w-100">Get Help</a>
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
                            <a href="student-dashboard.php">Dashboard</a>
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
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });

        // Print progress report
        function printProgressReport() {
            const originalContent = document.body.innerHTML;
            const printContent = document.querySelector('.container-xxl').innerHTML;
            
            document.body.innerHTML = `
                <div class="container mt-4">
                    <div class="text-center mb-4">
                        <h2>Tekksol Global - Progress Report</h2>
                        <h3>Student: <?php echo htmlspecialchars($student_data['name'] ?? $_SESSION['student_username']); ?></h3>
                        <p>Generated on: ${new Date().toLocaleDateString()}</p>
                        <hr>
                    </div>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

        // Add print button functionality
        document.addEventListener('DOMContentLoaded', function() {
            const actionButtons = document.querySelector('.text-center.mt-4');
            const printButton = document.createElement('button');
            printButton.className = 'btn btn-outline-primary btn-lg ms-2';
            printButton.innerHTML = '<i class="fas fa-print me-2"></i>Print Report';
            printButton.onclick = printProgressReport;
            actionButtons.appendChild(printButton);
        });
    </script>
</body>

</html>