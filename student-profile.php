<?php
// student-profile.php
session_start();

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if user is logged in as staff
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

$student_data = [];
$error = "";
$student_username = $_SESSION['student_username'];

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get student data
    $sql = "SELECT * FROM student_details WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
    } else {
        $error = "Student not found!";
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Student Profile - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Student Profile, Tekksol Global" name="keywords">
    <meta content="Student profile details" name="description">

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
        
        /* Student Profile Styles */
        .profile-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .profile-card {
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
            font-weight: 700;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 180px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
            text-align: right;
        }
        
        .profile-icon {
            font-size: 5rem;
            color: white;
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background: #28a745;
            color: white;
        }
        
        .status-inactive {
            background: #dc3545;
            color: white;
        }
        
        .status-completed {
            background: #17a2b8;
            color: white;
        }
        
        .quick-stats {
            background: #f8f9fa;
            border-radius: 10px;
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
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            flex: 1;
            min-width: 150px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .student-header-info {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
        }
        
        .student-header-info p {
            margin-bottom: 5px;
            font-size: 1.1rem;
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
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-checkin.php">
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

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="col-md-10">
                    <?php if (!empty($student_data)): ?>
                        <h1 class="display-5 text-white mb-3"><?php echo htmlspecialchars($student_data['name']); ?></h1>
                        <div class="student-header-info">
                            <p><i class="fas fa-id-card me-2"></i><strong>Student ID:</strong> <?php echo htmlspecialchars($student_data['student_id']); ?></p>
                            <p><i class="fas fa-book me-2"></i><strong>Course:</strong> <?php echo htmlspecialchars($student_data['course_domain']); ?></p>
                            <p><i class="fas fa-calendar me-2"></i><strong>Enrollment Date:</strong> <?php echo date('F d, Y', strtotime($student_data['start_date'])); ?></p>
                            <span class="status-badge status-<?php echo strtolower($student_data['Status']); ?>">
                                <i class="fas fa-circle me-1"></i><?php echo htmlspecialchars($student_data['Status']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <h1 class="display-5 text-white mb-3">Student Profile</h1>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="back-button">
                <a href="student-dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
                <div class="text-center py-5">
                    <a href="staff-dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                </div>
            <?php elseif (!empty($student_data)): ?>
                
                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['duration'] ?? '0'); ?></div>
                            <div class="stat-label">Duration (Months)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number">₹<?php echo htmlspecialchars($student_data['amount_paid'] ?? '0'); ?></div>
                            <div class="stat-label">Amount Paid</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['year_of_passed_out'] ?? 'N/A'); ?></div>
                            <div class="stat-label">Passout Year</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['course_mode'] ?? 'N/A'); ?></div>
                            <div class="stat-label">Course Mode</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="info-card">
                            <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                            <div class="info-item">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Username:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['username']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Student ID:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['student_id']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date of Birth:</span>
                                <span class="info-value"><?php echo date('F d, Y', strtotime($student_data['DOB'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mobile No:</span>
                                <span class="info-value">
                                    <a href="tel:<?php echo htmlspecialchars($student_data['mobile_no']); ?>">
                                        <?php echo htmlspecialchars($student_data['mobile_no']); ?>
                                    </a>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value">
                                    <a href="mailto:<?php echo htmlspecialchars($student_data['email']); ?>">
                                        <?php echo htmlspecialchars($student_data['email']); ?>
                                    </a>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['location']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="info-card">
                            <h5><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                            <div class="info-item">
                                <span class="info-label">Degree:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['Degree']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Specialization:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['specialization']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Year of Passout:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['year_of_passed_out']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Program Type:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['Type']); ?></span>
                            </div>
                        </div>

                        <!-- Status Information -->
                        <div class="info-card">
                            <h5><i class="fas fa-info-circle me-2"></i>Status Information</h5>
                            <div class="info-item">
                                <span class="info-label">Current Status:</span>
                                <span class="info-value">
                                    <span class="badge bg-<?php echo $student_data['Status'] == 'Active' ? 'success' : 'danger'; ?>">
                                        <?php echo htmlspecialchars($student_data['Status']); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Amount Paid:</span>
                                <span class="info-value">₹<?php echo htmlspecialchars($student_data['amount_paid']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Course Details -->
                    <div class="col-lg-12 mb-4">
                        <div class="info-card">
                            <h5><i class="fas fa-book me-2"></i>Course Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Course Domain:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($student_data['course_domain']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Course Mode:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($student_data['course_mode']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Start Date:</span>
                                        <span class="info-value"><?php echo date('F d, Y', strtotime($student_data['start_date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">End Date:</span>
                                        <span class="info-value"><?php echo date('F d, Y', strtotime($student_data['End_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <span class="info-label">Duration:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($student_data['duration']); ?> months</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Class Timing:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($student_data['timing']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Trainer Name:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($student_data['trainer_name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <div class="info-card">
                            <h5><i class="fas fa-cog me-2"></i>Actions</h5>
                            <div class="action-buttons">
                                <a href="edit-student.php?student_id=<?php echo urlencode($student_data['student_id']); ?>" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Profile
                                </a>
                                <a href="student_attendance.php?student_id=<?php echo urlencode($student_data['student_id']); ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar-check me-2"></i>View Attendance
                                </a>
                                <a href="student_assignments.php?student_id=<?php echo urlencode($student_data['student_id']); ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-tasks me-2"></i>Assignments
                                </a>
                             
                                <button onclick="window.print()" class="btn btn-outline-secondary">
                                    <i class="fas fa-print me-2"></i>Print Profile
                                </button>
                            </div>
                        </div>
                    </div>
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
        // Print styling
        window.onbeforeprint = function() {
            document.querySelector('.navbar').style.display = 'none';
            document.querySelector('.footer').style.display = 'none';
            document.querySelector('.back-button').style.display = 'none';
            document.querySelector('.action-buttons').style.display = 'none';
        };
        
        window.onafterprint = function() {
            document.querySelector('.navbar').style.display = 'block';
            document.querySelector('.footer').style.display = 'block';
            document.querySelector('.back-button').style.display = 'block';
            document.querySelector('.action-buttons').style.display = 'flex';
        };
    </script>
</body>

</html>