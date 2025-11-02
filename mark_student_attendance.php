<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in as staff
if (!isset($_SESSION['staff_username'])) {
    header("Location: staff-login.html");
    exit();
}

$current_staff_username = $_SESSION['staff_username'];

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$error = "";
$success = "";
$students = [];
$selected_date = date('Y-m-d');

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle date selection
    if (isset($_POST['selected_date'])) {
        $selected_date = $_POST['selected_date'];
    }
    
    // Handle attendance submission
    if (isset($_POST['submit_attendance'])) {
        $attendance_date = $_POST['attendance_date'];
        $attendance_data = $_POST['attendance'] ?? [];
        
        if (empty($attendance_data)) {
            $error = "Please mark attendance for at least one student.";
        } else {
            $success_count = 0;
            $error_count = 0;
            
            foreach ($attendance_data as $student_id => $status) {
                // Verify student belongs to current trainer
                $verify_sql = "SELECT student_id FROM student_details WHERE student_id = ? AND trainer_name = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param("ss", $student_id, $current_staff_username);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                
                if ($verify_result->num_rows > 0) {
                    // Check if attendance already exists
                    $check_sql = "SELECT attendance_id FROM student_attendance WHERE student_id = ? AND attendance_date = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ss", $student_id, $attendance_date);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        // Update existing attendance
                        $update_sql = "UPDATE student_attendance SET status = ?, marked_by = ? WHERE student_id = ? AND attendance_date = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("ssss", $status, $current_staff_username, $student_id, $attendance_date);
                        
                        if ($update_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        $update_stmt->close();
                    } else {
                        // Insert new attendance
                        $insert_sql = "INSERT INTO student_attendance (student_id, attendance_date, status, marked_by) VALUES (?, ?, ?, ?)";
                        $insert_stmt = $conn->prepare($insert_sql);
                        $insert_stmt->bind_param("ssss", $student_id, $attendance_date, $status, $current_staff_username);
                        
                        if ($insert_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                        $insert_stmt->close();
                    }
                    $check_stmt->close();
                } else {
                    $error_count++;
                }
                $verify_stmt->close();
            }
            
            if ($success_count > 0) {
                $success = "Successfully marked attendance for {$success_count} student(s).";
            }
            if ($error_count > 0) {
                $error = "Failed to mark attendance for {$error_count} student(s).";
            }
        }
    }
    
    // Get students assigned to current trainer with today's attendance status
    $sql = "SELECT 
                sd.student_id, 
                sd.name, 
                sd.course_domain, 
                sd.Status,
                sa.status as attendance_status,
                sa.attendance_id
            FROM student_details sd
            LEFT JOIN student_attendance sa ON sd.student_id = sa.student_id AND sa.attendance_date = ?
            WHERE sd.trainer_name = ? AND sd.Status = 'Active'
            ORDER BY sd.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $selected_date, $current_staff_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    
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
    <title>Mark Student Attendance - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Mark Student Attendance, Tekksol Global" name="keywords">
    <meta content="Mark attendance for students" name="description">

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
        
        .student-row {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .student-row:hover {
            background: #e9ecef;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .student-name {
            font-weight: 600;
            color: #06BBCC;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .student-details {
            color: #666;
            font-size: 0.9rem;
        }
        
        .attendance-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .attendance-btn {
            flex: 1;
            min-width: 100px;
            padding: 10px 20px;
            border: 2px solid;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .btn-present {
            border-color: #28a745;
            color: #28a745;
            background: white;
        }
        
        .btn-present:hover, .btn-present.active {
            background: #28a745;
            color: white;
        }
        
        .btn-absent {
            border-color: #dc3545;
            color: #dc3545;
            background: white;
        }
        
        .btn-absent:hover, .btn-absent.active {
            background: #dc3545;
            color: white;
        }
        
        .btn-leave {
            border-color: #ffc107;
            color: #856404;
            background: white;
        }
        
        .btn-leave:hover, .btn-leave.active {
            background: #ffc107;
            color: #856404;
        }
        
        .date-selector {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .submit-btn {
            background: #06BBCC;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: #0596a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .marked-badge {
            background: #17a2b8;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .attendance-buttons {
                flex-direction: column;
            }
            
            .attendance-btn {
                min-width: 100%;
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
                <a href="staff-dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars((String)$_SESSION['staff_username']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li>
                            <a class="dropdown-item" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="mark_attendance.php">
                                <i class="fas fa-check-circle me-2"></i> Staff Check-in
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="mark_student_attendance.php">
                                <i class="fas fa-calendar-check me-2"></i> Student Attendance
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
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
                    <h1 class="display-5 text-white mb-3">
                        <i class="fas fa-calendar-check me-3"></i>Mark Student Attendance
                    </h1>
                    <p class="text-white mb-0">
                        Trainer: <strong><?php echo htmlspecialchars((String)$current_staff_username); ?></strong>
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($students); ?></div>
                        <div class="stats-label">Active Students</div>
                    </div>
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

            <!-- Date Selector -->
            <div class="date-selector">
                <form method="POST" action="">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar me-2"></i>Select Date
                            </label>
                            <input type="date" 
                                   class="form-control form-control-lg" 
                                   name="selected_date" 
                                   value="<?php echo $selected_date; ?>"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   onchange="this.form.submit()">
                        </div>
                        <div class="col-md-4 text-end">
                            <label class="form-label fw-bold d-block">&nbsp;</label>
                            <span class="badge bg-primary fs-6 p-3">
                                <?php echo date('l, F j, Y', strtotime($selected_date)); ?>
                            </span>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($students)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No active students assigned to you.
                </div>
            <?php else: ?>
                <form method="POST" action="" id="attendanceForm">
                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">
                    
                    <div class="attendance-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Student List (<?php echo count($students); ?>)
                            </h5>
                            <div>
                                <button type="button" class="btn btn-outline-success me-2" onclick="markAll('Present')">
                                    <i class="fas fa-check me-1"></i>Mark All Present
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="clearAll()">
                                    <i class="fas fa-times me-1"></i>Clear All
                                </button>
                            </div>
                        </div>

                        <?php foreach ($students as $student): ?>
                            <div class="student-row">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <div class="student-name">
                                            <?php echo htmlspecialchars((String)$student['name']); ?>
                                            <?php if ($student['attendance_status']): ?>
                                                <span class="marked-badge ms-2">
                                                    <i class="fas fa-check me-1"></i>Marked
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="student-details">
                                            <i class="fas fa-id-card me-1"></i><?php echo htmlspecialchars((String)$student['student_id']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-book me-1"></i><?php echo htmlspecialchars((String)$student['course_domain']); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="attendance-buttons">
                                            <label class="attendance-btn btn-present <?php echo ($student['attendance_status'] == 'Present') ? 'active' : ''; ?>">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                       value="Present"
                                                       <?php echo ($student['attendance_status'] == 'Present') ? 'checked' : ''; ?>
                                                       style="display: none;">
                                                <i class="fas fa-check-circle me-1"></i>Present
                                            </label>
                                            <label class="attendance-btn btn-absent <?php echo ($student['attendance_status'] == 'Absent') ? 'active' : ''; ?>">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                       value="Absent"
                                                       <?php echo ($student['attendance_status'] == 'Absent') ? 'checked' : ''; ?>
                                                       style="display: none;">
                                                <i class="fas fa-times-circle me-1"></i>Absent
                                            </label>
                                            <label class="attendance-btn btn-leave <?php echo ($student['attendance_status'] == 'Leave') ? 'active' : ''; ?>">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['student_id']; ?>]" 
                                                       value="Leave"
                                                       <?php echo ($student['attendance_status'] == 'Leave') ? 'checked' : ''; ?>
                                                       style="display: none;">
                                                <i class="fas fa-calendar-times me-1"></i>Leave
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-4">
                            <button type="submit" name="submit_attendance" class="submit-btn">
                                <i class="fas fa-save me-2"></i>Submit Attendance
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="staff-dashboard.php" class="btn btn-outline-primary btn-lg">
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
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Tekksol Global, OMR, Chennai</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+91 9042527746</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@tekksolglobal.com</p>
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
    <script src="js/main.js"></script>

    <script>
        // Handle attendance button clicks
        document.querySelectorAll('.attendance-btn').forEach(button => {
            button.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                const parentButtons = this.parentElement.querySelectorAll('.attendance-btn');
                
                // Remove active class from all buttons in this group
                parentButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Check the radio button
                radio.checked = true;
            });
        });

        // Mark all as present
        function markAll(status) {
            const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
            radios.forEach(radio => {
                radio.checked = true;
                const label = radio.closest('.attendance-btn');
                const parentButtons = label.parentElement.querySelectorAll('.attendance-btn');
                parentButtons.forEach(btn => btn.classList.remove('active'));
                label.classList.add('active');
            });
        }

        // Clear all selections
        function clearAll() {
            document.querySelectorAll('.attendance-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.checked = false;
            });
        }

        // Form validation
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const checkedRadios = document.querySelectorAll('input[type="radio"]:checked');
            if (checkedRadios.length === 0) {
                e.preventDefault();
                alert('Please mark attendance for at least one student.');
                return false;
            }
            
            return confirm(`Are you sure you want to submit attendance for ${checkedRadios.length} student(s)?`);
        });
    </script>
</body>

</html>
