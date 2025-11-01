<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Database configuration
$servername = "localhost";
$dbusername = "root";
$dbpassword = "ruban";
$db = "tekksol_global";

// Check if user is logged in as student
if (!isset($_SESSION['student_username'])) {
    header("Location: student-login.html");
    exit();
}

$student_username = $_SESSION['student_username'];
$assignment_id = $_GET['id'] ?? null;
$assignment = null;
$success_message = "";
$error_message = "";

if (!$assignment_id) {
    header("Location: view_assignment.php");
    exit();
}

try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get assignment details
    $sql = "SELECT st.*, sd.name as student_name 
            FROM student_task st 
            LEFT JOIN student_details sd ON st.username = sd.username 
            WHERE st.id = ? AND st.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $assignment_id, $student_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Assignment not found or you don't have permission to access it.");
    }
    
    $assignment = $result->fetch_assoc();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $submission_text = trim($_POST['submission_text'] ?? '');
        $submission_file = null;
        
        // Validate submission
        if (empty($submission_text)) {
            throw new Exception("Please provide your submission content.");
        }
        
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "submissions/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['submission_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            // Validate file type
            $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if (!in_array($file_extension, $allowed_types)) {
                throw new Exception("File type not allowed. Please upload PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, JPEG, or PNG files.");
            }
            
            // Validate file size (5MB max)
            if ($_FILES['submission_file']['size'] > 5 * 1024 * 1024) {
                throw new Exception("File size too large. Maximum size is 5MB.");
            }
            
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
                $submission_file = $file_name;
            } else {
                throw new Exception("Error uploading file. Please try again.");
            }
        }
        
        // Update assignment with submission
        $update_sql = "UPDATE student_task 
                      SET submission_text = ?, 
                          submission_file = ?, 
                          submission_date = NOW(), 
                          status = 'submitted' 
                      WHERE id = ? AND username = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssis", $submission_text, $submission_file, $assignment_id, $student_username);
        
        if ($update_stmt->execute()) {
            $success_message = "Assignment submitted successfully!";
            // Refresh assignment data
            $assignment['status'] = 'submitted';
            $assignment['submission_text'] = $submission_text;
            $assignment['submission_file'] = $submission_file;
            $assignment['submission_date'] = date('Y-m-d H:i:s');
        } else {
            throw new Exception("Error submitting assignment: " . $update_stmt->error);
        }
        
        $update_stmt->close();
    }
    
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Submit Assignment - Tekksol Global</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Submit Assignment, Tekksol Global, Student Tasks" name="keywords">
    <meta content="Submit your assignment for Tekksol Global training institute" name="description">

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
        }
        
        .form-header {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .assignment-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #06BBCC;
        }
        
        .assignment-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
        
        .submission-textarea {
            min-height: 300px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            line-height: 1.5;
        }
        
        .file-upload-area {
            border: 2px dashed #06BBCC;
            border-radius: 10px;
            padding: 30px;
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
            border-color: #0288d1;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.875rem;
            color: #666;
        }
        
        .character-count {
            text-align: right;
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .submission-preview {
            background: #e8f5e8;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .meta-item i {
            color: #06BBCC;
            width: 16px;
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
                <a href="student-dashboard.php" class="nav-item nav-link">Dashboard</a>
                <a href="view_assignment.php" class="nav-item nav-link">Assignments</a>
                <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="nav-item nav-link active">Submit Assignment</a>
                <a href="contact.html" class="nav-item nav-link">Contact</a>
            </div>
            <div class="d-none d-lg-block">
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
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="view_assignment.php">
                                <i class="fas fa-tasks me-2"></i> Assignments
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center py-2" href="student-profile.php">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
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

    <!-- Submit Assignment Section -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="form-container">
                <div class="form-header text-center">
                    <h2><i class="fas fa-paper-plane me-2"></i>Submit Assignment</h2>
                    <p class="mb-0">Submit your work for evaluation</p>
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

                <!-- Assignment Details -->
                <div class="assignment-details">
                    <h4><i class="fas fa-tasks me-2"></i>Assignment Details</h4>
                    <div class="meta-item">
                        <i class="fas fa-hashtag"></i>
                        <strong>Assignment ID:</strong> #<?php echo $assignment['id']; ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <strong>Assigned:</strong> <?php echo date('M d, Y g:i A', strtotime($assignment['assigned_date'])); ?>
                    </div>
                    <?php if ($assignment['due_date']): ?>
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i class="fas fa-user-tie"></i>
                        <strong>Assigned by:</strong> <?php echo htmlspecialchars($assignment['assigned_by']); ?>
                    </div>
                    
                    <h6 class="mt-3 mb-2">Assignment Instructions:</h6>
                    <div class="assignment-content">
                        <?php echo htmlspecialchars($assignment['assignment_text']); ?>
                    </div>
                </div>

                <?php if ($assignment['status'] === 'submitted'): ?>
                    <!-- Submission Preview -->
                    <div class="submission-preview">
                        <h4><i class="fas fa-check-circle me-2 text-success"></i>Already Submitted</h4>
                        <div class="meta-item">
                            <i class="fas fa-calendar-check"></i>
                            <strong>Submitted on:</strong> <?php echo date('M d, Y g:i A', strtotime($assignment['submission_date'])); ?>
                        </div>
                        
                        <?php if ($assignment['submission_text']): ?>
                            <h6 class="mt-3 mb-2">Your Submission:</h6>
                            <div class="assignment-content">
                                <?php echo htmlspecialchars($assignment['submission_text']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($assignment['submission_file']): ?>
                            <div class="meta-item mt-3">
                                <i class="fas fa-file"></i>
                                <strong>Attached File:</strong> 
                                <a href="submissions/<?php echo $assignment['submission_file']; ?>" download class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="view_assignment.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Assignments
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Submission Form -->
                    <form method="POST" action="" enctype="multipart/form-data" id="submissionForm">
                        <!-- Submission Text -->
                        <div class="mb-4">
                            <label class="form-label required-field">Your Submission</label>
                            <textarea class="form-control submission-textarea" name="submission_text" id="submission_text" 
                                      required placeholder="Enter your solution, code, answers, or any relevant work here..."
                                      rows="15"><?php echo htmlspecialchars($_POST['submission_text'] ?? ''); ?></textarea>
                            <div class="character-count">
                                <span id="charCount">0</span> characters
                            </div>
                            <small class="text-muted">Provide your complete solution or answer to the assignment.</small>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-4">
                            <label class="form-label">Attach File (Optional)</label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5>Drag & Drop your file here</h5>
                                <p class="text-muted">or click to browse</p>
                                <input type="file" name="submission_file" id="submission_file" 
                                       accept=".pdf,.doc,.docx,.txt,.zip,.rar,.jpg,.jpeg,.png" 
                                       style="display: none;">
                                <div class="file-info" id="fileInfo">
                                    Supported formats: PDF, DOC, DOCX, TXT, ZIP, RAR, JPG, JPEG, PNG (Max: 5MB)
                                </div>
                            </div>
                            <div id="selectedFileName" class="mt-2"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-submit btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Submit Assignment
                            </button>
                            <a href="view_assignment.php" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <!-- Your existing footer code -->
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
                    <a class="btn btn-link" href="view_assignment.php">Assignments</a>
                    <a class="btn btn-link" href="progress-report.html">Progress Reports</a>
                    <a class="btn btn-link" href="placement.html">Placement Support</a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Support</h4>
                    <p>Need help with submission? Contact your trainer</p>
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <a href="contact.html" class="btn btn-primary w-100">Get Help</a>
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

    <script>
        // Character count for submission text
        document.addEventListener('DOMContentLoaded', function() {
            const submissionText = document.getElementById('submission_text');
            const charCount = document.getElementById('charCount');
            
            submissionText.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
            
            // Initialize character count
            charCount.textContent = submissionText.value.length;
            
            // File upload handling
            const fileInput = document.getElementById('submission_file');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const selectedFileName = document.getElementById('selectedFileName');
            
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    selectedFileName.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-file me-2"></i>
                            <strong>Selected file:</strong> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            <button type="button" class="btn-close btn-sm" onclick="clearFileSelection()"></button>
                        </div>
                    `;
                }
            });
            
            // Drag and drop functionality
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            fileUploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    fileInput.files = e.dataTransfer.files;
                    const file = e.dataTransfer.files[0];
                    selectedFileName.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-file me-2"></i>
                            <strong>Selected file:</strong> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            <button type="button" class="btn-close btn-sm" onclick="clearFileSelection()"></button>
                        </div>
                    `;
                }
            });
        });
        
        function clearFileSelection() {
            document.getElementById('submission_file').value = '';
            document.getElementById('selectedFileName').innerHTML = '';
        }
        
        // Form submission confirmation
        document.getElementById('submissionForm').addEventListener('submit', function(e) {
            const submissionText = document.getElementById('submission_text').value.trim();
            if (submissionText.length < 10) {
                e.preventDefault();
                alert('Please provide a more detailed submission (at least 10 characters).');
                return;
            }
            
            if (!confirm('Are you sure you want to submit this assignment? You cannot edit your submission after submitting.')) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>