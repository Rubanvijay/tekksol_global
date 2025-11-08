<?php
session_start();

// Check if student is registered
if (!isset($_SESSION['registered']) || !$_SESSION['registered']) {
    header("Location: test_php.html");
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$dbname = "bzbnom7tqqucjcivbuxo";

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Test - Tekksol Global</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            min-height: calc(100vh - 160px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .login-card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #06BBCC 0%, #06BBCC 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .form-control:focus {
            border-color: #06BBCC;
            box-shadow: 0 0 0 0.2rem rgba(6, 187, 204, 0.25);
        }
        
        .btn-login {
            background-color: #06BBCC;
            border-color: #06BBCC;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 5px;
        }
        
        .btn-login:hover {
            background-color: #0596a3;
            border-color: #0596a3;
        }
        
        .login-footer {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
            margin-top: 1.5rem;
        }
        
        .login-logo {
            max-width: 180px;
            margin-bottom: 1rem;
        }
        
        .forgot-password {
            color: #06BBCC;
            text-decoration: none;
        }
        
        .forgot-password:hover {
            color: #0596a3;
            text-decoration: underline;
        }

        .student-info {
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-success {
            background-color: #d1edff;
            color: #0c5460;
            border-left: 4px solid #06BBCC;
        }
    </style>
</head>
<body>
    <div class="container-xxl py-5 login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="login-card">
                        <div class="login-header">
                            <img src="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" 
                                 alt="Tekksol Global" class="login-logo">
                            <h2 class="mb-0">Select Your Test</h2>
                            <p class="mb-0">Assessment Portal</p>
                        </div>
                        <div class="login-body">
                            <div class="student-info">
                                <h5 class="mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['student_name']); ?>!</h5>
                                <p class="mb-0">Email: <?php echo htmlspecialchars($_SESSION['student_email']); ?></p>
                            </div>

                            <?php
                            if (isset($_SESSION['error'])) {
                                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                                echo '<i class="fas fa-exclamation-circle me-2"></i>';
                                echo $_SESSION['error'];
                                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                                echo '</div>';
                                unset($_SESSION['error']);
                            }
                            if (isset($_SESSION['success'])) {
                                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                                echo '<i class="fas fa-check-circle me-2"></i>';
                                echo $_SESSION['success'];
                                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                                echo '</div>';
                                unset($_SESSION['success']);
                            }
                            ?>

                            <form action="process_test_selection.php" method="POST" id="testSelectionForm">
                                <div class="mb-4">
                                    <label for="course" class="form-label">Select Course *</label>
                                    <select class="form-control" id="course" name="course" required>
                                        <option value="">Choose a course...</option>
                                        <option value="Python">Python</option>
                                        <option value="Java">Java</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="test" class="form-label">Select Test *</label>
                                    <select class="form-control" id="test" name="test" required>
                                        <option value="">Choose a test...</option>
                                        <option value="Test A">Test A</option>
                                        <option value="Test B">Test B</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <button type="submit" class="btn btn-primary btn-login w-100">
                                        Start Test
                                    </button>
                                </div>
                            </form>

                            <div class="login-footer">
                                <p class="mb-0"><a href="logout.php" class="forgot-password">Logout</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/js/all.min.js"></script>
    <script>
        document.getElementById('testSelectionForm').addEventListener('submit', function(e) {
            const course = document.getElementById('course').value;
            const test = document.getElementById('test').value;
            
            if (!course || !test) {
                e.preventDefault();
                alert('Please select both course and test');
                return false;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>