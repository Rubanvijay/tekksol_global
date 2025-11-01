<?php
// trainer_registration.php - FIXED TO MATCH WORKING send_email.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $firstName = trim($_POST["firstName"] ?? '');
    $lastName = trim($_POST["lastName"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $profession = trim($_POST["profession"] ?? '');
    $experience = trim($_POST["experience"] ?? '');
    $specializations = trim($_POST["specializations"] ?? '');
    $currentCompany = trim($_POST["currentCompany"] ?? '');
    $additionalInfo = trim($_POST["additionalInfo"] ?? '');
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($profession) || empty($experience)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    
    // Create submissions directory if it doesn't exist
    if (!is_dir('trainer_submissions')) {
        mkdir('trainer_submissions', 0755, true);
    }
    
    // Save submission data (always do this as backup)
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
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
    ];
    
    $filename = 'trainer_submissions/trainer_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($filename, json_encode($submission, JSON_PRETTY_PRINT));
    
    // Try to send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail - EXACTLY LIKE send_email.php
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubanvijay1000@gmail.com'; // Your Gmail
        $mail->Password = 'nryb ijpp aafa yzwr'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients - Send confirmation to the trainer
        $mail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
        $mail->addAddress($email, $firstName . ' ' . $lastName); // Send to the trainer
        $mail->addReplyTo('info@tekksolglobal.com', 'Tekksol Global');
        
        // Content - Confirmation email to trainer
        $mail->isHTML(true);
        $mail->Subject = "Trainer Application Received - Tekksol Global";
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #06BBCC; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                    .footer { text-align: center; margin-top: 20px; padding: 20px; color: #666; font-size: 14px; }
                    .info-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #06BBCC; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Trainer Application Received</h1>
                    </div>
                    <div class='content'>
                        <p>Dear <strong>$firstName $lastName</strong>,</p>
                        
                        <p>Thank you for your interest in becoming a trainer at Tekksol Global! We have received your application and our team will review it carefully.</p>
                        
                        <div class='info-box'>
                            <h3>Your Application Details:</h3>
                            <p><strong>Name:</strong> $firstName $lastName</p>
                            <p><strong>Email:</strong> $email</p>
                            <p><strong>Phone:</strong> $phone</p>
                            <p><strong>Profession:</strong> $profession</p>
                            <p><strong>Experience:</strong> $experience years</p>
                            <p><strong>Specializations:</strong> " . ($specializations ?: 'Not specified') . "</p>
                            <p><strong>Current Company:</strong> " . ($currentCompany ?: 'Not specified') . "</p>
                            <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
                        </div>
                        
                        <p><strong>What happens next?</strong><br>
                        Our training team will review your application within 2-3 business days. If your profile matches our requirements, we will contact you for an interview and discussion about potential training opportunities.</p>
                    </div>
                    <div class='footer'>
                        <p><strong>Tekksol Global - Training Department</strong><br>
                        OMR, Rajiv Gandhi Salai, Chennai, Tamil Nadu 600097<br>
                        Phone: +91 9042527746 | Email: info@tekksolglobal.com</p>
                        <p><a href='https://www.tekksolglobal.com'>Visit our website</a></p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Alternative plain text version
        $mail->AltBody = "
Trainer Application Received - Tekksol Global

Dear $firstName $lastName,

Thank you for your interest in becoming a trainer at Tekksol Global! We have received your application and our team will review it carefully.

Your Application Details:
Name: $firstName $lastName
Email: $email
Phone: $phone
Profession: $profession
Experience: $experience years
Specializations: " . ($specializations ?: 'Not specified') . "
Current Company: " . ($currentCompany ?: 'Not specified') . "
Submitted: " . date('F j, Y, g:i a') . "

What happens next?
Our training team will review your application within 2-3 business days. If your profile matches our requirements, we will contact you for an interview.

Tekksol Global - Training Department
OMR, Rajiv Gandhi Salai, Chennai, Tamil Nadu 600097
Phone: +91 9042527746 | Email: info@tekksolglobal.com
Website: https://www.tekksolglobal.com
        ";
        
        $mail->send();
        
        // Also send a notification to yourself (optional)
        $notificationMail = new PHPMailer(true);
        try {
            $notificationMail->isSMTP();
            $notificationMail->Host = 'smtp.gmail.com';
            $notificationMail->SMTPAuth = true;
            $notificationMail->Username = 'rubanvijay1000@gmail.com';
            $notificationMail->Password = 'nryb ijpp aafa yzwr';
            $notificationMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $notificationMail->Port = 587;
            
            $notificationMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global Website');
            $notificationMail->addAddress('rubanvijay1000@gmail.com'); // Notification to you
            
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
                <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
            ";
            
            $notificationMail->send();
        } catch (Exception $e) {
            // Silently fail for notification email, but trainer email is more important
            error_log("Trainer notification email failed: " . $e->getMessage());
        }
        
        // Success response
        echo json_encode([
            'status' => 'success', 
            'message' => 'Thank you! Your application has been submitted successfully. We have sent a confirmation email to your inbox.'
        ]);
        
    } catch (Exception $e) {
        // If email fails, but we still saved the data, return success
        echo json_encode([
            'status' => 'success', 
            'message' => 'Thank you! Your application has been received. We will review it and contact you within 2-3 business days.'
        ]);
        
        // Log the error for debugging - USING error_log() LIKE send_email.php
        error_log("Trainer PHPMailer Error: " . $mail->ErrorInfo);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>