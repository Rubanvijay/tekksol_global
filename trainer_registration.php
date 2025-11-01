<?php
// trainer_registration.php - FIXED FOR FILE UPLOADS
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

// Debug logging
file_put_contents('debug_trainer.log', "=== NEW REQUEST ===\n" . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents('debug_trainer.log', "POST Data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug_trainer.log', "FILES Data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data - use $_POST directly since we're using multipart/form-data
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $profession = isset($_POST['profession']) ? trim($_POST['profession']) : '';
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $specializations = isset($_POST['specializations']) ? trim($_POST['specializations']) : '';
    $currentCompany = isset($_POST['currentCompany']) ? trim($_POST['currentCompany']) : '';
    $additionalInfo = isset($_POST['additionalInfo']) ? trim($_POST['additionalInfo']) : '';
    
    // Debug each field
    file_put_contents('debug_trainer.log', "FIELD CHECK:\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "firstName: '$firstName' (isset: " . (isset($_POST['firstName']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "lastName: '$lastName' (isset: " . (isset($_POST['lastName']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "email: '$email' (isset: " . (isset($_POST['email']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "phone: '$phone' (isset: " . (isset($_POST['phone']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "profession: '$profession' (isset: " . (isset($_POST['profession']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    file_put_contents('debug_trainer.log', "experience: '$experience' (isset: " . (isset($_POST['experience']) ? 'YES' : 'NO') . ")\n", FILE_APPEND);
    
    // SIMPLE VALIDATION - Check if fields exist and are not empty
    $missingFields = [];
    
    if (empty($firstName)) $missingFields[] = 'First Name';
    if (empty($lastName)) $missingFields[] = 'Last Name';
    if (empty($email)) $missingFields[] = 'Email';
    if (empty($phone)) $missingFields[] = 'Phone';
    if (empty($profession)) $missingFields[] = 'Profession';
    
    // Special handling for experience - allow "0"
    if (!isset($_POST['experience']) || $experience === '') {
        $missingFields[] = 'Experience';
    }
    
    if (!empty($missingFields)) {
        file_put_contents('debug_trainer.log', "VALIDATION FAILED: Missing - " . implode(', ', $missingFields) . "\n\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    file_put_contents('debug_trainer.log', "VALIDATION PASSED\n", FILE_APPEND);
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    
    // Create submissions directory if it doesn't exist
    if (!is_dir('trainer_submissions')) {
        mkdir('trainer_submissions', 0755, true);
    }
    
    // Handle file upload
    $resumePath = null;
    $resumeName = 'No resume uploaded';
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $resume = $_FILES['resume'];
        $resumeName = $resume['name'];
        $resumeTmpName = $resume['tmp_name'];
        $resumeSize = $resume['size'];
        $resumeType = $resume['type'];
        
        // Validate file size (5MB max)
        if ($resumeSize > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'Resume file size must be less than 5MB.']);
            exit;
        }
        
        // Validate file type
        $allowedTypes = [
            'application/pdf', 
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!in_array($resumeType, $allowedTypes)) {
            echo json_encode(['status' => 'error', 'message' => 'Please upload a valid document (PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX).']);
            exit;
        }
        
        // Save resume file with safe filename
        $resumeExtension = pathinfo($resumeName, PATHINFO_EXTENSION);
        $safeResumeName = 'resume_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $resumeExtension;
        $resumePath = 'trainer_submissions/' . $safeResumeName;
        
        if (move_uploaded_file($resumeTmpName, $resumePath)) {
            $resumeName = $resume['name'] . " (" . round($resumeSize / 1024, 2) . " KB)";
            file_put_contents('debug_trainer.log', "RESUME UPLOADED: $resumeName -> $resumePath\n", FILE_APPEND);
        } else {
            $resumePath = null;
            $resumeName = 'Resume upload failed';
            file_put_contents('debug_trainer.log', "RESUME UPLOAD FAILED\n", FILE_APPEND);
        }
    } else {
        $uploadError = $_FILES['resume']['error'] ?? 'No file';
        file_put_contents('debug_trainer.log', "RESUME UPLOAD ERROR: $uploadError\n", FILE_APPEND);
    }
    
    // Save submission data
    $submission = [
        'timestamp' => date('Y-m-d H:i:s'),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'profession' => $profession,
        'experience' => $experience,
        'specializations' => $specializations,
        'currentCompany' => $currentCompany,
        'additionalInfo' => $additionalInfo,
        'resume' => $resumeName,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    
    $filename = 'trainer_submissions/trainer_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
    file_put_contents($filename, json_encode($submission, JSON_PRETTY_PRINT));
    file_put_contents('debug_trainer.log', "DATA SAVED: $filename\n", FILE_APPEND);
    
    // Try to send email using PHPMailer
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
        
        // Recipients - Send confirmation to the trainer
        $mail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
        $mail->addAddress($email, $firstName . ' ' . $lastName);
        $mail->addReplyTo('info@tekksolglobal.com', 'Tekksol Global');
        
        // Content - Confirmation email to trainer
        $mail->isHTML(true);
        $mail->Subject = "Trainer Application Received - Tekksol Global";
        $mail->Body = "
            <h3>Thank You for Your Application!</h3>
            <p>Dear $firstName $lastName,</p>
            <p>Thank you for your interest in becoming a trainer at Tekksol Global! We have received your application and our team will review it carefully.</p>
            
            <h4>Your Application Details:</h4>
            <p><strong>Name:</strong> $firstName $lastName</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Profession:</strong> $profession</p>
            <p><strong>Experience:</strong> $experience years</p>
            <p><strong>Specializations:</strong> " . ($specializations ?: 'Not specified') . "</p>
            <p><strong>Current Company:</strong> " . ($currentCompany ?: 'Not specified') . "</p>
            <p><strong>Resume:</strong> $resumeName</p>
            
            <p><strong>What happens next?</strong><br>
            Our training team will review your application within 2-3 business days.</p>
            
            <p>Best regards,<br>
            Tekksol Global Team</p>
        ";
        
        $mail->send();
        file_put_contents('debug_trainer.log', "CONFIRMATION EMAIL SENT TO: $email\n", FILE_APPEND);
        
        // Also send notification email WITH RESUME ATTACHMENT
        $notificationMail = new PHPMailer(true);
        $notificationMail->isSMTP();
        $notificationMail->Host = 'smtp.gmail.com';
        $notificationMail->SMTPAuth = true;
        $notificationMail->Username = 'rubanvijay1000@gmail.com';
        $notificationMail->Password = 'nryb ijpp aafa yzwr';
        $notificationMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $notificationMail->Port = 587;
        
        $notificationMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global Website');
        $notificationMail->addAddress('rubanvijay1000@gmail.com');
        
        $notificationMail->isHTML(true);
        $notificationMail->Subject = "New Trainer Application: $firstName $lastName";
        $notificationMail->Body = "
            <h3>New Trainer Application Received</h3>
            <p><strong>Name:</strong> $firstName $lastName</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Profession:</strong> $profession</p>
            <p><strong>Experience:</strong> $experience years</p>
            <p><strong>Specializations:</strong> " . ($specializations ?: 'Not specified') . "</p>
            <p><strong>Current Company:</strong> " . ($currentCompany ?: 'Not specified') . "</p>
            <p><strong>Additional Info:</strong> " . ($additionalInfo ?: 'None') . "</p>
            <p><strong>Resume:</strong> $resumeName</p>
            <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
        ";
        
        // ATTACH RESUME FILE IF UPLOADED
        if ($resumePath && file_exists($resumePath)) {
            $notificationMail->addAttachment($resumePath, $resumeName);
            file_put_contents('debug_trainer.log', "RESUME ATTACHED: $resumePath\n", FILE_APPEND);
        }
        
        $notificationMail->send();
        file_put_contents('debug_trainer.log', "NOTIFICATION EMAIL SENT WITH RESUME\n\n", FILE_APPEND);
        
        // Success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Thank you! Your application has been submitted successfully. We have sent a confirmation email to your inbox.'
        ]);
        
    } catch (Exception $e) {
        file_put_contents('debug_trainer.log', "EMAIL ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
        
        // Even if email fails, return success since we saved the data
        echo json_encode([
            'status' => 'success', 
            'message' => 'Thank you! Your application has been received. We will review it and contact you within 2-3 business days.'
        ]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>