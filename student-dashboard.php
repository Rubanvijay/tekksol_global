<?php
// student-dashboard.php
session_start();

// Database configuration
$servername = "localhost";
$dbusername = "root";
$dbpassword = "ruban";
$db = "tekksol_global";

// Check if user is logged in
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

$student_data = [];
$error = "";

try {
    // Create connection
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get student data using the provided SQL query
    $sql = "SELECT * FROM student_details sd 
            JOIN students s ON s.username = sd.username 
            WHERE s.username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['student_username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student_data = $result->fetch_assoc();
    } else {
        $error = "Student data not found!";
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
    <title>Student Dashboard - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Student Dashboard, Tekksol Global, Learning Platform" name="keywords">
    <meta content="Student dashboard for Tekksol Global training institute" name="description">

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
        
        /* Student Dashboard Styles */
        .dashboard-header {
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
        }
        
        .info-item {
            display: flex;
            justify-content: between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
        }
        
        .info-value {
            color: #333;
            flex: 1;
        }
        
        .welcome-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .profile-icon {
            font-size: 4rem;
            color: #06BBCC;
            margin-bottom: 20px;
        }
        
        .status-badge {
            background: #06BBCC;
            color: white;
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
                <a href="placement.html" class="nav-item nav-link">Placement</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
                <a href="student-dashboard.php" class="nav-item nav-link active">Dashboard</a>
            </div>
            
            <!-- Desktop Login Dropdown -->
            <div class="d-none d-lg-block desktop-login-dropdown">
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
            
            <!-- Mobile Login Dropdown -->
            <div class="mobile-login-dropdown d-lg-none">
                <div class="dropdown">
                    <button class="btn btn-primary w-100 dropdown-toggle" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($_SESSION['student_username'] ?? 'Student'); ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
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
                    <h1 class="display-5 text-white mb-3">Student Dashboard</h1>
                    <p class="welcome-text text-white">
                        Welcome back, <strong><?php echo htmlspecialchars($student_data['name'] ?? 'Student'); ?></strong>!
                    </p>
                    <span class="status-badge">Active Student</span>
                </div>
                <div class="col-md-4 text-center">
                    <div class="profile-icon">
                        <i class="fas fa-user-graduate"></i>
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
            <?php elseif (!empty($student_data)): ?>
                
                <!-- Quick Stats -->
                <div class="row mb-5">
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['duration'] ?? '0'); ?></div>
                            <div class="stat-label">Course Duration (Months)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="quick-stats">
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['amount_paid'] ?? '0'); ?></div>
                            <div class="stat-label">Course Amount</div>
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
                            <div class="stat-number"><?php echo htmlspecialchars($student_data['Status'] ?? 'N/A'); ?></div>
                            <div class="stat-label">Status</div>
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
                                <span class="info-label">Date of Birth:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['DOB']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mobile No:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['mobile_no']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['email']); ?></span>
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
                    </div>

                    <!-- Course Details -->
                    <div class="col-lg-6 mb-4">
                        <div class="info-card">
                            <h5><i class="fas fa-book me-2"></i>Course Details</h5>
                            <div class="info-item">
                                <span class="info-label">Course Domain:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['course_domain']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Start Date:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['start_date']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">End Date:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['End_date']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Duration:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['duration']); ?> months</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Class Timing:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['timing']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Trainer:</span>
                                <span class="info-value"><?php echo htmlspecialchars($student_data['trainer_name']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-12">
                        <div class="info-card">
                            <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="student_attendance.php" class="btn btn-primary w-100">
                                        <i class="fas fa-book me-2"></i>View Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="view_assignment.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-tasks me-2"></i>Assignments
                                    </a>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <a href="contact.html" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-headset me-2"></i>Support
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                    <h3>No Student Data Found</h3>
                    <p>Please contact administration to complete your profile setup.</p>
                    <a href="contact.html" class="btn btn-primary">Contact Support</a>
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
                    <h4 class="text-white mb-3">Student Resources</h4>
                    <a class="btn btn-link" href="course-materials.html">Course Materials</a>
                    <a class="btn btn-link" href="vew_assignment.php">Assignments</a>
                    <a class="btn btn-link" href="progress-report.html">Progress Reports</a>
                    <a class="btn btn-link" href="placement.html">Placement Support</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Newsletter</h4>
                    <p>Subscribe for updates and announcements</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <input class="form-control border-0 w-100 py-3 ps-4 pe-5" type="email" placeholder="Your email">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">Subscribe</button>
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
</body>

</html>