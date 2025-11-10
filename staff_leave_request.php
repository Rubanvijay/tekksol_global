<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in as admin or staff
if (!isset($_SESSION['admin_username']) && !isset($_SESSION['staff_email'])) {
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
$leave_requests = [];
$staff_list = [];

// Default date range (last 30 days)
$date_from = date('Y-m-d', strtotime('-30 days'));
$date_to = date('Y-m-d');
$selected_staff = "";

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get staff list for dropdown
    $sql = "SELECT DISTINCT staff_email FROM staff_leave_request ";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $staff_list[] = $row['staff_email'];
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $date_from = $_POST['date_from'] ?? $date_from;
        $date_to = $_POST['date_to'] ?? $date_to;
        $selected_staff = $_POST['staff_email'] ?? '';
    }
    
    // Build query based on filters
    $sql = "SELECT id, staff_email, leave_dates, leave_reason, request_date, status, admin_notes, updated_at 
            FROM staff_leave_request 
            WHERE status = 'approved'";
    
    $params = [];
    $types = "";
    
    // Add date filter
    if (!empty($date_from) && !empty($date_to)) {
        $sql .= " AND DATE(request_date) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= "ss";
    }
    
    // Add staff filter
    if (!empty($selected_staff)) {
        $sql .= " AND staff_email = ?";
        $params[] = $selected_staff;
        $types .= "s";
    }
    
    $sql .= " ORDER BY request_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $leave_requests[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Staff Leave Request error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Staff Leave Requests - Tekksol Global Admin</title>
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
        
        .leave-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .table thead {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
        }
        
        .status-approved {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-pending {
            color: #ffc107;
            font-weight: 600;
        }
        
        .status-rejected {
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
        
        .leave-dates {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .leave-dates:hover {
            overflow: visible;
            white-space: normal;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 4px;
            position: absolute;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
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

    <!-- Content -->
    <div class="container-xxl py-4">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Reports</h6>
                <h1 class="mb-4">Staff Leave History</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="filter-card">
                <h5 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Leave History</h5>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="staff_email" class="form-label">Staff Email</label>
                            <select class="form-select" id="staff_email" name="staff_email">
                                <option value="">All Staff</option>
                                <?php foreach ($staff_list as $email): ?>
                                    <option value="<?php echo htmlspecialchars($email); ?>" <?php echo $selected_staff == $email ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($email); ?>
                                    </option>
                                <?php endforeach; ?>
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
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="summary-box">
                        <div class="summary-number"><?php echo count($leave_requests); ?></div>
                        <div>Approved Leave Requests</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="summary-box">
                        <div class="summary-number"><?php echo count(array_unique(array_column($leave_requests, 'staff_email'))); ?></div>
                        <div>Staff Members</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="summary-box">
                        <div class="summary-number">
                            <?php
                            $total_days = 0;
                            foreach ($leave_requests as $request) {
                                $dates = explode(',', $request['leave_dates']);
                                $total_days += count($dates);
                            }
                            echo $total_days;
                            ?>
                        </div>
                        <div>Total Leave Days</div>
                    </div>
                </div>
            </div>

            <!-- Leave Requests Report -->
            <?php if (!empty($leave_requests)): ?>
            <div class="report-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><i class="fas fa-calendar-check me-2"></i>Approved Leave Requests</h5>
                    
                </div>
                <div class="leave-table">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Staff Email</th>
                                    <th>Leave Dates</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Request Date</th>
                                    <th>Status</th>
                                    <th>Admin Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leave_requests as $index => $request): 
                                    $dates = explode(',', $request['leave_dates']);
                                    $days_count = count($dates);
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <?php echo htmlspecialchars($request['staff_email']); ?>
                                    </td>
                                    <td>
                                        <div class="leave-dates" title="<?php echo htmlspecialchars($request['leave_dates']); ?>">
                                            <?php 
                                            if ($days_count > 3) {
                                                echo htmlspecialchars(implode(', ', array_slice($dates, 0, 3))) . '...';
                                            } else {
                                                echo htmlspecialchars($request['leave_dates']);
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-calendar-day me-1"></i>
                                            <?php echo $days_count; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($request['leave_reason']); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($request['request_date'])); ?></td>
                                    <td>
                                        <span class="status-approved">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['admin_notes'])): ?>
                                            <span class="text-muted"><?php echo htmlspecialchars($request['admin_notes']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>
                No approved leave requests found for the selected criteria.
            </div>
            <?php endif; ?>

            
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
                    <a class="btn btn-link" href="staff_leave_request.php">Leave History</a>
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
            
            // Set default date range to last 30 days if not set
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            if (dateFrom && !dateFrom.value) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                dateFrom.valueAsDate = thirtyDaysAgo;
            }
            
            if (dateTo && !dateTo.value) {
                dateTo.valueAsDate = new Date();
            }
        });
    </script>
</body> 
</html>