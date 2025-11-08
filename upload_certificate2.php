<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Upload Certificate - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Upload Certificate, Tekksol Global, Admin Portal" name="keywords">
    <meta content="Upload course completion certificates for students" name="description">

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
        
        .upload-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid #06BBCC;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #06BBCC;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 187, 204, 0.3);
        }
        
        .student-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        .requirements-list {
            list-style: none;
            padding: 0;
        }
        
        .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .requirements-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .student-details-display {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #06BBCC;
        }
        
        @media (max-width: 768px) {
            .upload-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .upload-icon {
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
                <a href="index.html" class="nav-item nav-link">Home</a>
                <a href="admin_dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="upload_certificate2.php" class="nav-item nav-link active">Upload Certificate</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            
            <!-- Admin Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i>Admin
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                         <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="upload_certificate2.php">
                                <i class="fas fa-medal me-2"></i> Upload Certificate
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
                    <h1 class="display-5 text-white mb-3">Upload Certificate</h1>
                    <p class="text-white mb-0">
                        Upload course completion certificates for students. Select student and upload their certificate file.
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="upload-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Content -->
    <div class="container-xxl py-5">
        <div class="container">
            <?php
            // Database configuration
            $servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
            $dbusername = "uwgxq8otzk6mhome";
            $dbpassword = "8oQDCXxH6aqYgvkG7g8t";
            $dbname = "bzbnom7tqqucjcivbuxo";

            // PHP code to handle file upload and database insertion
            $message = "";
            $messageClass = "";
            $students = [];

            // Get all students for dropdown
            try {
                $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $sql = "SELECT username, student_id, name, course_domain FROM student_details ORDER BY username";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $conn = null;
            } catch(PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $messageClass = "error";
            }

            if(isset($_POST["submit"])) {
                $target_dir = "certificates/";
                $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
                $uploadOk = 1;
                $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                // Get form data
                $selected_username = $_POST['student_username'] ?? '';
                $uploaded_by = "Admin"; // Fixed value since we're removing this field

                // Validate required fields
                if(empty($selected_username)) {
                    $message = "Please select a student.";
                    $uploadOk = 0;
                    $messageClass = "error";
                }

                // Check if file is a PDF
                if($uploadOk == 1 && $fileType != "pdf") {
                    $message = "Sorry, only PDF files are allowed.";
                    $uploadOk = 0;
                    $messageClass = "error";
                }

                // Check if file already exists
                if ($uploadOk == 1 && file_exists($target_file)) {
                    $message = "Sorry, file already exists.";
                    $uploadOk = 0;
                    $messageClass = "error";
                }

                // Check file size (3 MB limit)
                if ($uploadOk == 1 && $_FILES["fileToUpload"]["size"] > 3145728) {
                    $message = "Sorry, your file is too large. Maximum size is 3 MB.";
                    $uploadOk = 0;
                    $messageClass = "error";
                }

                // Check if $uploadOk is set to 0 by an error
                if ($uploadOk == 0) {
                    if (empty($message)) {
                        $message = "Sorry, your file was not uploaded.";
                        $messageClass = "error";
                    }
                } else {
                    // Get student details from selected username
                    try {
                        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
                        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        $sql = "SELECT student_id, name, course_domain FROM student_details WHERE username = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$selected_username]);
                        $student = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($student) {
                            $student_id = $student['student_id'];
                            $student_name = $student['name'];
                            $course_domain = $student['course_domain'];

                            // Create directory if it doesn't exist
                            if (!file_exists($target_dir)) {
                                mkdir($target_dir, 0755, true);
                            }
                            
                            // Try to upload file
                            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                                // File uploaded successfully, now insert into database
                                $sql = "INSERT INTO student_certificates (student_id, student_name, course_domain, certificate_file, uploaded_by) 
                                        VALUES (:student_id, :student_name, :course_domain, :certificate_file, :uploaded_by)";
                                
                                $stmt = $conn->prepare($sql);
                                $stmt->bindParam(':student_id', $student_id);
                                $stmt->bindParam(':student_name', $student_name);
                                $stmt->bindParam(':course_domain', $course_domain);
                                $stmt->bindParam(':certificate_file', $target_file);
                                $stmt->bindParam(':uploaded_by', $uploaded_by);
                                
                                if ($stmt->execute()) {
                                    $message = "The file ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])). " has been uploaded successfully for " . $student_name . "!";
                                    $messageClass = "success";
                                    
                                    // Clear form selection after successful upload
                                    $selected_username = '';
                                } else {
                                    $message = "File uploaded but failed to save data in database.";
                                    $messageClass = "error";
                                }
                            } else {
                                $message = "Sorry, there was an error uploading your file.";
                                $messageClass = "error";
                            }
                        } else {
                            $message = "Selected student not found!";
                            $messageClass = "error";
                        }
                        
                    } catch(PDOException $e) {
                        $message = "Database error: " . $e->getMessage();
                        $messageClass = "error";
                    }
                    
                    $conn = null; // Close connection
                }
            }
            ?>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageClass === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?php echo $messageClass === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="upload-card">
                        <h3 class="text-center mb-4"><i class="fas fa-file-upload me-2"></i>Upload Student Certificate</h3>
                        
                        <form method="POST" action="upload_certificate2.php" enctype="multipart/form-data" id="uploadForm">
                            <!-- Student Selection Dropdown -->
                            <div class="mb-4">
                                <label for="student_username" class="form-label">Select Student</label>
                                <select class="form-select" id="student_username" name="student_username" required>
                                    <option value="">-- Choose a Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['username']); ?>" 
                                            <?php echo (isset($selected_username) && $selected_username == $student['username']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['username'] . ' - ' . $student['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the student who completed the course</div>
                            </div>

                            <!-- Student Details Display (Read-only) -->
                            <div class="student-details-display" id="studentDetails" style="display: none;">
                                <h6><i class="fas fa-user me-2"></i>Student Details</h6>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <strong>Student ID:</strong> <span id="display_student_id">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Name:</strong> <span id="display_student_name">-</span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>Course Domain:</strong> <span id="display_course_domain">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Uploaded By:</strong> Admin
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload -->
                            <div class="mb-4">
                                <label for="fileToUpload" class="form-label">Certificate PDF</label>
                                <input type="file" class="form-control" name="fileToUpload" id="fileToUpload" accept=".pdf" required>
                                <div class="form-text">
                                    Supported format: PDF | Maximum size: 3MB
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" name="submit" class="btn btn-upload btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Certificate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Requirements & Information -->
            
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
                    <a class="btn btn-link" href="admin_dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="upload_certificate2.php">Upload Certificate</a>
                    <a class="btn btn-link" href="view-all-students.php">Student Management</a>
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
    // Student details display functionality
    document.addEventListener('DOMContentLoaded', function() {
        const studentDropdown = document.getElementById('student_username');
        const studentDetails = document.getElementById('studentDetails');
        const studentIdDisplay = document.getElementById('display_student_id');
        const studentNameDisplay = document.getElementById('display_student_name');
        const courseDomainDisplay = document.getElementById('display_course_domain');

        // Student data from PHP
        const students = <?php echo json_encode($students); ?>;

        studentDropdown.addEventListener('change', function() {
            const selectedUsername = this.value;
            
            if (selectedUsername) {
                // Find the selected student in the students array
                const selectedStudent = students.find(student => student.username === selectedUsername);
                
                if (selectedStudent) {
                    // Update display fields
                    studentIdDisplay.textContent = selectedStudent.student_id;
                    studentNameDisplay.textContent = selectedStudent.name;
                    courseDomainDisplay.textContent = selectedStudent.course_domain;
                    
                    // Show student details section
                    studentDetails.style.display = 'block';
                }
            } else {
                // Hide student details section if no student selected
                studentDetails.style.display = 'none';
            }
        });

        // Trigger change event on page load if there's a selected value
        if (studentDropdown.value) {
            studentDropdown.dispatchEvent(new Event('change'));
        }
    });
    </script>
</body>
</html>