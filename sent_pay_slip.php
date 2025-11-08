<?php
session_start();
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


// Check if user is logged in as staff
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.html");
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$host = 'bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com';
$dbname = 'bzbnom7tqqucjcivbuxo';
$username = 'uwgxq8otzk6mhome';
$password = '8oQDCXxH6aqYgvkG7g8t';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_payslip'])) {
    $staff_email = $_POST['staff_email'];
    $basic_salary = $_POST['basic_salary'];
    $hra = $_POST['hra'];
    $conveyance = $_POST['conveyance'];
    $medical_allowance = $_POST['medical_allowance'];
    $special_allowance = $_POST['special_allowance'];
    $professional_tax = $_POST['professional_tax'];
    $tds = $_POST['tds'];
    $pf = $_POST['pf'];
    $leave_deduction = $_POST['leave_deduction'] ?? 0;
    $late_deduction = $_POST['late_deduction'] ?? 0;
    $net_salary = $_POST['net_salary'];
    $pay_period = $_POST['pay_period'];
    
    // Get staff details
    $stmt = $pdo->prepare("SELECT id, username, email FROM staff WHERE email = ?");
    $stmt->execute([$staff_email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff) {
        // Generate payslip HTML
        $payslip_html = generatePayslipHTML($staff, [
            'basic_salary' => $basic_salary,
            'hra' => $hra,
            'conveyance' => $conveyance,
            'medical_allowance' => $medical_allowance,
            'special_allowance' => $special_allowance,
            'professional_tax' => $professional_tax,
            'tds' => $tds,
            'pf' => $pf,
            'leave_deduction' => $leave_deduction,
            'late_deduction' => $late_deduction,
            'net_salary' => $net_salary,
            'pay_period' => $pay_period
        ]);
        
        // Send email
        if (sendPayslipEmail($staff['email'], $staff['username'], $payslip_html)) {
            $_SESSION['message'] = "Payslip sent successfully to " . $staff['email'];
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Failed to send payslip. Please try again.";
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = "Staff member not found!";
        $_SESSION['message_type'] = 'error';
    }
    
    header("Location: sent_pay_slip.php");
    exit();
}

// Function to generate payslip HTML with company logo
function generatePayslipHTML($staff, $salary_data) {
    $total_earnings = $salary_data['basic_salary'] + $salary_data['hra'] + $salary_data['conveyance'] + 
                     $salary_data['medical_allowance'] + $salary_data['special_allowance'];
    $total_deductions = $salary_data['professional_tax'] + $salary_data['tds'] + $salary_data['pf'] + 
                       $salary_data['leave_deduction'] + $salary_data['late_deduction'];
    
    // Company logo path - update this to your actual logo path
    $company_logo = 'https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: 'Arial', sans-serif; 
                margin: 0; 
                padding: 20px; 
                background-color: #f5f5f5;
            }
            .email-container {
                max-width: 700px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                overflow: hidden;
            }
            .header {
                background: linear-gradient(135deg, #2c3e50, #3498db);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .logo-container {
                margin-bottom: 15px;
            }
            .company-logo {
                max-width: 150px;
                height: auto;
                border-radius: 5px;
            }
            .company-name {
                font-size: 28px;
                font-weight: bold;
                margin: 10px 0 5px 0;
            }
            .company-tagline {
                font-size: 16px;
                opacity: 0.9;
                font-style: italic;
            }
            .payslip-container {
                padding: 30px;
            }
            .payslip-title {
                text-align: center;
                font-size: 24px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 10px;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
            }
            .pay-period {
                text-align: center;
                color: #7f8c8d;
                margin-bottom: 30px;
                font-size: 16px;
            }
            .section {
                margin: 25px 0;
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                border-left: 4px solid #3498db;
            }
            .section-title {
                font-size: 18px;
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 15px;
                padding-bottom: 8px;
                border-bottom: 1px solid #ddd;
            }
            .row {
                display: flex;
                justify-content: space-between;
                margin: 8px 0;
                padding: 5px 0;
            }
            .row:nth-child(even) {
                background-color: rgba(255,255,255,0.5);
            }
            .label {
                font-weight: 600;
                color: #34495e;
            }
            .amount {
                font-weight: 600;
                text-align: right;
                color: #2c3e50;
            }
            .total-row {
                border-top: 2px solid #bdc3c7;
                padding-top: 12px;
                margin-top: 12px;
                font-weight: bold;
                background-color: #ecf0f1 !important;
                border-radius: 4px;
                padding: 10px;
            }
            .net-salary {
                background: linear-gradient(135deg, #27ae60, #2ecc71) !important;
                color: white;
                border-radius: 8px;
                padding: 15px;
                margin-top: 20px;
            }
            .net-salary .label,
            .net-salary .amount {
                color: white;
                font-size: 18px;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding: 20px;
                background: #34495e;
                color: white;
                border-radius: 0 0 10px 10px;
            }
            .footer-text {
                font-style: italic;
                opacity: 0.8;
                margin-bottom: 10px;
            }
            .contact-info {
                font-size: 14px;
                opacity: 0.9;
            }
            .highlight {
                color: #3498db;
                font-weight: bold;
            }
            .deduction-note {
                font-size: 12px;
                color: #e74c3c;
                margin-top: 5px;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <div class='logo-container'>
                    <img src='$company_logo' alt='Tekksol Global' class='company-logo'>
                </div>
                <div class='company-name'>TEKKSOL GLOBAL</div>
                <div class='company-tagline'>Your search ends here</div>
            </div>
            
            <div class='payslip-container'>
                <div class='payslip-title'>SALARY PAYSLIP</div>
                <div class='pay-period'>Pay Period: <span class='highlight'>{$salary_data['pay_period']}</span></div>
                
                <div class='section'>
                    <div class='section-title'>Employee Details</div>
                    <div class='row'>
                        <span class='label'>Employee Name:</span>
                        <span class='amount'>{$staff['username']}</span>
                    </div>
                    <div class='row'>
                        <span class='label'>Employee ID:</span>
                        <span class='amount'>TG{$staff['id']}</span>
                    </div>
                    <div class='row'>
                        <span class='label'>Email:</span>
                        <span class='amount'>{$staff['email']}</span>
                    </div>
                </div>
                
                <div class='section'>
                    <div class='section-title'>Earnings</div>
                    <div class='row'>
                        <span>Basic Salary:</span>
                        <span class='amount'>₹" . number_format($salary_data['basic_salary'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>House Rent Allowance (HRA):</span>
                        <span class='amount'>₹" . number_format($salary_data['hra'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>Conveyance Allowance:</span>
                        <span class='amount'>₹" . number_format($salary_data['conveyance'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>Medical Allowance:</span>
                        <span class='amount'>₹" . number_format($salary_data['medical_allowance'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>Special Allowance:</span>
                        <span class='amount'>₹" . number_format($salary_data['special_allowance'], 2) . "</span>
                    </div>
                    <div class='row total-row'>
                        <span>Total Earnings:</span>
                        <span class='amount'>₹" . number_format($total_earnings, 2) . "</span>
                    </div>
                </div>
                
                <div class='section'>
                    <div class='section-title'>Deductions</div>
                    <div class='row'>
                        <span>Professional Tax:</span>
                        <span class='amount'>₹" . number_format($salary_data['professional_tax'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>TDS:</span>
                        <span class='amount'>₹" . number_format($salary_data['tds'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>Provident Fund (PF):</span>
                        <span class='amount'>₹" . number_format($salary_data['pf'], 2) . "</span>
                    </div>
                    <div class='row'>
                        <span>Leave Deduction:</span>
                        <span class='amount'>₹" . number_format($salary_data['leave_deduction'], 2) . "</span>
                    </div>
                    <div class='deduction-note'>Deduction for unauthorized leaves</div>
                    <div class='row'>
                        <span>Late Deduction:</span>
                        <span class='amount'>₹" . number_format($salary_data['late_deduction'], 2) . "</span>
                    </div>
                    <div class='deduction-note'>Deduction for late arrivals</div>
                    <div class='row total-row'>
                        <span>Total Deductions:</span>
                        <span class='amount'>₹" . number_format($total_deductions, 2) . "</span>
                    </div>
                </div>
                
                <div class='section'>
                    <div class='section-title'>Net Salary</div>
                    <div class='row net-salary'>
                        <span class='label'>Net Amount Payable:</span>
                        <span class='amount'>₹" . number_format($salary_data['net_salary'], 2) . "</span>
                    </div>
                </div>
            </div>
            
            <div class='footer'>
                <div class='footer-text'>
                    This is a computer-generated payslip and does not require signature.
                </div>
                <div class='contact-info'>
                    Tekksol Global | HR Department | hr@tekksolglobal.com | +91-XXXXXXXXXX
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Function to send email using PHPMailer
function sendPayslipEmail($to_email, $staff_name, $payslip_html) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubanvijay1000@gmail.com';
        $mail->Password = 'nryb ijpp aafa yzwr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global HR');
        $mail->addAddress($to_email, $staff_name);
        $mail->addReplyTo('hr@tekksolglobal.com', 'HR Department');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Salary Payslip - Tekksol Global";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background: linear-gradient(135deg, #2c3e50, #3498db); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h2>Tekksol Global</h2>
                    <p><em>Your search ends here</em></p>
                </div>
                
                <div style='padding: 20px; background: #f8f9fa;'>
                    <h3>Dear $staff_name,</h3>
                    <p>Please find your salary payslip for the mentioned period attached below.</p>
                    <p>This payslip contains detailed information about your earnings, deductions, and net salary.</p>
                    
                    <div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #3498db;'>
                        <p><strong>📋 Important Information:</strong></p>
                        <ul>
                            <li>Keep this payslip for your records</li>
                            <li>Use it for loan applications and tax filings</li>
                            <li>Contact HR for any discrepancies</li>
                            <li>Note: Leave and late deductions are applied as per company policy</li>
                        </ul>
                    </div>
                    
                    <p>If you have any questions regarding your payslip, please contact the HR department.</p>
                    
                    <p>Best regards,<br>
                    <strong>HR Department</strong><br>
                    Tekksol Global</p>
                </div>
                
                <div style='background: #34495e; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px;'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 2px dashed #bdc3c7;'>
                
                " . $payslip_html . "
            </div>
        ";
        
        // Alternative body for non-HTML email clients
        $mail->AltBody = "Dear $staff_name,\n\nPlease find your salary payslip attached.\n\nYour payslip contains detailed information about your earnings, deductions (including leave and late deductions), and net salary for the period.\n\nKeep this payslip for your records and use it for loan applications and tax filings.\n\nIf you have any questions, please contact the HR department.\n\nBest regards,\nHR Department\nTekksol Global";
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

// Get all staff members for dropdown
$staff_stmt = $pdo->query("SELECT id, username, email FROM staff ORDER BY username");
$staff_members = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Payslip - Tekksol Global</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payslip-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        .salary-input { max-width: 200px; }
        .total-row { background-color: #e8f5e8; font-weight: bold; }
        .logo-preview {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .deduction-section {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2" style="background-color: darkgrey; color: white; height: 100vh;">
                <div class="sidebar-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="https://www.tekksolglobal.com/wp-content/uploads/2024/05/WhatsApp_Image_2024-05-16_at_11.40.04_a1aa6339-removebg-preview-e1716316097904.png" alt="Tekksol Global" class="logo-preview">

                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="sent_pay_slip.php" class="nav-link text-white active">
                                <i class="fas fa-paper-plane me-2"></i>Send Payslip
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <h2 class="mb-4">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Send Payslip to Staff
                    </h2>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>Payslip Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="payslipForm">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="staff_email" class="form-label">Select Staff Member *</label>
                                        <select class="form-select" id="staff_email" name="staff_email" required>
                                            <option value="">Select Staff Member</option>
                                            <?php foreach ($staff_members as $staff): ?>
                                                <option value="<?php echo $staff['email']; ?>">
                                                    <?php echo htmlspecialchars($staff['username'] . ' (' . $staff['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="pay_period" class="form-label">Pay Period *</label>
                                        <input type="month" class="form-control" id="pay_period" name="pay_period" required>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-3 text-primary">
                                    <i class="fas fa-arrow-up me-2"></i>Earnings
                                </h6>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="basic_salary" class="form-label">Basic Salary</label>
                                        <input type="number" class="form-control salary-input" id="basic_salary" name="basic_salary" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hra" class="form-label">House Rent Allowance (HRA)</label>
                                        <input type="number" class="form-control salary-input" id="hra" name="hra" step="0.01" required>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="conveyance" class="form-label">Conveyance Allowance</label>
                                        <input type="number" class="form-control salary-input" id="conveyance" name="conveyance" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="medical_allowance" class="form-label">Medical Allowance</label>
                                        <input type="number" class="form-control salary-input" id="medical_allowance" name="medical_allowance" step="0.01" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="special_allowance" class="form-label">Special Allowance</label>
                                        <input type="number" class="form-control salary-input" id="special_allowance" name="special_allowance" step="0.01" required>
                                    </div>
                                </div>

                                <h6 class="mt-4 mb-3 text-danger">
                                    <i class="fas fa-arrow-down me-2"></i>Standard Deductions
                                </h6>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="professional_tax" class="form-label">Professional Tax</label>
                                        <input type="number" class="form-control salary-input" id="professional_tax" name="professional_tax" step="0.01" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tds" class="form-label">TDS</label>
                                        <input type="number" class="form-control salary-input" id="tds" name="tds" step="0.01" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="pf" class="form-label">Provident Fund (PF)</label>
                                        <input type="number" class="form-control salary-input" id="pf" name="pf" step="0.01" required>
                                    </div>
                                </div>

                                <!-- NEW: Leave and Late Deductions -->
                                <h6 class="mt-4 mb-3 text-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Attendance Deductions
                                </h6>
                                <div class="row mb-2">
                                    <div class="col-md-6">
                                        <label for="leave_deduction" class="form-label">Leave Deduction</label>
                                        <input type="number" class="form-control salary-input" id="leave_deduction" name="leave_deduction" step="0.01" value="0">
                                        <small class="text-muted">Deduction for unauthorized leaves</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="late_deduction" class="form-label">Late Deduction</label>
                                        <input type="number" class="form-control salary-input" id="late_deduction" name="late_deduction" step="0.01" value="0">
                                        <small class="text-muted">Deduction for late arrivals</small>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="net_salary" class="form-label">Net Salary</label>
                                        <input type="number" class="form-control salary-input total-row" id="net_salary" name="net_salary" step="0.01" readonly required>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" name="send_payslip" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Send Payslip
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-lg" onclick="calculateNetSalary()">
                                        <i class="fas fa-calculator me-2"></i>Calculate Net Salary
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateNetSalary() {
            // Get earnings
            const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
            const hra = parseFloat(document.getElementById('hra').value) || 0;
            const conveyance = parseFloat(document.getElementById('conveyance').value) || 0;
            const medical = parseFloat(document.getElementById('medical_allowance').value) || 0;
            const special = parseFloat(document.getElementById('special_allowance').value) || 0;
            
            // Get deductions
            const ptax = parseFloat(document.getElementById('professional_tax').value) || 0;
            const tds = parseFloat(document.getElementById('tds').value) || 0;
            const pf = parseFloat(document.getElementById('pf').value) || 0;
            const leaveDeduction = parseFloat(document.getElementById('leave_deduction').value) || 0;
            const lateDeduction = parseFloat(document.getElementById('late_deduction').value) || 0;
            
            // Calculate totals
            const totalEarnings = basic + hra + conveyance + medical + special;
            const totalDeductions = ptax + tds + pf + leaveDeduction + lateDeduction;
            const netSalary = totalEarnings - totalDeductions;
            
            // Set net salary
            document.getElementById('net_salary').value = netSalary.toFixed(2);
        }
        
        // Auto-calculate when any salary field changes
        document.querySelectorAll('.salary-input').forEach(input => {
            input.addEventListener('change', calculateNetSalary);
        });

        // Initialize calculation on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateNetSalary();
        });
    </script>
</body>
</html>