<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Debug session
error_log("Admin Leave Approval - Session data: " . print_r($_SESSION, true));

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

$admin_username = $_SESSION['admin_username'];
$success = "";
$error = "";
$pending_requests = [];
$all_requests = [];
$current_month_stats = [];
$staff_leave_calendar = [];

// Handle admin actions
try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
  // Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        $action = $_POST['action'];
        $admin_notes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : '';
        
        // Map action to correct ENUM values
        $status = '';
        if ($action === 'approve') {
            $status = 'approved';
        } elseif ($action === 'reject') {
            $status = 'rejected';
        }
        
        if ($status) {
            $sql = "UPDATE staff_leave_request SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ssi", $status, $admin_notes, $request_id);
                
                if ($stmt->execute()) {
                    $success = "Leave request " . $status . " successfully!";
                } else {
                    $error = "Error updating leave request: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Error preparing statement: " . $conn->error;
            }
        }
    }
}
    
    // Get pending leave requests
    $sql = "SELECT * FROM staff_leave_request WHERE status = 'pending' ORDER BY request_date DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    
    // Get all leave requests
    $sql = "SELECT * FROM staff_leave_request ORDER BY request_date DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $all_requests[] = $row;
    }
    
    // Get current month statistics
    $current_month = date('Y-m');
    $sql = "SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_requests,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_requests,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
                COUNT(DISTINCT staff_email) as unique_staff
            FROM staff_leave_request 
            WHERE DATE_FORMAT(request_date, '%Y-%m') = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_month);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_month_stats = $result->fetch_assoc();
    $stmt->close();
    
    // Get staff leave calendar data for current month
    $sql = "SELECT staff_email, leave_dates, status 
            FROM staff_leave_request 
            WHERE status = 'approved' 
            AND leave_dates LIKE ?";
    $search_month = date('Y-m') . '%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search_month);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $dates = explode(',', $row['leave_dates']);
        foreach ($dates as $date) {
            if (strpos($date, date('Y-m')) === 0) { // Only include current month dates
                $staff_leave_calendar[] = [
                    'staff_email' => $row['staff_email'],
                    'date' => $date,
                    'status' => $row['status']
                ];
            }
        }
    }
    $stmt->close();
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Admin leave approval error: " . $e->getMessage());
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

