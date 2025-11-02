<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Staff Dashboard - Session data: " . print_r($_SESSION, true));

// Check if user is logged in as staff
if (!isset($_SESSION['staff_username'])) {
    error_log("No staff session found, redirecting to login");
    header("Location: staff-login.html");
    exit();
}

error_log("Staff user logged in: " . $_SESSION['staff_username']);

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$staff_data = [];
$error = "";
$search_results = [];
$search_query = "";
$current_staff_username = $_SESSION['staff_username'];

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle search - ONLY SHOW STUDENTS FOR CURRENT TRAINER
    if (isset($_POST['search']) && !empty($_POST['search_query'])) {
        $search_query = $_POST['search_query'];
        $sql = "SELECT student_id, name, username, email, mobile_no, course_domain, Status, trainer_name 
                FROM student_details 
                WHERE (student_id LIKE ? OR name LIKE ? OR username LIKE ? OR email LIKE ?)
                AND trainer_name = ?
                LIMIT 10";
        
        $search_term = "%{$search_query}%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $current_staff_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
        
        $stmt->close();
    }
    
    // Get total students count - ONLY FOR CURRENT TRAINER
    $total_students = 0;
    $sql = "SELECT COUNT(*) as count FROM student_details WHERE trainer_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $total_students = $row['count'];
    }
    $stmt->close();
    
    // Get active students count - ONLY FOR CURRENT TRAINER
    $active_students = 0;
    $sql = "SELECT COUNT(*) as count FROM student_details WHERE Status = 'Active' AND trainer_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $active_students = $row['count'];
    }
    $stmt->close();
    
    // Get completed students count - ONLY FOR CURRENT TRAINER
    $completed_students = 0;
    $sql = "SELECT COUNT(*) as count FROM student_details WHERE Status = 'Completed' AND trainer_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $completed_students = $row['count'];
    }
    $stmt->close();
    
    // Get recent students - ONLY FOR CURRENT TRAINER
    $recent_students = [];
    $sql = "SELECT student_id, name, course_domain, start_date, Status FROM student_details WHERE trainer_name = ? ORDER BY start_date DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_students[] = $row;
        }
    }
    $stmt->close();
    
    // Get course distribution for current trainer
    $course_distribution = [];
    $sql = "SELECT course_domain, COUNT(*) as count FROM student_details WHERE trainer_name = ? GROUP BY course_domain";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $course_distribution[] = $row;
        }
    }
    $stmt->close();
    
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
    <title>Staff Dashboard - Tekksol Global</title>
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
 
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars((string)$_SESSION['staff_username'] ?? 'Staff'); ?>
                       

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
                    <h1 class="display-5 text-white mb-3">Staff Dashboard</h1>
                    <p class="welcome-text text-white">

                        Welcome back, <strong><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?></strong>!

                      

                    </p>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="status-badge me-3">Trainer</span>
                        <span class="status-badge me-3" style="background: #28a745; color: white;">
                            <i class="fas fa-users me-1"></i><?php echo $total_students; ?> Total Students
                        </span>
                        <span class="status-badge" style="background: #17a2b8; color: white;">
                            <i class="fas fa-graduation-cap me-1"></i><?php echo $completed_students; ?> Completed
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Quick Stats -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $active_students; ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo $completed_students; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="quick-stats">
                        <div class="stat-number"><?php echo count($recent_students); ?></div>
                        <div class="stat-label">Recent Enrollments</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Search Section -->
                <div class="col-lg-8 mb-4">
                    <!-- Course Distribution -->
                    <?php if (!empty($course_distribution)): ?>
                    <div class="info-card mb-4">
                        <h5><i class="fas fa-chart-pie me-2"></i>Your Course Distribution</h5>
                        <div class="mt-3">
                            <?php foreach ($course_distribution as $course): ?>
                                <span class="course-badge">

                                    <?php echo htmlspecialchars($course['course_domain']); ?>: <?php echo $course['count']; ?>

                                   

                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="search-card">
                        <h5><i class="fas fa-search me-2"></i>Search Your Students</h5>
                        <p class="text-muted mb-3">Search through your <?php echo $total_students; ?> allocated students</p>
                        <form method="POST" action="">
                            <div class="search-bar">
                                <input type="text" 
                                       class="form-control" 
                                       name="search_query" 
                                       placeholder="Search your students by ID, Name, Username, or Email..." 

                                       value="<?php echo htmlspecialchars($search_query); ?>"

                                       value="<?php echo htmlspecialchars((string)$search_query); ?>"

                                       required>
                                <button type="submit" name="search" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!empty($search_results)): ?>
                            <div class="search-results">
                                <h6 class="mt-4 mb-3">Search Results (<?php echo count($search_results); ?> of your students)</h6>
                                <?php foreach ($search_results as $student): ?>
                                    <div class="student-card" onclick="window.location.href='student-profile.php?student_id=<?php echo urlencode($student['student_id']); ?>'">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">

                                                <h6><?php echo htmlspecialchars($student['name']); ?></h6>
                                                <div class="student-info">
                                                    <span class="me-3"><i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars($student['student_id']); ?></span>
                                                    <span class="me-3"><i class="fas fa-book me-1"></i><?php echo htmlspecialchars($student['course_domain']); ?></span>

                                                <h6><?php echo htmlspecialchars((string)$student['name']); ?></h6>
                                                <div class="student-info">
                                                    <span class="me-3"><i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars((string)$student['student_id']); ?></span>
                                                    <span class="me-3"><i class="fas fa-book me-1"></i><?php echo htmlspecialchars((string)$student['course_domain']); ?></span>

                                                    <span class="<?php 
                                                        echo $student['Status'] == 'Active' ? 'status-active' : 
                                                              ($student['Status'] == 'Completed' ? 'status-completed' : 'status-inactive'); 
                                                    ?>">

                                                        <i class="fas fa-circle me-1"></i><?php echo htmlspecialchars($student['Status']); ?>

                                                        <i class="fas fa-circle me-1"></i><?php echo htmlspecialchars((string)$student['Status']); ?>

                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">

                                                <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>

                                                <small class="text-muted"><?php echo htmlspecialchars((string)$student['email']); ?></small>

                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (isset($_POST['search'])): ?>
                            <div class="alert alert-info mt-3">

                                <i class="fas fa-info-circle me-2"></i>No students found in your list matching "<?php echo htmlspecialchars($search_query); ?>"

                               

                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Students -->
                    <?php if (!empty($recent_students)): ?>
                    <div class="info-card">
                        <h5><i class="fas fa-clock me-2"></i>Your Recent Enrollments</h5>
                        <ul class="recent-students-list">
                            <?php foreach ($recent_students as $student): ?>
                                <li onclick="window.location.href='student-profile.php?student_id=<?php echo urlencode($student['student_id']); ?>'" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>

                                            <strong><?php echo htmlspecialchars($student['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['course_domain']); ?></small>

                                            <span class="<?php 
                                                echo $student['Status'] == 'Active' ? 'status-active' : 
                                                      ($student['Status'] == 'Completed' ? 'status-completed' : 'status-inactive'); 
                                            ?> ms-2">

                                                <i class="fas fa-circle me-1"></i><?php echo htmlspecialchars($student['Status']); ?>

                                               

                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted"><?php echo date('M d, Y', strtotime($student['start_date'])); ?></small>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
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


                        <a href="mark_student_attendance.php" class="action-btn">
    <i class="fas fa-calendar-check"></i>
    <strong>Student Attendance</strong>
    <small class="d-block text-muted mt-2">Mark daily attendance</small>

                        
                        <a href="generate_student_credentials.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <strong>Generate Credentials</strong>
                            <small class="d-block text-muted mt-2">Generate Student username & Password</small>
                        </a>

                        <a href="add-student.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <strong>Add New Student</strong>
                            <small class="d-block text-muted mt-2">Register new enrollment</small>
                        </a>

                        <a href="edit-student.php" class="action-btn">
                            <i class="fas fa-edit"></i>
                            <strong>Edit Student</strong>
                            <small class="d-block text-muted mt-2">Edit Student Details</small>
                        </a>
                        
                        <a href="add-assignment.php" class="action-btn">
                            <i class="fas fa-tasks"></i>
                            <strong>Add Assignment</strong>
                            <small class="d-block text-muted mt-2">Create new assignment</small>
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
</body>

</html>
