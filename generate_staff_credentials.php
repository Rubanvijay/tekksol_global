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
$servername = "localhost";
$dbusername = "root";
$dbpassword = "ruban";
$db = "tekksol_global";

$error = "";
$success = "";
$username = "";
$password = "";

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Validate inputs
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            // Check if username already exists
            $check_sql = "SELECT username FROM staff WHERE username = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username '$username' already exists. Please choose a different username.";
            } else {
                // Insert into database with plain text password
                $insert_sql = "INSERT INTO staff (username, password) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ss", $username, $password);
                
                if ($stmt->execute()) {
                    $success = "Staff account created successfully!";
                    // Clear form fields
                    $username = "";
                    $password = "";
                } else {
                    $error = "Error creating staff account: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Generate Staff Credentials error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Generate Staff Credentials - Tekksol Global Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        .credentials-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 20px;
        }
        
        .form-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        .form-control:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #e0e0e0;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .credentials-card {
                padding: 20px;
                margin-top: 15px;
                border-radius: 12px;
            }
            
            .form-card {
                padding: 20px;
                margin-bottom: 20px;
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
        }
        
        @media (max-width: 576px) {
            .credentials-card {
                padding: 15px;
                margin-top: 10px;
                border-radius: 10px;
            }
            
            .form-card {
                padding: 15px;
                margin-bottom: 15px;
            }
            
            h1.mb-5 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem !important;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 16px;
            }
            
            .btn-lg {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .fa-4x {
                font-size: 3rem;
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
            
            .dropdown-menu {
                border: none;
                box-shadow: none;
                padding-left: 15px;
            }
        }
        
        /* Mobile button improvements */
        .btn-group-mobile {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
        }
        
        .btn-group-mobile .btn {
            width: 100%;
            margin: 0 !important;
        }
        
        /* Mobile form improvements */
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .input-group {
            margin-bottom: 5px;
        }
        
        /* Mobile footer improvements */
        @media (max-width: 768px) {
            .footer .row {
                text-align: center;
            }
            
            .footer .btn-link {
                display: block;
                margin-bottom: 5px;
            }
        }
        
        /* Success/Error message improvements */
        .alert {
            border-radius: 10px;
            padding: 15px;
        }
        
        /* Mobile dropdown for navigation */
        .mobile-dropdown {
            width: 100%;
            text-align: left;
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
                <a href="view_all_students.php" class="nav-item nav-link">Students</a>
                <a href="view_staff.php" class="nav-item nav-link">Staff</a>
                <a href="generate_staff_credentials.php" class="nav-item nav-link active">Create Staff</a>
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
        </div>
    </nav>

    <!-- Content -->
    <div class="container-xxl py-4">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Staff Management</h6>
                <h1 class="mb-4">Create Staff Account</h1>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Creation Form -->
                    <div class="credentials-card">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-4x text-primary mb-3"></i>
                            <h4>Create New Staff Account</h4>
                            <p class="text-muted">Enter username and password for the new staff member</p>
                        </div>

                        <form method="POST" action="" class="form-card">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-user text-primary"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Enter username"
                                           required
                                           value="<?php echo htmlspecialchars($username); ?>">
                                </div>
                                <small class="form-text text-muted">
                                    Choose a unique username for the staff member
                                </small>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="fas fa-key text-primary"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter password"
                                           required
                                           value="<?php echo htmlspecialchars($password); ?>">
                                </div>
                                <small class="form-text text-muted">
                                    Set a secure password for the staff member
                                </small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="generate" class="btn btn-primary btn-lg py-3">
                                    <i class="fas fa-user-plus me-2"></i>Create Staff Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <div class="btn-group-mobile d-lg-none">
                                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <a href="view_staff.php" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>View All Staff
                                </a>
                            </div>
                            <div class="d-none d-lg-block">
                                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                                <a href="view_staff.php" class="btn btn-outline-primary ms-2">
                                    <i class="fas fa-users me-2"></i>View All Staff
                                </a>
                            </div>
                        </div>
                    </div>
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
                    <div class="d-flex pt-2 justify-content-center justify-content-md-start">
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