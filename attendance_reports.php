<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.html");
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$error = "";
$staff_attendance = [];
$student_attendance = [];
$report_type = "staff"; // Default
$date_from = date('Y-m-d', strtotime('-30 days'));
$date_to = date('Y-m-d');

// Handle Excel Download
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    $download_type = $_GET['type'] ?? 'staff';
    $download_date_from = $_GET['date_from'] ?? $date_from;
    $download_date_to = $_GET['date_to'] ?? $date_to;
    
    try {
        $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Generate Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="attendance_report_' . $download_type . '_' . date('Y-m-d') . '.xls"');
        
        echo "<table border='1'>";
        
        if ($download_type == 'staff' || $download_type == 'both') {
            // Staff Attendance Data
            $sql = "SELECT employee_id, latitude, longitude, timestamp, DATE(timestamp) as attendance_date, checkin_type, status
                    FROM staff_attendance 
                    WHERE DATE(timestamp) BETWEEN ? AND ?
                    ORDER BY timestamp DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $download_date_from, $download_date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            
         echo "<tr><td colspan='5' style='background:#06BBCC;color:white;text-align:center;font-weight:bold;font-size:16px;'>STAFF ATTENDANCE REPORT (" . $download_date_from . " to " . $download_date_to . ")</td></tr>";
echo "<tr style='background:#f8f9fa;font-weight:bold;'>
        <th>#</th>
        <th>Employee ID</th>
        <th>Date</th>
        <th>Time</th>
        <th>Check-in Type</th>
      </tr>";

$counter = 1;
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>" . $counter . "</td>
            <td>" . htmlspecialchars($row['employee_id']) . "</td>
            <td>" . date('M d, Y', strtotime($row['attendance_date'])) . "</td>
            <td>" . date('h:i A', strtotime($row['timestamp'])) . "</td>
            <td>" . ucfirst(str_replace('_', ' ', $row['checkin_type'])) . "</td>
          </tr>";
    $counter++;
}
            $stmt->close();
            
            if ($download_type == 'both') {
                echo "<tr><td colspan='7' style='height:20px;'></td></tr>";
            }
        }
        
        if ($download_type == 'student' || $download_type == 'both') {
            // Student Attendance Data
            $sql = "SELECT s.name, s.student_id, s.course_domain, 
                           sa.attendance_date, sa.status
                    FROM student_attendance sa
                    JOIN student_details s ON sa.student_id = s.student_id
                    WHERE sa.attendance_date BETWEEN ? AND ?
                    ORDER BY sa.attendance_date DESC, s.name";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $download_date_from, $download_date_to);
            $stmt->execute();
            $result = $stmt->get_result();
            
            echo "<tr><td colspan='6' style='background:#28a745;color:white;text-align:center;font-weight:bold;font-size:16px;'>STUDENT ATTENDANCE REPORT (" . $download_date_from . " to " . $download_date_to . ")</td></tr>";
            echo "<tr style='background:#f8f9fa;font-weight:bold;'>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>";
            
            $counter = 1;
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $counter . "</td>
                        <td>" . htmlspecialchars($row['student_id']) . "</td>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>" . htmlspecialchars($row['course_domain']) . "</td>
                        <td>" . date('M d, Y', strtotime($row['attendance_date'])) . "</td>
                        <td>" . htmlspecialchars($row['status']) . "</td>
                      </tr>";
                $counter++;
            }
            $stmt->close();
        }
        
        echo "</table>";
        $conn->close();
        exit();
        
    } catch (Exception $e) {
        $error = "Download error: " . $e->getMessage();
    }
}

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $report_type = $_POST['report_type'] ?? 'staff';
        $date_from = $_POST['date_from'] ?? $date_from;
        $date_to = $_POST['date_to'] ?? $date_to;
    }
    
    // Get Staff Attendance
    if ($report_type == 'staff' || $report_type == 'both') {
        $sql = "SELECT employee_id, latitude, longitude, timestamp, DATE(timestamp) as attendance_date, checkin_type, status
                FROM staff_attendance 
                WHERE DATE(timestamp) BETWEEN ? AND ?
                ORDER BY timestamp DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $staff_attendance[] = $row;
        }
        $stmt->close();
    }
    
    // Get Student Attendance
    if ($report_type == 'student' || $report_type == 'both') {
        $sql = "SELECT s.name, s.student_id, s.course_domain, 
                       sa.attendance_date, sa.status
                FROM student_attendance sa
                JOIN student_details s ON sa.student_id = s.student_id
                WHERE sa.attendance_date BETWEEN ? AND ?
                ORDER BY sa.attendance_date DESC, s.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $student_attendance[] = $row;
        }
        $stmt->close();
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Attendance Reports error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Attendance Reports - Tekksol Global Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        .report-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .attendance-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .table thead {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
        }
        
        .status-present {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-absent {
            color: #dc3545;
            font-weight: 600;
        }
        
        .summary-box {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .summary-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .location-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-family: monospace;
        }
        
        .download-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .report-card {
                padding: 20px;
                margin-bottom: 15px;
            }
            
            .filter-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .summary-box {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .download-section {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .summary-number {
                font-size: 1.5rem;
            }
            
            .navbar-brand img {
                height: 50px;
                width: 80px;
            }
            
            h1.mb-5 {
                font-size: 1.8rem;
                margin-bottom: 2rem !important;
            }
            
            .section-title {
                font-size: 0.9rem;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .location-badge {
                font-size: 0.75rem;
                padding: 3px 6px;
            }
        }
        
        @media (max-width: 576px) {
            .report-card {
                padding: 15px;
                margin-bottom: 12px;
            }
            
            .filter-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            .summary-box {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .download-section {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .summary-number {
                font-size: 1.3rem;
            }
            
            h1.mb-5 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn {
                font-size: 0.875rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .badge {
                font-size: 0.75rem;
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
        
        
        /* Mobile table improvements */
        .table th, .table td {
            padding: 0.75rem 0.5rem;
        }
        
        @media (max-width: 768px) {
            .table th, .table td {
                padding: 0.5rem 0.3rem;
            }
        }
        
        /* Mobile form improvements */
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        /* Mobile button improvements */
        .btn-group-mobile {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        
        /* Mobile dropdown for navigation */
        .mobile-dropdown {
            width: 100%;
            text-align: left;
            margin-top: 10px;
        }
        
        /* Ensure tables are properly responsive */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }
        
        /* Mobile filter form improvements */
        @media (max-width: 768px) {
            .filter-card .row {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .filter-card .col-md-4,
            .filter-card .col-md-3,
            .filter-card .col-md-2 {
                padding-left: 8px;
                padding-right: 8px;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
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
            <div class="d-lg-none mt-3">
                <div class="dropdown">
                    <button class="btn btn-primary w-100 dropdown-toggle mobile-dropdown" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="view-all-students.php">
                                <i class="fas fa-users me-2"></i> View Students
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="view_staff.php">
                                <i class="fas fa-user-tie me-2"></i> View Staff
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="generate_staff_credentials.php">
                                <i class="fas fa-key me-2"></i> Generate Staff Credentials
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="attendance_reports.php">
                                <i class="fas fa-chart-bar me-2"></i> Attendance Reports
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

    <!-- Content -->
    <div class="container-xxl py-4">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Reports</h6>
                <h1 class="mb-4">Attendance Reports</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="filter-card">
                <h5 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Reports</h5>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="staff" <?php echo $report_type == 'staff' ? 'selected' : ''; ?>>Staff Attendance</option>
                                <option value="student" <?php echo $report_type == 'student' ? 'selected' : ''; ?>>Student Attendance</option>
                                <option value="both" <?php echo $report_type == 'both' ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Generate
                            </button>
                        </div>
                    </div>
                </form>
            </div>

           

            <!-- Summary -->
            <div class="row mb-4">
                <?php if ($report_type == 'staff' || $report_type == 'both'): ?>
                <div class="col-md-6 mb-3">
                    <div class="summary-box">
                        <div class="summary-number"><?php echo count($staff_attendance); ?></div>
                        <div>Staff Check-ins</div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($report_type == 'student' || $report_type == 'both'): ?>
                <div class="col-md-6 mb-3">
                    <div class="summary-box">
                        <div class="summary-number"><?php echo count($student_attendance); ?></div>
                        <div>Student Attendance Records</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Staff Attendance Report -->
            <?php if (($report_type == 'staff' || $report_type == 'both') && !empty($staff_attendance)): ?>
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><i class="fas fa-user-tie me-2"></i>Staff Attendance Report</h5>
                    <a href="?download=excel&type=staff&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Excel
                    </a>
                </div>
                <div class="attendance-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee ID</th>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-in Type</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff_attendance as $index => $record): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <i class="fas fa-user-circle text-primary me-2"></i>
                                        <?php echo htmlspecialchars($record['employee_id']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo date('h:i A', strtotime($record['timestamp'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst(str_replace('_', ' ', $record['checkin_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($record['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($record['latitude']) && !empty($record['longitude'])): ?>
                                            <span class="location-badge">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo round($record['latitude'], 4) . ', ' . round($record['longitude'], 4); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Student Attendance Report -->
            <?php if (($report_type == 'student' || $report_type == 'both') && !empty($student_attendance)): ?>
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><i class="fas fa-users me-2"></i>Student Attendance Report</h5>
                    <a href="?download=excel&type=student&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Download Excel
                    </a>
                </div>
                <div class="attendance-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_attendance as $index => $record): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                                    <td>
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <?php echo htmlspecialchars($record['name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['course_domain']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                                    <td>
                                        <span class="<?php echo $record['status'] == 'Present' ? 'status-present' : 'status-absent'; ?>">
                                            <i class="fas fa-circle me-1"></i>
                                            <?php echo htmlspecialchars($record['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- No Records Message -->
            <?php if (
                (($report_type == 'staff' && empty($staff_attendance)) ||
                ($report_type == 'student' && empty($student_attendance)) ||
                ($report_type == 'both' && empty($staff_attendance) && empty($student_attendance)))
            ): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                No attendance records found for the selected date range.
            </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div class="text-center mt-4">
                <a href="admin_dashboard.php" class="btn btn-primary">
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
            
            // Prevent form zoom on iOS
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.fontSize = '16px';
                });
            });
        });
    </script>
</body> 
</html>