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

// Check if user is logged in as staff
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.html");
    exit();
}

$students = [];
$total_students = 0;
$active_students = 0;
$search_query = "";
$filter_course = "";
$filter_status = "";

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Build search and filter conditions
    $where_conditions = [];
    $params = [];
    $types = "";

    // Handle search
    if (isset($_GET['search']) && !empty($_GET['search_query'])) {
        $search_query = trim($_GET['search_query']);
        $where_conditions[] = "(sd.name LIKE ? OR sd.username LIKE ? OR sd.student_id LIKE ? OR sd.email LIKE ?)";
        $search_term = "%{$search_query}%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
        $types .= "ssss";
    }

    // Handle course filter
    if (isset($_GET['course_filter']) && !empty($_GET['course_filter'])) {
        $filter_course = $_GET['course_filter'];
        $where_conditions[] = "sd.course_domain = ?";
        $params[] = $filter_course;
        $types .= "s";
    }

    // Handle status filter
    if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
        $filter_status = $_GET['status_filter'];
        $where_conditions[] = "sd.Status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }

    // Build WHERE clause
    $where_clause = "";
    if (!empty($where_conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    }

    // Get total students count
    $count_sql = "SELECT COUNT(*) as total FROM student_details sd $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_students = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    // Get active students count
    $active_sql = "SELECT COUNT(*) as active FROM student_details WHERE Status = 'Active'";
    $active_result = $conn->query($active_sql);
    $active_students = $active_result->fetch_assoc()['active'];

    // Get students with pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT sd.*, s.username 
            FROM student_details sd 
            JOIN students s ON sd.username = s.username 
            $where_clause 
            ORDER BY sd.start_date DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    $stmt->close();

    // Get unique course domains for filter
    $course_sql = "SELECT DISTINCT course_domain FROM student_details WHERE course_domain IS NOT NULL AND course_domain != '' ORDER BY course_domain";
    $course_result = $conn->query($course_sql);
    $course_domains = [];
    while ($row = $course_result->fetch_assoc()) {
        $course_domains[] = $row['course_domain'];
    }

    // Get unique statuses for filter
    $status_sql = "SELECT DISTINCT Status FROM student_details WHERE Status IS NOT NULL AND Status != '' ORDER BY Status";
    $status_result = $conn->query($status_sql);
    $statuses = [];
    while ($row = $status_result->fetch_assoc()) {
        $statuses[] = $row['Status'];
    }

    $conn->close();

} catch (Exception $e) {
    $error_message = "Error loading students: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>All Students - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="View Students, Tekksol Global, Student Management" name="keywords">
    <meta content="View all students in Tekksol Global training institute" name="description">

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
        /* Login Dropdown Customization - FIXED */
        #loginDropdown, #mobileLoginDropdown {
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
        
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 30px 0 20px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
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
        
        .student-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #06BBCC;
            transition: all 0.3s;
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .student-name {
            color: #06BBCC;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .student-info {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .student-info i {
            width: 16px;
            color: #06BBCC;
            margin-right: 5px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .search-filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .page-link {
            color: #06BBCC;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .page-link:hover {
            color: #0596a3;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .page-item.active .page-link {
            background-color: #06BBCC;
            border-color: #06BBCC;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
        
        .no-students {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .no-students i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }
        
        .filter-badge {
            background: #06BBCC;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .filter-badge a {
            color: white;
            text-decoration: none;
            margin-left: 4px;
        }
        
        .profile-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 15px;
        }
        
        .welcome-text {
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 20px 0 15px;
                margin-bottom: 15px;
            }
            
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .profile-icon {
                font-size: 2.5rem;
                margin-bottom: 10px;
            }
            
            .stats-card {
                padding: 12px;
                margin-bottom: 10px;
            }
            
            .stat-number {
                font-size: 1.3rem;
            }
            
            .student-card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .student-name {
                font-size: 1rem;
            }
            
            .search-filter-card {
                padding: 12px;
            }
            
            .navbar-brand img {
                height: 50px;
                width: 80px;
            }
            
            .welcome-text {
                font-size: 0.9rem;
            }
            
            .dropdown-menu {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-header h1 {
                font-size: 1.3rem;
            }
            
            .stat-number {
                font-size: 1.2rem;
            }
            
            .student-info {
                font-size: 0.8rem;
            }
            
            .btn {
                font-size: 0.875rem;
            }
            
            .form-control, .form-select {
                font-size: 0.875rem;
            }
            
            .page-link {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
        }
        
        /* Mobile navigation improvements */
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        @media (max-width: 991px) {
            .navbar-collapse {
                background: white;
                padding: 10px;
                border-radius: 0 0 10px 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
        }
        
        /* Mobile table improvements */
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        /* Mobile form improvements */
        .form-control {
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        /* Mobile grid improvements */
        .row {
            margin-left: -8px;
            margin-right: -8px;
        }
        
        .col-lg-6, .col-xl-4, .col-md-4, .col-md-3, .col-md-2 {
            padding-left: 8px;
            padding-right: 8px;
        }
        
        /* Active filters mobile styling */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            align-items: center;
        }
        
        /* Mobile dropdown for filters */
        .filter-collapse-btn {
            width: 100%;
            text-align: left;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            margin-bottom: 10px;
        }
        
        .filter-collapse-btn:focus {
            box-shadow: none;
        }
        
        .filter-collapse-btn:after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            float: right;
        }
        
        .filter-collapse-btn.collapsed:after {
            content: '\f054';
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
               <a href="admin_dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="view-all-students.php" class="nav-item nav-link">View Students</a>
                <a href="view_staff.php" class="nav-item nav-link">View Staff</a>
                <a href="request_leave_approval_admin.php" class="nav-item nav-link">Leave Approval</a>
                <a href="add_careers.php" class="nav-item nav-link">Add Careers</a>
                <a href="attendance_reports.php" class="nav-item nav-link">Attendance Report</a>
            </div>
            
            <!-- Mobile Dropdown -->
            <div class="d-lg-none mt-3">
                <div class="dropdown">
                    <button class="btn btn-primary w-100 dropdown-toggle" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
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
            
            <!-- Desktop Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-3 px-4 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
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
                    <h1 class="display-6 text-white mb-3">All Students</h1>
                    <p class="welcome-text text-white">
                        Manage and view all student records
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="status-badge bg-white text-primary">Staff Portal</span>
                        <span class="status-badge bg-white text-primary"><?php echo $total_students; ?> Students</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Content -->
    <div class="container-xxl py-4">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-6 col-md-4">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $active_students; ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo count($students); ?></div>
                        <div class="stat-label">Showing</div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="search-filter-card">
                <!-- Mobile Filter Toggle -->
                <button class="btn filter-collapse-btn d-lg-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
                    <i class="fas fa-filter me-2"></i>Search & Filters
                </button>

                <form method="GET" action="">
                    <div class="collapse d-lg-block" id="filterCollapse">
                        <div class="row">
                            <div class="col-lg-4 mb-3">
                                <label class="form-label">Search Students</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search_query" 
                                           value="<?php echo htmlspecialchars($search_query); ?>" 
                                           placeholder="Search by name, username, ID, or email">
                                    <button class="btn btn-primary" type="submit" name="search">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label class="form-label">Course Domain</label>
                                <select class="form-select" name="course_filter">
                                    <option value="">All Courses</option>
                                    <?php foreach ($course_domains as $course): ?>
                                        <option value="<?php echo htmlspecialchars($course); ?>" 
                                            <?php echo $filter_course === $course ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status_filter">
                                    <option value="">All Status</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status); ?>" 
                                            <?php echo $filter_status === $status ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 mb-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-1"></i> Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Active Filters -->
                <?php if (!empty($search_query) || !empty($filter_course) || !empty($filter_status)): ?>
                    <div class="mt-3">
                        <small class="text-muted">Active filters:</small>
                        <div class="active-filters">
                            <?php if (!empty($search_query)): ?>
                                <span class="filter-badge">
                                    Search: "<?php echo htmlspecialchars($search_query); ?>"
                                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['search_query' => '', 'search' => ''])); ?>">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filter_course)): ?>
                                <span class="filter-badge">
                                    Course: <?php echo htmlspecialchars($filter_course); ?>
                                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['course_filter' => ''])); ?>">×</a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filter_status)): ?>
                                <span class="filter-badge">
                                    Status: <?php echo htmlspecialchars($filter_status); ?>
                                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['status_filter' => ''])); ?>">×</a>
                                </span>
                            <?php endif; ?>
                            <a href="view-all-students.php" class="btn btn-sm btn-outline-secondary">
                                Clear All
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Students List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($students)): ?>
                        <div class="no-students">
                            <i class="fas fa-users-slash"></i>
                            <h3>No Students Found</h3>
                            <p>
                                <?php if (!empty($search_query) || !empty($filter_course) || !empty($filter_status)): ?>
                                    No students match your search criteria. Try adjusting your filters.
                                <?php else: ?>
                                    No students found in the system. 
                                    <a href="add-student.php">Add the first student</a>.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($students as $student): ?>
                                <div class="col-12 col-sm-6 col-lg-4 mb-3">
                                    <div class="student-card">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="student-name mb-0">
                                                <?php echo htmlspecialchars($student['name']); ?>
                                            </h5>
                                            <span class="status-badge status-<?php echo strtolower($student['Status'] ?? 'active'); ?>">
                                                <?php echo htmlspecialchars($student['Status'] ?? 'Active'); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="student-info">
                                            <i class="fas fa-id-card"></i>
                                            <strong>ID:</strong> <?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="student-info">
                                            <i class="fas fa-user"></i>
                                            <strong>Username:</strong> <?php echo htmlspecialchars($student['username']); ?>
                                        </div>
                                        <div class="student-info">
                                            <i class="fas fa-envelope"></i>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="student-info">
                                            <i class="fas fa-phone"></i>
                                            <strong>Mobile:</strong> <?php echo htmlspecialchars($student['mobile_no'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="student-info">
                                            <i class="fas fa-book"></i>
                                            <strong>Course:</strong> <?php echo htmlspecialchars($student['course_domain'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="student-info">
                                            <i class="fas fa-calendar"></i>
                                            <strong>Start Date:</strong> 
                                            <?php echo isset($student['start_date']) ? date('M d, Y', strtotime($student['start_date'])) : 'N/A'; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_students > $limit): ?>
                            <nav aria-label="Student pagination">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php
                                    $total_pages = ceil($total_students / $limit);
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
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
                    <a class="btn btn-link" href="view-all-students.php">All Students</a>
                    <a class="btn btn-link" href="reports.php">Reports</a>
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
        // Mobile-specific enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-expand filter section on mobile when filters are active
            const hasActiveFilters = <?php echo (!empty($search_query) || !empty($filter_course) || !empty($filter_status)) ? 'true' : 'false'; ?>;
            if (hasActiveFilters && window.innerWidth < 992) {
                const filterCollapse = document.getElementById('filterCollapse');
                if (filterCollapse) {
                    new bootstrap.Collapse(filterCollapse, { toggle: true });
                }
            }
            
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
            
            // Auto-submit form when filters change on mobile (optional)
            if (window.innerWidth >= 992) {
                const filters = document.querySelectorAll('select[name="course_filter"], select[name="status_filter"]');
                filters.forEach(filter => {
                    filter.addEventListener('change', function() {
                        this.form.submit();
                    });
                });
            }
        });

        // Quick search functionality
        function quickSearch() {
            const searchInput = document.querySelector('input[name="search_query"]');
            if (searchInput.value.trim().length >= 2) {
                document.querySelector('form').submit();
            }
        }
    </script>
</body>

</html>