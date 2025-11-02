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
$assignments = [];
$student_name = "";
$success_message = "";

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Handle assignment submission
    if (isset($_POST['submit_assignment']) && isset($_POST['assignment_id'])) {
        $assignment_id = intval($_POST['assignment_id']);
        
        // Verify the assignment belongs to this student and is pending
        $verify_sql = "SELECT id FROM student_task WHERE id = ? AND username = ? AND status = 'pending'";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("is", $assignment_id, $student_username);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Update assignment status to submitted
            $update_sql = "UPDATE student_task SET status = 'submitted', submission_date = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $assignment_id);
            
            if ($update_stmt->execute()) {
                $success_message = "Assignment submitted successfully!";
            } else {
                throw new Exception("Error submitting assignment: " . $update_stmt->error);
            }
            
            $update_stmt->close();
        } else {
            throw new Exception("Assignment not found or already submitted.");
        }
        
        $verify_stmt->close();
    }

    // Get student name
    $name_sql = "SELECT name FROM student_details WHERE username = ?";
    $name_stmt = $conn->prepare($name_sql);
    $name_stmt->bind_param("s", $student_username);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    
    if ($name_result->num_rows > 0) {
        $name_row = $name_result->fetch_assoc();
        $student_name = $name_row['name'];
    }
    $name_stmt->close();

    // Get assignments for this student
    $sql = "SELECT * FROM student_task WHERE username = ? ORDER BY assigned_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Assignments - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Student Assignments, Tekksol Global, Tasks" name="keywords">
    <meta content="View your assignments and tasks from Tekksol Global training institute" name="description">

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
        
        .assignment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 25px;
            border-left: 5px solid #06BBCC;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .assignment-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .assignment-title {
            color: #06BBCC;
            margin-bottom: 10px;
        }
        
        .assignment-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .meta-item i {
            color: #06BBCC;
            width: 16px;
        }
        
        .assignment-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .status-submitted {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .status-graded {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .due-date {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .due-date-future {
            background: #e7f3ff;
            color: #0066cc;
            border: 1px solid #b3d9ff;
        }
        
        .due-date-today {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }
        
        .due-date-past {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .no-assignments {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-assignments i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
        }
        
        .btn-submit:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-view {
            background: #17a2b8;
            color: white;
        }
        
        .btn-view:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
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
        
        .welcome-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .profile-icon {
            font-size: 4rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .submission-info {
            color: #28a745;
            font-weight: 500;
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
                <a href="view_assignment.php" class="nav-item nav-link active">Assignments</a>
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
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="student_attendance.php">
                                <i class="fas fa-book me-2"></i> My Attendance
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="view_assignment.php">
                                <i class="fas fa-tasks me-2"></i> Assignment
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
                    <h1 class="display-5 text-white mb-3">My Assignments</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php echo htmlspecialchars($student_name ?: $_SESSION['student_username']); ?></strong>!
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="status-badge bg-white text-primary">Student Portal</span>
                        <span class="status-badge bg-white text-primary"><?php echo count($assignments); ?> Assignments</span>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Assignment Statistics -->
            <div class="row mb-5">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo count($assignments); ?></div>
                        <div class="stat-label">Total Assignments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number">
                            <?php 
                            $pending = array_filter($assignments, function($a) { 
                                return $a['status'] == 'pending'; 
                            });
                            echo count($pending);
                            ?>
                        </div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stat-number">
                            <?php 
                            $submitted = array_filter($assignments, function($a) { 
                                return $a['status'] == 'submitted'; 
                            });
                            echo count($submitted);
                            ?>
                        </div>
                        <div class="stat-label">Submitted</div>
                    </div>
                </div>
                
                </div>
            </div>

            <!-- Assignments List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($assignments)): ?>
                        <div class="no-assignments">
                            <i class="fas fa-clipboard-list"></i>
                            <h3>No Assignments Yet</h3>
                            <p>You don't have any assignments at the moment. Check back later for new tasks.</p>
                            <a href="student-dashboard.php" class="btn btn-primary mt-3">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-header">
                                    <div class="flex-grow-1">
                                        <h4 class="assignment-title">
                                            <i class="fas fa-tasks me-2"></i>Assignment #<?php echo $assignment['id']; ?>
                                        </h4>
                                        <div class="assignment-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar-alt"></i>
                                                <strong>Assigned:</strong> 
                                                <?php echo date('M d, Y g:i A', strtotime($assignment['assigned_date'])); ?>
                                            </div>
                                            <?php if ($assignment['due_date']): ?>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <strong>Due Date:</strong> 
                                                    <?php 
                                                    $due_date = new DateTime($assignment['due_date']);
                                                    $today = new DateTime();
                                                    $interval = $today->diff($due_date);
                                                    $days_diff = $interval->days;
                                                    
                                                    if ($interval->invert) {
                                                        // Past due
                                                        $due_class = 'due-date-past';
                                                        $due_text = 'Overdue by ' . $days_diff . ' day' . ($days_diff != 1 ? 's' : '');
                                                    } elseif ($days_diff == 0) {
                                                        // Due today
                                                        $due_class = 'due-date-today';
                                                        $due_text = 'Due Today';
                                                    } else {
                                                        // Future due
                                                        $due_class = 'due-date-future';
                                                        $due_text = 'Due in ' . $days_diff . ' day' . ($days_diff != 1 ? 's' : '');
                                                    }
                                                    ?>
                                                    <span class="due-date <?php echo $due_class; ?>">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        <?php echo $due_text; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="meta-item">
                                                <i class="fas fa-user-tie"></i>
                                                <strong>Assigned by:</strong> <?php echo htmlspecialchars($assignment['assigned_by']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="status-badge status-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </div>
                                </div>

                                <div class="assignment-content">
                                    <?php echo htmlspecialchars($assignment['assignment_text']); ?>
                                </div>

                                <div class="assignment-actions">
                                    <?php if ($assignment['status'] == 'pending'): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                            <button type="submit" name="submit_assignment" class="btn btn-action btn-submit" 
                                                    onclick="return confirm('Are you sure you want to submit this assignment? This action cannot be undone.')">
                                                <i class="fas fa-paper-plane me-1"></i>Submit Work
                                            </button>
                                        </form>
                                    <?php elseif ($assignment['status'] == 'submitted'): ?>
                                        <span class="submission-info">
                                            <i class="fas fa-check-circle me-1"></i>
                                            Submitted on <?php echo date('M d, Y g:i A', strtotime($assignment['submission_date'])); ?>
                                        </span>
                                    <?php elseif ($assignment['status'] == 'graded'): ?>
                                        <span class="text-primary">
                                            <i class="fas fa-star me-1"></i>
                                            Graded - View Results
                                        </span>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-action btn-view" onclick="downloadAssignment(<?php echo $assignment['id']; ?>)">
                                        <i class="fas fa-download me-1"></i>Download
                                    </button>
                                    
                                    <button class="btn btn-outline-secondary" onclick="printAssignment(<?php echo $assignment['id']; ?>)">
                                        <i class="fas fa-print me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                    <h4 class="text-white mb-3">Student Resources</h4>
                    <a class="btn btn-link" href="course-materials.html">Course Materials</a>
                    <a class="btn btn-link" href="view_assignment.php">Assignments</a>
                    <a class="btn btn-link" href="progress_report.php">Progress Reports</a>
                    <a class="btn btn-link" href="placement.html">Placement Support</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help with assignments? Contact your trainer</p>
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
        function downloadAssignment(assignmentId) {
            // Create a blob with the assignment text and download it
            const assignmentCard = document.querySelector(`[onclick*="downloadAssignment(${assignmentId})"]`).closest('.assignment-card');
            const assignmentText = assignmentCard.querySelector('.assignment-content').textContent;
            const assignmentTitle = assignmentCard.querySelector('.assignment-title').textContent;
            
            const blob = new Blob([assignmentText], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `assignment-${assignmentId}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        function printAssignment(assignmentId) {
            const assignmentCard = document.querySelector(`[onclick*="printAssignment(${assignmentId})"]`).closest('.assignment-card');
            const printContent = assignmentCard.innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div class="container mt-4">
                    <div class="text-center mb-4">
                        <h2>Tekksol Global - Assignment</h2>
                        <p>Printed on: ${new Date().toLocaleDateString()}</p>
                    </div>
                    ${printContent}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }

        // Auto-refresh page every 5 minutes to check for new assignments
        setInterval(() => {
            // Check if there are any new assignments without refreshing the whole page
            fetch('check_new_assignments.php')
                .then(response => response.json())
                .then(data => {
                    if (data.hasNewAssignments) {
                        if (confirm('New assignments are available! Would you like to refresh the page?')) {
                            location.reload();
                        }
                    }
                })
                .catch(error => console.error('Error checking for new assignments:', error));
        }, 300000); // 5 minutes
    </script>
</body>

</html>