// Function to get staff name from email
function getStaffName($email) {
    $username = explode('@', $email)[0];
    return ucfirst($username);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Admin Leave Approval - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Leave Approval, Tekksol Global, Admin Portal" name="keywords">
    <meta content="Admin leave approval dashboard for Tekksol Global" name="description">

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
        /* Admin Dashboard Styles */
        .page-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #06BBCC;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #06BBCC;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
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
        
        /* Tables */
        .leave-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .leave-table th,
        .leave-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .leave-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Calendar Styles */
        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .calendar-day {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            min-height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        
        .calendar-day.header {
            background: #f8f9fa;
            font-weight: 600;
            min-height: auto;
        }
        
        .calendar-day.leave {
            background: #06BBCC;
            color: white;
            position: relative;
        }
        
        .calendar-day.sunday {
            background: #ffe6e6;
            color: #dc3545;
        }
        
        .calendar-day.today {
            border: 2px solid #06BBCC;
        }
        
        .leave-staff {
            font-size: 0.7rem;
            margin-top: 2px;
            background: rgba(255,255,255,0.2);
            padding: 1px 4px;
            border-radius: 3px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .calendar-nav {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #06BBCC;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 10px;
            border: none;
        }
        
        .modal-header {
            background: #06BBCC;
            color: white;
            border-radius: 10px 10px 0 0;
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
        .navbar-nav {
    white-space: nowrap;
    flex-wrap: nowrap;
}

.navbar-nav .nav-link {
    white-space: nowrap;
    padding: 8px 10px !important;
    font-size: 0.85rem;
    margin: 0 2px;
}

/* Reduce container padding for navbar */
.navbar > .container {
    max-width: 100%;
    padding-left: 10px;
    padding-right: 10px;
}

/* Make the dropdown button more compact */
.btn-primary.py-4.px-lg-5 {
    padding-top: 12px !important;
    padding-bottom: 12px !important;
    padding-left: 20px !important;
    padding-right: 20px !important;
}
.navbar-brand img {
    height: 50px !important;
    width: 80px !important;
}

.navbar-brand {
    padding-left: 10px !important;
    padding-right: 10px !important;
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
                <a href="admin_dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="view-all-students.php" class="nav-item nav-link">View Students</a>
                <a href="view_staff.php" class="nav-item nav-link">View Staff</a>
              
                <a href="add_careers.php" class="nav-item nav-link">Add Careers</a>
                <a href="attendance_reports.php" class="nav-item nav-link">Attendance Report</a>
            </div>
            
            <!-- Admin Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="request_leave_approval_admin.php">
                                <i class="fas fa-calendar-check me-2"></i> Leave Approval
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
                    <h1 class="display-5 text-white mb-3">Leave Request Approval</h1>
                    <p class="text-white mb-0">
                        Manage all staff leave requests, approve or reject with remarks, and monitor leave calendar.
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Content -->
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
            
            <!-- Statistics Cards -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $current_month_stats['total_requests'] ?? 0; ?></div>
                        <div class="stats-label">Total Requests</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $current_month_stats['approved_requests'] ?? 0; ?></div>
                        <div class="stats-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $current_month_stats['pending_requests'] ?? 0; ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $current_month_stats['unique_staff'] ?? 0; ?></div>
                        <div class="stats-label">Staff on Leave</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Leave Calendar -->
                

                <!-- Leave Requests -->
                <div class="col-lg-12 mb-4">
                    <div class="info-card">
                        <h5><i class="fas fa-clipboard-list me-2"></i>Leave Requests Management</h5>
                        
                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="leaveTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                    Pending (<?php echo count($pending_requests); ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                    All Requests (<?php echo count($all_requests); ?>)
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="leaveTabsContent">
                            <!-- Pending Requests Tab -->
                            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                                <?php if (!empty($pending_requests)): ?>
                                    <div class="table-responsive">
                                        <table class="leave-table">
                                            <thead>
                                                <tr>
                                                    <th>Staff</th>
                                                    <th>Leave Dates</th>
                                                    <th>Reason</th>
                                                    <th>Request Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pending_requests as $request): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo getStaffName($request['staff_email']); ?></strong>
                                                            <br><small class="text-muted"><?php echo $request['staff_email']; ?></small>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $dates = explode(',', $request['leave_dates']);
                                                            foreach ($dates as $date): 
                                                            ?>
                                                                <div><?php echo date('M d, Y', strtotime($date)); ?></div>
                                                            <?php endforeach; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($request['leave_reason']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <button class="btn btn-success btn-sm" onclick="openApproveModal(<?php echo $request['id']; ?>)">
                                                                    <i class="fas fa-check"></i> Approve
                                                                </button>
                                                                <button class="btn btn-danger btn-sm" onclick="openRejectModal(<?php echo $request['id']; ?>)">
                                                                    <i class="fas fa-times"></i> Reject
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No pending leave requests.</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- All Requests Tab -->
                            <div class="tab-pane fade" id="all" role="tabpanel">
                                <?php if (!empty($all_requests)): ?>
                                    <div class="table-responsive">
                                        <table class="leave-table">
                                            <thead>
                                                <tr>
                                                    <th>Staff</th>
                                                    <th>Leave Dates</th>
                                                    <th>Reason</th>
                                                    <th>Request Date</th>
                                                    <th>Status</th>
                                                    <th>Admin Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($all_requests as $request): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo getStaffName($request['staff_email']); ?></strong>
                                                            <br><small class="text-muted"><?php echo $request['staff_email']; ?></small>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $dates = explode(',', $request['leave_dates']);
                                                            foreach ($dates as $date): 
                                                            ?>
                                                                <div><?php echo date('M d, Y', strtotime($date)); ?></div>
                                                            <?php endforeach; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($request['leave_reason']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                        <td>
                                                            <span class="status-badge <?php echo getStatusBadgeClass($request['status']); ?>">
                                                                <?php echo ucfirst($request['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($request['admin_notes'] ?? 'No notes'); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No leave requests found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Approve Leave Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="request_id" id="approveRequestId">
                        <div class="mb-3">
                            <label for="approveNotes" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="approveNotes" name="admin_notes" rows="3" placeholder="Add any remarks for the staff member..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Leave Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="request_id" id="rejectRequestId">
                        <div class="mb-3">
                            <label for="rejectNotes" class="form-label">Remarks (Required)</label>
                            <textarea class="form-control" id="rejectNotes" name="admin_notes" rows="3" placeholder="Please provide reason for rejection..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Leave</button>
                    </div>
                </form>
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
                    <a class="btn btn-link" href="admin_dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="request_leave_approval_admin.php">Leave Approval</a>
                    <a class="btn btn-link" href="view_staff.php">Staff Management</a>
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
    
    <!-- Custom Calendar Script -->
    <script>
        let calendarDate = new Date();
        
        // Modal functions
        function openApproveModal(requestId) {
            document.getElementById('approveRequestId').value = requestId;
            document.getElementById('approveNotes').value = '';
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }
        
        function openRejectModal(requestId) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectNotes').value = '';
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
        
        // Calendar functions
        function renderCalendar(date) {
            const calendar = document.getElementById('leaveCalendar');
            const currentMonthYear = document.getElementById('currentMonthYear');
            
            // Clear previous calendar
            calendar.innerHTML = '';
            
            // Set month and year
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            currentMonthYear.textContent = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            
            // Add day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day header';
                dayElement.textContent = day;
                calendar.appendChild(dayElement);
            });
            
            // Get first day of month and total days
            const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
            const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
            
            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'calendar-day empty';
                emptyCell.textContent = '';
                calendar.appendChild(emptyCell);
            }
            
            // Add days of the month
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.innerHTML = `<div>${day}</div>`;
                
                const currentDay = new Date(date.getFullYear(), date.getMonth(), day);
                const dayOfWeek = currentDay.getDay();
                const dateString = formatDate(currentDay);
                
                // Check if it's Sunday
                if (dayOfWeek === 0) {
                    dayElement.classList.add('sunday');
                }
                
                // Check if it's today
                if (currentDay.toDateString() === today.toDateString()) {
                    dayElement.classList.add('today');
                }
                
                // Check if this date has any approved leaves
                <?php 
                if (!empty($staff_leave_calendar)) {
                    echo "const leaveData = " . json_encode($staff_leave_calendar) . ";";
                    echo "const dayLeaves = leaveData.filter(leave => leave.date === dateString);";
                    echo "if (dayLeaves.length > 0) {";
                    echo "    dayElement.classList.add('leave');";
                    echo "    dayLeaves.forEach(leave => {";
                    echo "        const staffBadge = document.createElement('div');";
                    echo "        staffBadge.className = 'leave-staff';";
                    echo "        staffBadge.textContent = leave.staff_email.split('@')[0];";
                    echo "        staffBadge.title = leave.staff_email;";
                    echo "        dayElement.appendChild(staffBadge);";
                    echo "    });";
                    echo "}";
                } else {
                    echo "const dayLeaves = [];";
                }
                ?>
                
                calendar.appendChild(dayElement);
            }
        }
        
        function changeCalendarMonth(direction) {
            calendarDate.setMonth(calendarDate.getMonth() + direction);
            renderCalendar(calendarDate);
        }
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar(calendarDate);
        });
    </script>
</body>

</html>