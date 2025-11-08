<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    error_log("Admin access denied - No valid session");
    error_log("Session data: " . print_r($_SESSION, true));
    header("Location: admin-login.html");
    exit();
}

error_log("Admin access granted for: " . $_SESSION['admin_username']);

// Handle form submission for adding career
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_career'])) {
    $job_title = $conn->real_escape_string($_POST['job_title']);
    $job_type = $conn->real_escape_string($_POST['job_type']);
    $job_description = $conn->real_escape_string($_POST['job_description']);
    $location = $conn->real_escape_string($_POST['location']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $sql = "INSERT INTO add_careers (job_title, job_type, job_description, location, experience, qualification, status) 
            VALUES ('$job_title', '$job_type', '$job_description', '$location', '$experience', '$qualification', '$status')";
    
    if ($conn->query($sql)) {
        $success_msg = "Career opening added successfully!";
    } else {
        $error_msg = "Error: " . $conn->error;
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM add_careers WHERE id = $id";
    if ($conn->query($sql)) {
        $success_msg = "Career opening deleted successfully!";
    } else {
        $error_msg = "Error deleting record: " . $conn->error;
    }
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $sql = "UPDATE add_careers SET status = IF(status = 'active', 'inactive', 'active') WHERE id = $id";
    $conn->query($sql);
    header("Location: add_careers.php");
    exit();
}

// Fetch all careers
$careers_query = "SELECT * FROM add_careers ORDER BY created_at DESC";
$careers_result = $conn->query($careers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Careers - Admin Panel</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" rel="icon">
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    
    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    
    <style>
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
        
        .admin-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .admin-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .admin-card-header {
            background: #06BBCC;
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.875rem;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .status-active {
            background-color: #28a745;
            color: white;
        }
        
        .status-inactive {
            background-color: #dc3545;
            color: white;
        }
        
        /* Beautiful Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dc3545;
            transition: .4s;
            border-radius: 24px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .toggle-slider {
            background-color: #28a745;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        .toggle-slider:hover {
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }

        /* Action buttons styling */
        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .btn-toggle-status {
            background: transparent;
            border: none;
            padding: 0;
        }

        .btn-delete {
            background: #dc3545;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-delete i {
            color: white;
        }
        .navbar-collapse {
    min-width: max-content;
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
                <a href="request_leave_approval_admin.php" class="nav-item nav-link">Leave Approval</a>
             
                <a href="attendance_reports.php" class="nav-item nav-link">Attendance Report</a>
            </div>
           <div class="d-none d-lg-block">
    <div class="dropdown">
        <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loginDropdown">
            <li>
                <a class="dropdown-item" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-3"></i><span>Dashboard</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="view-all-students.php">
                    <i class="fas fa-users me-3"></i><span>View Students</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="add_careers.php">
                    <i class="fas fa-briefcase me-3"></i><span>Post Vacancy</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="view_staff.php">
                    <i class="fas fa-user-tie me-3"></i><span>View Staff</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="generate_staff_credentials.php">
                    <i class="fas fa-key me-3"></i><span>Generate Staff Credentials</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="attendance_reports.php">
                    <i class="fas fa-chart-bar me-3"></i><span>Attendance Reports</span>
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i><span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>
        </div>
    </nav>
    <!-- Navbar End -->


    <div class="container py-5">
        <!-- Success/Error Messages -->
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add New Career Form -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Career Opening</h4>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="job_title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="job_title" name="job_title" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="job_type" name="job_type" required>
                                <option value="Full Time">Full Time</option>
                                <option value="Part Time">Part Time</option>
                                <option value="Contract">Contract</option>
                                <option value="Internship">Internship</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="job_description" class="form-label">Job Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="job_description" name="job_description" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="experience" class="form-label">Experience <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="experience" name="experience" placeholder="e.g., 2-4 years" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="qualification" class="form-label">Qualification <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="qualification" name="qualification" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_career" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Career Opening
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Careers List -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h4 class="mb-0"><i class="fas fa-list me-2"></i>All Career Openings</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Job Title</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($careers_result->num_rows > 0): ?>
                                <?php while ($row = $careers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['job_title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['job_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo htmlspecialchars($row['experience']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $row['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                        <td class="text-center">
                                            <div class="action-buttons">
                                                <form action="?toggle_status=<?php echo $row['id']; ?>" method="GET" style="display: inline;">
                                                    <input type="hidden" name="toggle_status" value="<?php echo $row['id']; ?>">
                                                    <label class="toggle-switch" title="Toggle Status">
                                                        <input type="checkbox" <?php echo $row['status'] == 'active' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </form>
                                                
                                                <a href="?delete=<?php echo $row['id']; ?>" 
                                                   class="btn-delete" 
                                                   onclick="return confirm('Are you sure you want to delete this career opening?')"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                        <p class="text-muted">No career openings found. Add your first opening above.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
                    <a class="btn btn-link" href="view-all-students.php">Student Management</a>
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