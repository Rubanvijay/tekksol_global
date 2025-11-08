<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("View Global Team Records - Session data: " . print_r($_SESSION, true));

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

$reports = [];
$walkin_details = [];
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');
$filter_hr = $_GET['filter_hr'] ?? '';

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Build query based on filters
    $sql = "SELECT dwr.*, 
                   COUNT(wd.id) as actual_walkin_count,
                   GROUP_CONCAT(DISTINCT wd.name) as walkin_names
            FROM daily_walkin_reports dwr 
            LEFT JOIN walkin_details wd ON dwr.id = wd.report_id";
    
    $where_conditions = [];
    $params = [];
    $types = "";
    
    if (!empty($filter_date)) {
        $where_conditions[] = "dwr.report_date = ?";
        $params[] = $filter_date;
        $types .= "s";
    }
    
    if (!empty($filter_hr)) {
        $where_conditions[] = "dwr.hr_name LIKE ?";
        $params[] = "%" . $filter_hr . "%";
        $types .= "s";
    }
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $sql .= " GROUP BY dwr.id ORDER BY dwr.report_date DESC, dwr.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    $stmt->close();
    
    // Get walkin details for selected report if requested
    if (isset($_GET['view_details']) && !empty($_GET['report_id'])) {
        $report_id = intval($_GET['report_id']);
        $details_sql = "SELECT * FROM walkin_details WHERE report_id = ?";
        $details_stmt = $conn->prepare($details_sql);
        $details_stmt->bind_param("i", $report_id);
        $details_stmt->execute();
        $details_result = $details_stmt->get_result();
        
        while ($detail = $details_result->fetch_assoc()) {
            $walkin_details[] = $detail;
        }
        $details_stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log("View Global Team Records error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Global Team Records - Admin - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Admin Dashboard, Tekksol Global, Global Team Records" name="keywords">
    <meta content="Admin dashboard to view global team daily walk-in reports" name="description">

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
        
        /* Admin Dashboard Styles - BLUE THEME */
        .dashboard-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 30px 0 20px;
            margin-bottom: 20px;
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
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
        
        .stats-card {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        /* Improved Table Responsiveness */
        .table-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            background: white;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            width: 100%;
            min-width: 800px; /* Minimum width for the table */
            margin-bottom: 0;
        }
        
        .table th {
            background: #06BBCC;
            color: white;
            border: none;
            padding: 12px 8px;
            font-weight: 600;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .table td {
            padding: 12px 8px;
            vertical-align: middle;
            border-color: #f1f1f1;
            font-size: 0.85rem;
        }
        
        /* Mobile-optimized table */
        @media (max-width: 768px) {
            .table {
                min-width: 1000px; /* Wider minimum width for mobile to show all columns */
            }
            
            .table th,
            .table td {
                padding: 10px 6px;
                font-size: 0.8rem;
            }
            
            /* Make important columns more visible */
            .table td:nth-child(1), /* HR Name */
            .table td:nth-child(2), /* Report Date */
            .table td:nth-child(7) { /* Actions */
                font-weight: 500;
            }
        }
        
        /* Extra small devices */
        @media (max-width: 576px) {
            .table {
                min-width: 1100px; /* Even wider for very small screens */
            }
            
            .table th,
            .table td {
                padding: 8px 4px;
                font-size: 0.75rem;
            }
            
            .table-container {
                margin: 0 -15px;
                border-radius: 0;
            }
            
            .table-responsive {
                border-radius: 0;
            }
        }
        
        .badge-calls {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-today {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-tomorrow {
            background: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 6px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.75rem;
            border: none;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
            color: white;
            transform: translateY(-2px);
        }
        
        .walkin-details-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .walkin-details-modal .modal-header {
            background: #06BBCC;
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .detail-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #06BBCC;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border-top: 4px solid #06BBCC;
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
        
        .btn-outline-primary {
            border-color: #06BBCC;
            color: #06BBCC;
        }
        
        .btn-outline-primary:hover {
            background-color: #06BBCC;
            color: white;
        }
        
        /* Scroll indicator for mobile */
        .scroll-hint {
            display: none;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            color: #6c757d;
            font-size: 0.8rem;
            border-top: 1px solid #dee2e6;
        }
        
        @media (max-width: 768px) {
            .scroll-hint {
                display: block;
            }
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
            
            .filter-card, .info-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .stats-card, .quick-stats {
                padding: 12px;
                margin-bottom: 10px;
            }
            
            .stats-number, .stat-number {
                font-size: 1.3rem;
            }
            
            .navbar-brand img {
                height: 50px;
                width: 80px;
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
            
            .stats-number, .stat-number {
                font-size: 1.2rem;
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
                <a href="admin_dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="view_global_team_record.php" class="nav-item nav-link active">Global Team Records</a>
                <a href="view_staff.php" class="nav-item nav-link">View Staff</a>
               
            </div>
            
            <!-- Admin Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-3 px-4 dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li>
                            <a class="dropdown-item" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-3"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="view_global_team_record.php">
                                <i class="fas fa-globe-americas me-3"></i><span>Global Team Records</span>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="view_staff.php">
                                <i class="fas fa-users-cog me-3"></i><span>View Staff</span>
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
                    <h1 class="display-6 text-white mb-3">Global Team Records</h1>
                    <p class="welcome-text text-white">
                        Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                    </p>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="status-badge me-2" style="background: white; color: #06BBCC;">Administrator</span>
                        <span class="status-badge me-2" style="background: #28a745; color: white;">
                            <i class="fas fa-database me-1"></i>View All Reports
                        </span>
                        <span class="status-badge" style="background: #17a2b8; color: white;">
                            <i class="fas fa-clock me-1"></i><?php echo date('F j, Y'); ?>
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
            <!-- Filters -->
            <div class="filter-card">
                <h4 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Reports</h4>
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filter_hr" class="form-label">HR Email</label>
                                <input type="text" class="form-control" id="filter_hr" name="filter_hr" value="<?php echo htmlspecialchars($filter_hr); ?>" placeholder="Search by HR Email...">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            <?php if (!empty($reports)): ?>
            <div class="row mb-4">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($reports); ?></div>
                        <div class="stats-label">Total Reports</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php echo array_sum(array_column($reports, 'no_of_calls')); ?>
                        </div>
                        <div class="stats-label">Total Calls</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php echo array_sum(array_column($reports, 'today_walkin_count')); ?>
                        </div>
                        <div class="stats-label">Today Walk-ins</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php echo array_sum(array_column($reports, 'tomorrow_walkin_count')); ?>
                        </div>
                        <div class="stats-label">Tomorrow Expected</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reports Table -->
            <div class="table-container">
                <div class="table-responsive">
                    <?php if (!empty($reports)): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>HR Name</th>
                                    <th>Date</th>
                                    <th>Calls</th>
                                    <th>Today</th>
                                    <th>Actual</th>
                                    <th>Tomorrow</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($report['hr_name']); ?></strong></td>
                                        <td><?php echo date('M j, Y', strtotime($report['report_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-calls"><?php echo $report['no_of_calls']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-today"><?php echo $report['today_walkin_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-today"><?php echo $report['actual_walkin_count']; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-tomorrow"><?php echo $report['tomorrow_walkin_count']; ?></span>
                                        </td>
                                        <td><?php echo date('M j, g:i A', strtotime($report['created_at'])); ?></td>
                                        <td>
                                            <?php if ($report['actual_walkin_count'] > 0): ?>
                                                <button type="button" class="btn btn-view action-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#walkinDetailsModal"
                                                        data-report-id="<?php echo $report['id']; ?>"
                                                        data-hr-name="<?php echo htmlspecialchars($report['hr_name']); ?>"
                                                        data-report-date="<?php echo $report['report_date']; ?>">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-inbox"></i>
                            <h4>No Reports Found</h4>
                            <p>No daily walk-in reports found for the selected criteria.</p>
                            <a href="view_global_team_record.php" class="btn btn-primary mt-3">
                                <i class="fas fa-refresh me-2"></i>Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="scroll-hint">
                    <i class="fas fa-arrow-left-right me-2"></i>Scroll horizontally to view all columns
                </div>
            </div>
        </div>
    </div>

    <!-- Walk-in Details Modal -->
    <div class="modal fade walkin-details-modal" id="walkinDetailsModal" tabindex="-1" aria-labelledby="walkinDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="walkinDetailsModalLabel">
                        <i class="fas fa-walking me-2"></i>Walk-in Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="walkinDetailsContent">
                        <!-- Details will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                    <a class="btn btn-link" href="admin_dashboard.php">Admin Dashboard</a>
                    <a class="btn btn-link" href="view_staff.php">View Staff</a>
                    
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
                            <a href="admin-dashboard.php">Dashboard</a>
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
            // Handle modal opening for walk-in details
            $('#walkinDetailsModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var reportId = button.data('report-id');
                var hrName = button.data('hr-name');
                var reportDate = button.data('report-date');
                
                var modal = $(this);
                modal.find('.modal-title').html(
                    '<i class="fas fa-walking me-2"></i>Walk-in Details - ' + hrName + ' (' + reportDate + ')'
                );
                
                // Load walk-in details via AJAX
                $.ajax({
                    url: 'get_walkin_details.php',
                    type: 'GET',
                    data: { report_id: reportId },
                    success: function(response) {
                        $('#walkinDetailsContent').html(response);
                    },
                    error: function() {
                        $('#walkinDetailsContent').html(
                            '<div class="alert alert-danger">Error loading walk-in details. Please try again.</div>'
                        );
                    }
                });
            });

            // Mobile-specific enhancements
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