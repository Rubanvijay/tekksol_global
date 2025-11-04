<?php
// send_email.php - Simplified for Render
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://tekksol-global.onrender.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

// Function to clean input
function clean_input($data) {
    return trim(htmlspecialchars($data ?? ''));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and clean form data
    $name = clean_input($_POST["name"]);
    $email = clean_input($_POST["email"]);
    $subject = clean_input($_POST["subject"]);
    $phone = clean_input($_POST["phone"]);
    $message = clean_input($_POST["message"]);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($phone) || empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    
    try {
        // Create main email
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubanvijay1000@gmail.com';
        $mail->Password = 'nryb ijpp aafa yzwr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30; // Increase timeout
        $mail->SMTPDebug = 0; // Set to 0 for production
        
        // Email content
        $mail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
        $mail->addAddress('rubanvijay1000@gmail.com');
        $mail->addReplyTo($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = "New Contact: $subject";
        
        $mail->Body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Phone:</strong> $phone</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
        ";
        
        $mail->AltBody = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\nMessage: $message";
        
        // Send main email
        if ($mail->send()) {
            // Try to send confirmation email (but don't fail if this doesn't work)
            try {
                $confirmMail = new PHPMailer(true);
                $confirmMail->isSMTP();
                $confirmMail->Host = 'smtp.gmail.com';
                $confirmMail->SMTPAuth = true;
                $confirmMail->Username = 'rubanvijay1000@gmail.com';
                $confirmMail->Password = 'nryb ijpp aafa yzwr';
                $confirmMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $confirmMail->Port = 587;
                
                $confirmMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
                $confirmMail->addAddress($email, $name);
                
                $confirmMail->isHTML(true);
                $confirmMail->Subject = "We Received Your Message";
                
                $confirmMail->Body = "
                    <h2>Thank You for Contacting Tekksol Global</h2>
                    <p>Dear $name,</p>
                    <p>We have received your message and will get back to you within 24-48 hours.</p>
                    <p><strong>Your Subject:</strong> $subject</p>
                    <p>Best regards,<br>Tekksol Global Team</p>
                ";
                
                $confirmMail->send();
            } catch (Exception $confirmationError) {
                // Ignore confirmation email errors
            }
            
            // Success response
            echo json_encode([
                'status' => 'success', 
                'message' => 'Thank you! Your message has been sent successfully.'
            ]);
        } else {
            throw new Exception('Failed to send email');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Unable to send email. Please try again later or contact us directly at info@tekksolglobal.com.'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
?>