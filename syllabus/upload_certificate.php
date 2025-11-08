<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Set high limits to eliminate size issues
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '256M');

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.html");
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

$error = "";
$success = "";
$students = [];
$selected_student = null;

// Create certificates table if not exists
function createCertificatesTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS student_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(20) NOT NULL,
        student_name VARCHAR(255) NOT NULL,
        course_domain VARCHAR(255) NOT NULL,
        certificate_file VARCHAR(255) NOT NULL,
        uploaded_by VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES student_details(student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    if ($conn->query($sql) === TRUE) {
        error_log("Student certificates table created successfully");
        return true;
    } else {
        error_log("Error creating certificates table: " . $conn->error);
        return false;
    }
}

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create certificates table if not exists
    createCertificatesTable($conn);
    
    // Get all students for dropdown
    $sql = "SELECT student_id, name, course_domain FROM student_details ORDER BY name";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }

    // Handle form submission - SIMPLIFIED AND ROBUST VERSION
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        $student_id = $_POST['student_id'] ?? '';
        
        // DEBUG: Log everything
        error_log("=== UPLOAD DEBUG ===");
        error_log("Student ID: " . $student_id);
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        error_log("Files array exists: " . (isset($_FILES['certificate_file']) ? 'YES' : 'NO'));
        if (isset($_FILES['certificate_file'])) {
            error_log("File error: " . $_FILES['certificate_file']['error']);
            error_log("File name: " . ($_FILES['certificate_file']['name'] ?? 'No name'));
            error_log("File size: " . ($_FILES['certificate_file']['size'] ?? 'No size'));
        }
        error_log("===================");

        // SIMPLIFIED VALIDATION
        if (empty($student_id)) {
            $error = "Please select a student";
        } 
        // Check if file was uploaded
        elseif (!isset($_FILES['certificate_file']) || empty($_FILES['certificate_file']['name'])) {
            $error = "Please choose a certificate file";
        }
        // Check for upload errors
        elseif ($_FILES['certificate_file']['error'] !== UPLOAD_ERR_OK) {
            if ($_FILES['certificate_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = "No file was uploaded";
            } else {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit',
                    UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $error_code = $_FILES['certificate_file']['error'];
                $error = "File upload error: " . ($upload_errors[$error_code] ?? 'Unknown error');
            }
        }
        // Validate file size
        elseif ($_FILES['certificate_file']['size'] > 5 * 1024 * 1024) {
            $error = "File size too large. Maximum size is 5MB.";
        }
        // Validate file type
        else {
            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['certificate_file']['name'], PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                $error = "Invalid file type. Allowed types: PDF, JPG, JPEG, PNG";
            } else {
                // Get student details
                $sql = "SELECT name, course_domain FROM student_details WHERE student_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $student = $result->fetch_assoc();
                    $student_name = $student['name'];
                    $course_domain = $student['course_domain'];
                    
                    // Generate unique filename
                    $filename = "certificate_" . $student_id . "_" . time() . "." . $file_extension;
                    $upload_dir = "certificates/";
                    
                    // Create upload directory if not exists
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $upload_path = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['certificate_file']['tmp_name'], $upload_path)) {
                        // Insert certificate record into database
                        $sql = "INSERT INTO student_certificates (student_id, student_name, course_domain, certificate_file, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $uploaded_by = $_SESSION['admin_username'];
                        $stmt->bind_param("sssss", $student_id, $student_name, $course_domain, $filename, $uploaded_by);
                        
                        if ($stmt->execute()) {
                            $success = "Certificate uploaded successfully for " . $student_name . "!";
                            
                            // Clear form data to prevent resubmission
                            $_POST = [];
                            $_FILES = [];
                        } else {
                            $error = "Error saving certificate record: " . $stmt->error;
                            // Remove uploaded file if database insert failed
                            if (file_exists($upload_path)) {
                                unlink($upload_path);
                            }
                        }
                    } else {
                        $error = "Error moving uploaded file. Check folder permissions.";
                    }
                } else {
                    $error = "Student not found!";
                }
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Upload certificate error: " . $e->getMessage());
}
?>
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
        
        .file-upload-area {
            border: 2px dashed #06BBCC;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            background: #f8fdff;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-upload-area:hover {
            background: #e6f7ff;
            border-color: #0596a3;
        }
        
        .file-upload-area.dragover {
            background: #d1f2ff;
            border-color: #06BBCC;
        }
        
        .file-icon {
            font-size: 3rem;
            color: #06BBCC;
            margin-bottom: 15px;
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
        
        .file-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            display: none;
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
        
        .debug-info {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .upload-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .file-upload-area {
                padding: 30px 15px;
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
                <a href="upload_certificate.php" class="nav-item nav-link active">Upload Certificate</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            
            <!-- Admin Dropdown -->
            <div class="d-none d-lg-block">
                <div class="dropdown">
                    <button class="btn btn-primary py-4 px-lg-5 dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-2"></i><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="upload_certificate.php">
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
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Debug Information -->
            <div class="debug-info">
                <strong>Server Upload Status:</strong><br>
                upload_max_filesize: <?php echo ini_get('upload_max_filesize'); ?> | 
                post_max_size: <?php echo ini_get('post_max_size'); ?><br>
                Form is ready for file uploads
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="upload-card">
                        <h3 class="text-center mb-4"><i class="fas fa-file-upload me-2"></i>Upload Student Certificate</h3>
                        
                        <!-- MAKE SURE enctype="multipart/form-data" IS PRESENT -->
                        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                            <!-- Student Selection -->
                            <div class="mb-4">
                                <label for="student_id" class="form-label">Select Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">-- Choose a Student --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['student_id']); ?>" 
                                            <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $student['student_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['name'] . ' - ' . $student['student_id'] . ' (' . $student['course_domain'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the student who completed the course</div>
                            </div>

                            <!-- File Upload -->
                            <div class="mb-4">
                                <label class="form-label">Upload Certificate File</label>
                                <div class="file-upload-area" id="fileUploadArea">
                                    <div class="file-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <h5>Drag & Drop your certificate file here</h5>
                                    <p class="text-muted">or click to browse files</p>
                                    <!-- MAKE SURE name="certificate_file" MATCHES PHP CODE -->
                                    <input type="file" name="certificate_file" id="certificate_file" accept=".pdf,.jpg,.jpeg,.png" required style="display: none;">
                                    <button type="button" class="btn btn-outline-primary mt-2" onclick="document.getElementById('certificate_file').click()">
                                        <i class="fas fa-folder-open me-2"></i>Choose File
                                    </button>
                                </div>
                                <div class="file-info" id="fileInfo">
                                    <strong>Selected File:</strong> 
                                    <span id="fileName"></span>
                                    <span id="fileSize" class="text-muted"></span>
                                </div>
                                <div class="form-text">
                                    Supported formats: PDF, JPG, JPEG, PNG | Maximum size: 5MB
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-upload btn-lg">
                                    <i class="fas fa-upload me-2"></i>Upload Certificate
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Requirements & Information -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="student-info-card">
                        <h5><i class="fas fa-info-circle me-2"></i>Upload Requirements</h5>
                        <ul class="requirements-list mt-3">
                            <li>Select the correct student from the dropdown</li>
                            <li>Certificate must be in PDF, JPG, JPEG, or PNG format</li>
                            <li>Maximum file size: 5MB</li>
                            <li>Ensure certificate is clear and readable</li>
                            <li>Verify student details before uploading</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="student-info-card">
                        <h5><i class="fas fa-database me-2"></i>Certificate Storage</h5>
                        <p class="mb-2">Uploaded certificates are stored securely and linked to student records.</p>
                        <p class="mb-0">Each upload is logged with:</p>
                        <ul class="requirements-list mt-2">
                            <li>Student ID and Name</li>
                            <li>Course Domain</li>
                            <li>Upload Date & Time</li>
                            <li>Admin who uploaded</li>
                        </ul>
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
                    <h4 class="text-white mb-3">Admin Resources</h4>
                    <a class="btn btn-link" href="admin_dashboard.php">Dashboard</a>
                    <a class="btn btn-link" href="upload_certificate.php">Upload Certificate</a>
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
// File upload functionality
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('certificate_file');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    
    // Check if form was successfully submitted and reset file area
    <?php if ($success): ?>
    resetFileUploadArea();
    <?php endif; ?>
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            const file = this.files[0];
            displayFileInfo(file);
        }
    });
    
    function displayFileInfo(file) {
        fileName.textContent = file.name;
        fileSize.textContent = ' (' + formatFileSize(file.size) + ')';
        fileInfo.style.display = 'block';
        
        // Update upload area text
        fileUploadArea.innerHTML = `
            <div class="file-icon text-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h5>File Selected</h5>
            <p class="text-muted">${file.name}</p>
            <button type="button" class="btn btn-outline-secondary mt-2">
                <i class="fas fa-sync-alt me-2"></i>Change File
            </button>
        `;
        
        // Add event listener to the change file button
        setTimeout(() => {
            const changeFileBtn = fileUploadArea.querySelector('button');
            if (changeFileBtn) {
                changeFileBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    resetFileUploadArea();
                });
            }
        }, 100);
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Function to reset file upload area to initial state
    function resetFileUploadArea() {
        fileInput.value = ''; // Clear file input
        fileInfo.style.display = 'none'; // Hide file info
        
        // Reset upload area HTML
        fileUploadArea.innerHTML = `
            <div class="file-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h5>Drag & Drop your certificate file here</h5>
            <p class="text-muted">or click to browse files</p>
            <button type="button" class="btn btn-outline-primary mt-2" onclick="document.getElementById('certificate_file').click()">
                <i class="fas fa-folder-open me-2"></i>Choose File
            </button>
        `;
        
        // Re-add event listeners
        setupFileAreaListeners();
    }
    
    // Function to setup event listeners on file upload area
    function setupFileAreaListeners() {
        // Click event
        fileUploadArea.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON') {
                fileInput.click();
            }
        });
        
        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                displayFileInfo(e.dataTransfer.files[0]);
            }
        });
    }
    
    // Initial setup of event listeners
    setupFileAreaListeners();
    
    // Form validation
    uploadForm.addEventListener('submit', function(e) {
        const studentId = document.getElementById('student_id').value;
        const certificateFile = fileInput.files[0];
        
        if (!studentId || studentId === '') {
            e.preventDefault();
            alert('Please select a student');
            return false;
        }
        
        if (!certificateFile) {
            e.preventDefault();
            alert('Please select a certificate file');
            return false;
        }
        
        // Validate file type
        const allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        const fileExtension = certificateFile.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            e.preventDefault();
            alert('Invalid file type. Please select a PDF, JPG, JPEG, or PNG file.');
            return false;
        }
        
        // Validate file size (5MB max)
        if (certificateFile.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('File size too large. Maximum size is 5MB.');
            return false;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        submitBtn.disabled = true;
        
        return true;
    });
});
    </script>
</body>
</html>