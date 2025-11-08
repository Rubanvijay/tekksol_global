<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if student is logged in
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$dbname = "bzbnom7tqqucjcivbuxo";

$student_username = $_SESSION['student_username'];
$error = "";
$certificates = [];

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get student details to find their student_id
    $sql = "SELECT student_id, name FROM student_details WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$student_username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $student_id = $student['student_id'];
        $student_name = $student['name'];

        // Get all certificates for this student
        $sql = "SELECT id, certificate_file, course_domain, upload_date 
                FROM student_certificates 
                WHERE student_id = ? 
                ORDER BY upload_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$student_id]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Student not found!";
    }

    $conn = null;
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle file download
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $file_id = $_GET['download'];
    
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verify that the file belongs to the logged-in student and get the full file path
        $sql = "SELECT sc.certificate_file, sc.course_domain
                FROM student_certificates sc 
                JOIN student_details sd ON sc.student_id = sd.student_id 
                WHERE sc.id = ? AND sd.username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$file_id, $student_username]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($certificate) {
            $filepath = $certificate['certificate_file']; // This is the full path like "certificates/Payslip2.pdf"
            
            // Check if file exists
            if (file_exists($filepath)) {
                // Get just the filename for the download
                $filename = basename($filepath);
                
                // Set headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                
                // Clear output buffer
                flush();
                
                // Read the file and output it
                readfile($filepath);
                exit;
            } else {
                // File not found, show detailed error
                $error = "File not found: " . $filepath . ". Please contact administrator.";
            }
        } else {
            $error = "You are not authorized to download this certificate or certificate not found.";
        }
        
        $conn = null;
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Download Certificate - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Download Certificate, Tekksol Global, Student Portal" name="keywords">
    <meta content="Download your course completion certificates" name="description">

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
        .page-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .certificate-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid #06BBCC;
        }
        
        .download-icon {
            font-size: 3rem;
            color: #06BBCC;
            margin-bottom: 20px;
        }
        
        .certificate-list {
            list-style: none;
            padding: 0;
        }
        
        .certificate-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #06BBCC;
            transition: all 0.3s;
        }
        
        .certificate-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .btn-download {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .student-welcome {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        .no-certificates {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .no-certificates i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .certificate-badge {
            background: #06BBCC;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .file-path {
            font-size: 0.85rem;
            color: #6c757d;
            font-family: monospace;
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .certificate-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .download-icon {
                font-size: 2.5rem;
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
                <a href="student-dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="student_attendance.php" class="nav-item nav-link">Attendance</a>
                <a href="view_assignment.php" class="nav-item nav-link">Assignment</a>
                <a href="download_certificate.php" class="nav-item nav-link">My Cerificate</a>
            </div>
            
            <!-- Student Dropdown -->
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
    <a class="dropdown-item d-flex align-items-center py-2" href="download_certificate.php">
        <i class="fas fa-certificate me-2"></i> My Certificate
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
    <a class="dropdown-item d-flex align-items-center py-2" href="download_certificate.php">
        <i class="fas fa-certificate me-2"></i> My Certificate
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
                    <h1 class="display-5 text-white mb-3">My Certificates</h1>
                    <p class="text-white mb-0">
                        Download your course completion certificates. All your earned certificates are listed below.
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="download-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Download Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Student Welcome Card -->
            <div class="student-welcome">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2">Welcome, <?php echo htmlspecialchars($student_name ?? $student_username); ?>!</h4>
                        <p class="mb-0">Here you can download all your course completion certificates.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="certificate-badge">
                            <i class="fas fa-certificate me-1"></i>
                            <?php echo count($certificates); ?> Certificate(s)
                        </span>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="certificate-card">
                        <h3 class="text-center mb-4"><i class="fas fa-download me-2"></i>Download Your Certificates</h3>
                        
                        <?php if (empty($certificates)): ?>
                            <div class="no-certificates">
                                <i class="fas fa-file-pdf"></i>
                                <h4>No Certificates Found</h4>
                                <p class="text-muted">You haven't earned any certificates yet. Complete your courses to get certified!</p>
                            </div>
                        <?php else: ?>
                            <ul class="certificate-list">
                                <?php foreach ($certificates as $certificate): ?>
                                    <li class="certificate-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                                    <?php echo htmlspecialchars(basename($certificate['certificate_file'])); ?>
                                                </h6>
                                                <p class="mb-1 text-muted">
                                                    <strong>Course:</strong> <?php echo htmlspecialchars($certificate['course_domain']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Uploaded: <?php echo date('M j, Y', strtotime($certificate['upload_date'])); ?>
                                                </small>
                                                <div class="mt-1">
                                                    <small class="file-path">
                                                        Path: <?php echo htmlspecialchars($certificate['certificate_file']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="download_certificate.php?download=<?php echo urlencode($certificate['id']); ?>" 
                                                   class="btn btn-download"
                                                   onclick="return confirm('Are you sure you want to download this certificate?')">
                                                    <i class="fas fa-download me-2"></i>Download PDF
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href="https://www.facebook.com/teksolglobal/"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href="https://www.linkedin.com/company/tekksol-global/"><i class="fab fa-linkedin-in"></i></a>
                        <a class="btn btn-outline-light btn-social" href="https://www.instagram.com/tekksol_global/"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Student Resources</h4>
                    <a class="btn btn-link" href="student-dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="download_certificate.php">My Certificates</a>
                    <a class="btn btn-link" href="courses.html">Courses</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Student support and assistance</p>
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
                            <a href="student_dashboard.php">Dashboard</a>
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