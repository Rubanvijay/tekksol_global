<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$servername = "localhost";
$dbusername = "root";
$dbpassword = "ruban";
$db = "tekksol_global";

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
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate required fields
        if (empty($username) || empty($password) || empty($confirm_password)) {
            throw new Exception("Please fill in all required fields.");
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception("Username can only contain letters, numbers, and underscores.");
        }

        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long.");
        }

        if (strlen($username) > 50) {
            throw new Exception("Username cannot exceed 50 characters.");
        }

        // Validate password
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }

        if (strlen($password) > 50) {
            throw new Exception("Password cannot exceed 50 characters.");
        }

        // Check if username already exists
        $check_sql = "SELECT username FROM students WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("Username '$username' already exists. Please choose a different username.");
        }
        $check_stmt->close();

        // Insert into students table
        $sql = "INSERT INTO students (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $success_message = "Student credentials created successfully!<br>
                               <strong>Username:</strong> " . htmlspecialchars($username) . "<br>
                               <strong>Password:</strong> " . htmlspecialchars($password) . "<br><br>
                               <small class='text-muted'>Please provide these credentials to the student for login.</small>";
            
            // Clear form fields
            $_POST = array();
        } else {
            throw new Exception("Error creating student credentials: " . $stmt->error);
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
    <title>Generate Student Credentials - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Generate Student Credentials, Tekksol Global, Student Login" name="keywords">
    <meta content="Generate student login credentials for Tekksol Global training institute" name="description">

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
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
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
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
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
            width: 100%;
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
        
        .password-strength {
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #28a745; }
        
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
        
        .credentials-display {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 100px;
        }
        
        .check-availability {
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
        
        .availability-status {
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .available { color: #28a745; }
        .taken { color: #dc3545; }
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
                <a href="add-student.php" class="nav-item nav-link">Add Student</a>
                <a href="generate_student_credentials.php" class="nav-item nav-link active">Generate Credentials</a>
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
                            <a class="dropdown-item d-flex align-items-center py-2" href="generate_student_credentials.php">
                                <i class="fas fa-key me-2"></i> Generate Credentials
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

    <!-- Generate Credentials Section -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-key me-2"></i>Generate Student Credentials</h2>
                    <p class="mb-0">Create login credentials for new students</p>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?php echo $success_message; ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>


                <form method="POST" action="" id="credentialsForm">
                    <!-- Username Field -->
                    <div class="mb-4">
                        <label class="form-label required-field">Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="username" id="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                                   placeholder="Enter student username">
                            <button type="button" class="check-availability" onclick="checkAvailability()">
                                Check Availability
                            </button>
                        </div>
                        <div class="availability-status" id="availabilityStatus"></div>
                        <small class="text-muted">
                            Username must be 3-50 characters long and can only contain letters, numbers, and underscores.
                        </small>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-4">
                        <label class="form-label required-field">Password</label>
                        <input type="password" class="form-control" name="password" id="password" 
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" 
                               required minlength="6" maxlength="50"
                               placeholder="Enter password">
                        <div class="password-strength" id="passwordStrength"></div>
                        <small class="text-muted">Password must be at least 6 characters long.</small>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-4">
                        <label class="form-label required-field">Confirm Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" 
                               value="<?php echo htmlspecialchars($_POST['confirm_password'] ?? ''); ?>" 
                               required minlength="6" maxlength="50"
                               placeholder="Confirm password">
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-submit btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Generate Credentials
                        </button>
                        <a href="staff-dashboard.php" class="btn btn-outline-secondary btn-lg mt-3 w-100">
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
        // Check username availability
        function checkAvailability(username = null) {
            const usernameInput = document.getElementById('username');
            const usernameToCheck = username || usernameInput.value;
            const statusDiv = document.getElementById('availabilityStatus');
            
            if (usernameToCheck.length < 3) {
                statusDiv.innerHTML = '<span class="text-muted">Enter at least 3 characters</span>';
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(usernameToCheck)) {
                statusDiv.innerHTML = '<span class="taken">Only letters, numbers, and underscores allowed</span>';
                return;
            }
            
            statusDiv.innerHTML = '<span class="text-muted">Checking availability...</span>';
            
            // Create AJAX request to check username
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_username_availability.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.available) {
                            statusDiv.innerHTML = '<span class="available"><i class="fas fa-check-circle me-1"></i>Username is available!</span>';
                        } else {
                            statusDiv.innerHTML = '<span class="taken"><i class="fas fa-times-circle me-1"></i>Username already taken</span>';
                        }
                    } else {
                        statusDiv.innerHTML = '<span class="taken">Error checking availability</span>';
                    }
                }
            };
            
            xhr.send('username=' + encodeURIComponent(usernameToCheck));
        }

        

        // Password strength indicator
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthText = document.getElementById('passwordStrength');
            const matchText = document.getElementById('passwordMatch');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/\d/)) strength++;
                if (password.match(/[^a-zA-Z\d]/)) strength++;
                
                const strengthLabels = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
                const strengthClasses = ['strength-weak', 'strength-weak', 'strength-medium', 'strength-strong', 'strength-strong'];
                
                strengthText.textContent = 'Strength: ' + strengthLabels[strength];
                strengthText.className = 'password-strength ' + strengthClasses[strength];
                
                checkPasswordMatch();
            });
            
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword === '') {
                    matchText.textContent = '';
                    return;
                }
                
                if (password === confirmPassword) {
                    matchText.innerHTML = '<span class="available"><i class="fas fa-check-circle me-1"></i>Passwords match</span>';
                } else {
                    matchText.innerHTML = '<span class="taken"><i class="fas fa-times-circle me-1"></i>Passwords do not match</span>';
                }
            }
            
            // Auto-check username availability when user stops typing
            let usernameTimeout;
            document.getElementById('username').addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                usernameTimeout = setTimeout(() => {
                    if (this.value.length >= 3) {
                        checkAvailability();
                    }
                }, 500);
            });
        });
    </script>
</body>

</html>