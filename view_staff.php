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
$success = "";
$staff_list = [];
$search_query = "";

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Handle search
    if (isset($_POST['search']) && !empty($_POST['search_query'])) {
        $search_query = $_POST['search_query'];
        $sql = "SELECT username FROM staff WHERE username LIKE ? ORDER BY username";
        $search_term = "%{$search_query}%";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $staff_list[] = $row;
        }
        $stmt->close();
    } else {
        // Get all staff
        $sql = "SELECT username FROM staff ORDER BY username";
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $staff_list[] = $row;
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("View Staff error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>View Staff - Tekksol Global Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <style>
        .staff-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
            border-left: 4px solid #06BBCC;
        }
        
        .staff-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .search-bar {
            position: relative;
            margin-bottom: 30px;
        }
        
        .search-bar input {
            border-radius: 50px;
            padding: 15px 60px 15px 25px;
            border: 2px solid #e0e0e0;
        }
        
        .search-bar input:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .search-bar button {
            position: absolute;
            right: 5px;
            top: 5px;
            border-radius: 50px;
            padding: 10px 25px;
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
                <a href="admin_dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="view-all-students.php" class="nav-item nav-link">Students</a>
                <a href="view_staff.php" class="nav-item nav-link active">Staff</a>
                <a href="attendance_reports.php" class="nav-item nav-link">Reports</a>
            </div>
              <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
                            <a class="dropdown-item d-flex align-items-center py-2" href="  logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                <h6 class="section-title bg-white text-center text-primary px-3">Staff Management</h6>
                <h1 class="mb-5">All Staff Members</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <div class="row justify-content-center mb-4">
                <div class="col-lg-8">
                    <form method="POST" action="">
                        <div class="search-bar">
                            <input type="text" 
                                   class="form-control" 
                                   name="search_query" 
                                   placeholder="Search staff by username..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" name="search" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                    <?php if ($search_query): ?>
                        <div class="text-center">
                            <a href="view_staff.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Search
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Staff Count -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Total Staff Members: <strong><?php echo count($staff_list); ?></strong>
                        <?php if ($search_query): ?>
                            (Search results for "<?php echo htmlspecialchars($search_query); ?>")
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Staff List -->
            <div class="row">
                <?php if (empty($staff_list)): ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No staff members found<?php echo $search_query ? ' matching your search' : ''; ?>.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($staff_list as $staff): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="staff-card">
                                <div class="text-center mb-3">
                                    <i class="fas fa-user-tie fa-4x text-primary"></i>
                                </div>
                                <h5 class="text-center text-primary mb-3">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($staff['username']); ?>
                                </h5>
                                <div class="d-flex justify-content-center gap-2">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>Trainer
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Back to Dashboard -->
            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="admin_dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
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
</body>
</html>