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
if (!isset($_SESSION['staff_email'])) {
    header("Location: staff-login.html");
    exit();
}

$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Create connection
        $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Get form data
        $username = trim($_POST['username']);
        $assignment_text = trim($_POST['assignment_text']);
        $due_date = $_POST['due_date'] ?? null;
        $assigned_by = $_SESSION['staff_email'];

        // Validate required fields
        if (empty($username) || empty($assignment_text)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check if student exists
        $check_sql = "SELECT username FROM students WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("Student username '$username' does not exist.");
        }
        $check_stmt->close();

        // Insert assignment into student_task table
        $sql = "INSERT INTO student_task (username, assignment_text, due_date, assigned_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($due_date) {
            $stmt->bind_param("ssss", $username, $assignment_text, $due_date, $assigned_by);
        } else {
            $stmt->bind_param("ssss", $username, $assignment_text, $due_date, $assigned_by);
        }

        if ($stmt->execute()) {
            $success_message = "Assignment assigned successfully to student: " . htmlspecialchars($username);
            
            // Clear form fields
            $_POST = array();
        } else {
            throw new Exception("Error assigning task: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get recent students for suggestions
$recent_students = [];
try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    if (!$conn->connect_error) {
        $sql = "SELECT DISTINCT username FROM student_details ORDER BY start_date DESC LIMIT 10";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent_students[] = $row['username'];
            }
        }
        $conn->close();
    }
} catch (Exception $e) {
    // Ignore errors for suggestions
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Assignment - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Add Assignment, Tekksol Global, Student Tasks" name="keywords">
    <meta content="Add assignments for students in Tekksol Global training institute" name="description">

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
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            border-radius: 8px;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-radius: 8px;
        }
        
        .assignment-textarea {
            min-height: 300px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            line-height: 1.5;
        }
        
        .suggestion-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .suggestion-item {
            display: inline-block;
            background: white;
            border: 1px solid #06BBCC;
            color: #06BBCC;
            padding: 5px 10px;
            margin: 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        
        .suggestion-item:hover {
            background: #06BBCC;
            color: white;
        }
        
        .character-count {
            text-align: right;
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 120px;
        }
        
        .check-student {
            position: absolute;
            right: 5px;
            top: 5px;
            bottom: 5px;
            background: #06BBCC;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0 15px;
            font-size: 0.875rem;
        }
        
        .student-status {
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .exists { color: #28a745; }
        .not-exists { color: #dc3545; }
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
                <a href="staff-dashboard.php" class="nav-item nav-link ">Dashboard</a>
                <a href="mark_attendance.php" class="nav-item nav-link ">Checkin</a>
                <a href="request_leave_approval.php" class="nav-item nav-link ">Leave Request</a>
                
            </div>
            
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
    <!-- Navbar End -->

    <!-- Add Assignment Section -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-tasks me-2"></i>Add Student Assignment</h2>
                    <p class="mb-0">Create and assign tasks to students</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Recent Students Suggestions -->
                <?php if (!empty($recent_students)): ?>
                <div class="suggestion-box">
                    <h6><i class="fas fa-users me-2"></i>Recent Students</h6>
                    <p class="text-muted mb-2">Click on a username to select:</p>
                    <?php foreach ($recent_students as $student_username): ?>
                        <span class="suggestion-item" onclick="document.getElementById('username').value = '<?php echo $student_username; ?>'; checkStudentExists('<?php echo $student_username; ?>');">
                            <?php echo $student_username; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" id="assignmentForm">
                    <!-- Student Username -->
                    <div class="mb-4">
                        <label class="form-label required-field">Student Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="username" id="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   required placeholder="Enter student username">
                            <button type="button" class="check-student" onclick="checkStudentExists()">
                                Check Student
                            </button>
                        </div>
                        <div class="student-status" id="studentStatus"></div>
                        <small class="text-muted">Enter the username of the student you want to assign this task to.</small>
                    </div>

                    <!-- Due Date (Optional) -->
                    <div class="mb-4">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" 
                               value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                        <small class="text-muted">Optional: Set a deadline for this assignment</small>
                    </div>

                    <!-- Assignment Text -->
                    <div class="mb-4">
                        <label class="form-label required-field">Assignment Details</label>
                        <textarea class="form-control assignment-textarea" name="assignment_text" id="assignment_text" 
                                  required placeholder="Enter assignment details, instructions, code, or any relevant information..."
                                  rows="15"><?php echo htmlspecialchars($_POST['assignment_text'] ?? ''); ?></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span> characters
                        </div>
                        <small class="text-muted">Provide clear instructions and requirements for the assignment.</small>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Assign Task
                        </button>
                        <a href="staff-dashboard.php" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
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
                    <h4 class="text-white mb-3">Staff Resources</h4>
                    <a class="btn btn-link" href="staff-checkin.php">Student Check-in</a>
                    <a class="btn btn-link" href="add-student.php">Add Student</a>
                    <a class="btn btn-link" href="add-assignment.php">Add Assignment</a>
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
        // Check if student exists
        function checkStudentExists(username = null) {
            const usernameInput = document.getElementById('username');
            const usernameToCheck = username || usernameInput.value;
            const statusDiv = document.getElementById('studentStatus');
            
            if (usernameToCheck.length < 1) {
                statusDiv.innerHTML = '<span class="text-muted">Enter a username</span>';
                return;
            }
            
            statusDiv.innerHTML = '<span class="text-muted">Checking student...</span>';
            
            // Create AJAX request to check student
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_student_exists.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.exists) {
                            statusDiv.innerHTML = '<span class="exists"><i class="fas fa-check-circle me-1"></i>Student exists: ' + response.name + '</span>';
                        } else {
                            statusDiv.innerHTML = '<span class="not-exists"><i class="fas fa-times-circle me-1"></i>Student does not exist</span>';
                        }
                    } else {
                        statusDiv.innerHTML = '<span class="not-exists">Error checking student</span>';
                    }
                }
            };
            
            xhr.send('username=' + encodeURIComponent(usernameToCheck));
        }

        // Character count for assignment text
        document.addEventListener('DOMContentLoaded', function() {
            const assignmentText = document.getElementById('assignment_text');
            const charCount = document.getElementById('charCount');
            
            assignmentText.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
            
            // Initialize character count
            charCount.textContent = assignmentText.value.length;
            
            // Auto-check student when user stops typing
            let studentTimeout;
            document.getElementById('username').addEventListener('input', function() {
                clearTimeout(studentTimeout);
                studentTimeout = setTimeout(() => {
                    if (this.value.length >= 1) {
                        checkStudentExists();
                    }
                }, 500);
            });
            
            // Set minimum due date to today
            const dueDateInput = document.querySelector('input[name="due_date"]');
            if (!dueDateInput.value) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                dueDateInput.min = tomorrow.toISOString().split('T')[0];
            }
        });
    </script>
</body>

</html>