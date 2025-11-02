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
if (!isset($_SESSION['staff_username'])) {
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

        // Get and sanitize form data
        $username = trim($_POST['username']);
        $student_id = trim($_POST['student_id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $dob = $_POST['DOB'];
        $mobile_no = trim($_POST['mobile_no']);
        $location = trim($_POST['location']);
        $year_of_passed_out = trim($_POST['year_of_passed_out']);
        $degree = trim($_POST['Degree']);
        $specialization = trim($_POST['specialization']);
        $type = trim($_POST['Type']);
        $course_domain = trim($_POST['course_domain']);
        $course_mode = trim($_POST['course_mode']);
        $timing = trim($_POST['timing']);
        $duration = intval($_POST['duration']);
        $trainer_name = trim($_POST['trainer_name']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['End_date'];
        $amount_paid = trim($_POST['amount_paid']);
        $status = trim($_POST['Status']);

        // Validate required fields
        if (empty($username) || empty($student_id) || empty($name) || empty($email)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Check if username already exists
        $check_sql = "SELECT username FROM student_details WHERE username = ? OR student_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("Username or Student ID already exists. Please choose different ones.");
        }
        $check_stmt->close();

        // Insert into student_details table
        $sql = "INSERT INTO student_details (
            username, student_id, name, email, DOB, mobile_no, location, 
            year_of_passed_out, Degree, specialization, Type, course_domain, 
            course_mode, timing, duration, trainer_name, start_date, End_date, 
            amount_paid, Status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssssissssss",
            $username, $student_id, $name, $email, $dob, $mobile_no, $location,
            $year_of_passed_out, $degree, $specialization, $type, $course_domain,
            $course_mode, $timing, $duration, $trainer_name, $start_date, $end_date,
            $amount_paid, $status
        );

        if ($stmt->execute()) {
            $success_message = "Student added successfully!";
            
            // Clear form fields
            $_POST = array();
        } else {
            throw new Exception("Error adding student: " . $stmt->error);
        }

        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Add Student - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Add Student, Tekksol Global, Student Management" name="keywords">
    <meta content="Add new student to Tekksol Global training institute" name="description">

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
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .form-section h5 {
            color: #06BBCC;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
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
                <a href="staff-dashboard.php" class="nav-item nav-link active">Dashboard</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            
            <!-- Desktop Login Dropdown -->
            <div class="d-none d-lg-block desktop-login-dropdown">
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
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
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
            
            <!-- Mobile Login Dropdown -->
            <div class="mobile-login-dropdown d-lg-none">
                <div class="dropdown">
                    <button class="btn btn-primary w-100 dropdown-toggle" type="button" id="mobileLoginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($_SESSION['staff_username'] ?? 'Staff'); ?>
                    </button>
                    <ul class="dropdown-menu w-100" aria-labelledby="mobileLoginDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="staff-dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="mark_attendance.php">
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

    <!-- Add Student Section -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="form-container">
                <div class="form-header text-center">
                    <h2><i class="fas fa-user-plus me-2"></i>Add New Student</h2>
                    <p class="mb-0">Register a new student in the system</p>
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

                <form method="POST" action="">
                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                <small class="text-muted">Unique username for student login</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Student ID</label>
                                <input type="text" class="form-control" name="student_id" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="DOB" value="<?php echo htmlspecialchars($_POST['DOB'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile No</label>
                                <input type="text" class="form-control" name="mobile_no" value="<?php echo htmlspecialchars($_POST['mobile_no'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information Section -->
                    <div class="form-section">
                        <h5><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year of Passed Out</label>
                                <input type="text" class="form-control" name="year_of_passed_out" value="<?php echo htmlspecialchars($_POST['year_of_passed_out'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Degree</label>
                                <select class="form-select" name="Degree">
                                    <option value="">Select Degree</option>
                                    <option value="B.Tech" <?php echo (($_POST['Degree'] ?? '') == 'B.Tech') ? 'selected' : ''; ?>>B.Tech</option>
                                    <option value="B.E" <?php echo (($_POST['Degree'] ?? '') == 'B.E') ? 'selected' : ''; ?>>B.E</option>
                                    <option value="B.Sc" <?php echo (($_POST['Degree'] ?? '') == 'B.Sc') ? 'selected' : ''; ?>>B.Sc</option>
                                    <option value="B.Com" <?php echo (($_POST['Degree'] ?? '') == 'B.Com') ? 'selected' : ''; ?>>B.Com</option>
                                    <option value="BA" <?php echo (($_POST['Degree'] ?? '') == 'BA') ? 'selected' : ''; ?>>BA</option>
                                    <option value="M.Tech" <?php echo (($_POST['Degree'] ?? '') == 'M.Tech') ? 'selected' : ''; ?>>M.Tech</option>
                                    <option value="MBA" <?php echo (($_POST['Degree'] ?? '') == 'MBA') ? 'selected' : ''; ?>>MBA</option>
                                    <option value="MCA" <?php echo (($_POST['Degree'] ?? '') == 'MCA') ? 'selected' : ''; ?>>MCA</option>
                                    <option value="Other" <?php echo (($_POST['Degree'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Specialization</label>
                                <input type="text" class="form-control" name="specialization" value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Program Type</label>
                                <select class="form-select" name="Type">
                                    <option value="">Select Type</option>
                                    <option value="Regular" <?php echo (($_POST['Type'] ?? '') == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                                    <option value="Weekend" <?php echo (($_POST['Type'] ?? '') == 'Weekend') ? 'selected' : ''; ?>>Weekend</option>
                                    <option value="Online" <?php echo (($_POST['Type'] ?? '') == 'Online') ? 'selected' : ''; ?>>Online</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Course Details Section -->
                    <div class="form-section">
                        <h5><i class="fas fa-book me-2"></i>Course Details</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Domain</label>
                                <select class="form-select" name="course_domain">
                                    <option value="">Select Course Domain</option>
                                    <option value=".NET Development" <?php echo (($_POST['course_domain'] ?? '') == '.NET Development') ? 'selected' : ''; ?>>.NET Development</option>
                                    <option value="Java Development" <?php echo (($_POST['course_domain'] ?? '') == 'Java Development') ? 'selected' : ''; ?>>Java Development</option>
                                    <option value="Python Development" <?php echo (($_POST['course_domain'] ?? '') == 'Python Development') ? 'selected' : ''; ?>>Python Development</option>
                                    <option value="Web Development" <?php echo (($_POST['course_domain'] ?? '') == 'Web Development') ? 'selected' : ''; ?>>Web Development</option>
                                    <option value="Digital Marketing" <?php echo (($_POST['course_domain'] ?? '') == 'Digital Marketing') ? 'selected' : ''; ?>>Digital Marketing</option>
                                    <option value="Data Science" <?php echo (($_POST['course_domain'] ?? '') == 'Data Science') ? 'selected' : ''; ?>>Data Science</option>
                                    <option value="UI/UX Design" <?php echo (($_POST['course_domain'] ?? '') == 'UI/UX Design') ? 'selected' : ''; ?>>UI/UX Design</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Course Mode</label>
                                <select class="form-select" name="course_mode">
                                    <option value="">Select Mode</option>
                                    <option value="Online" <?php echo (($_POST['course_mode'] ?? '') == 'Online') ? 'selected' : ''; ?>>Online</option>
                                    <option value="Offline" <?php echo (($_POST['course_mode'] ?? '') == 'Offline') ? 'selected' : ''; ?>>Offline</option>
                                    <option value="Hybrid" <?php echo (($_POST['course_mode'] ?? '') == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Class Timing</label>
                                <select class="form-select" name="timing">
                                    <option value="">Select Timing</option>
                                    <option value="Morning (9 AM - 12 PM)" <?php echo (($_POST['timing'] ?? '') == 'Morning (9 AM - 12 PM)') ? 'selected' : ''; ?>>Morning (9 AM - 12 PM)</option>
                                    <option value="Afternoon (2 PM - 5 PM)" <?php echo (($_POST['timing'] ?? '') == 'Afternoon (2 PM - 5 PM)') ? 'selected' : ''; ?>>Afternoon (2 PM - 5 PM)</option>
                                    <option value="Evening (6 PM - 9 PM)" <?php echo (($_POST['timing'] ?? '') == 'Evening (6 PM - 9 PM)') ? 'selected' : ''; ?>>Evening (6 PM - 9 PM)</option>
                                    <option value="Weekend (10 AM - 4 PM)" <?php echo (($_POST['timing'] ?? '') == 'Weekend (10 AM - 4 PM)') ? 'selected' : ''; ?>>Weekend (10 AM - 4 PM)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (Months)</label>
                                <input type="number" class="form-control" name="duration" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" min="1" max="24">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trainer Name</label>
                                <input type="text" class="form-control" name="trainer_name" value="<?php echo htmlspecialchars($_POST['trainer_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="End_date" value="<?php echo htmlspecialchars($_POST['End_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount Paid</label>
                                <input type="text" class="form-control" name="amount_paid" value="<?php echo htmlspecialchars($_POST['amount_paid'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="Status">
                                    <option value="Active" <?php echo (($_POST['Status'] ?? '') == 'Active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="Inactive" <?php echo (($_POST['Status'] ?? '') == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="Completed" <?php echo (($_POST['Status'] ?? '') == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="Dropped" <?php echo (($_POST['Status'] ?? '') == 'Dropped') ? 'selected' : ''; ?>>Dropped</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Add Student
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
        <!-- Your existing footer code here -->
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
        // Auto-calculate end date based on start date and duration
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.querySelector('input[name="start_date"]');
            const durationInput = document.querySelector('input[name="duration"]');
            const endDateInput = document.querySelector('input[name="End_date"]');
            
            function calculateEndDate() {
                if (startDateInput.value && durationInput.value) {
                    const startDate = new Date(startDateInput.value);
                    const duration = parseInt(durationInput.value);
                    const endDate = new Date(startDate);
                    endDate.setMonth(endDate.getMonth() + duration);
                    
                    // Format to YYYY-MM-DD
                    const formattedEndDate = endDate.toISOString().split('T')[0];
                    endDateInput.value = formattedEndDate;
                }
            }
            
            startDateInput.addEventListener('change', calculateEndDate);
            durationInput.addEventListener('input', calculateEndDate);
        });
    </script>
</body>

</html>