<?php
// send_email.php - Contact Form Handler
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Turn off error display for production, log instead
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST["name"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $subject = trim($_POST["subject"] ?? '');
    $phone = trim($_POST["phone"] ?? '');
    $message = trim($_POST["message"] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($phone) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid email address.']);
        exit;
    }
    
    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rubanvijay1000@gmail.com'; // Your Gmail
        $mail->Password = 'nryb ijpp aafa yzwr'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global Website');
        $mail->addAddress('rubanvijay1000@gmail.com');
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Contact Form Submission: $subject";
        $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #06BBCC; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                    .info-box { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #06BBCC; }
                    .message-box { background: #f0f8ff; padding: 20px; border-radius: 5px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>New Contact Form Submission</h1>
                    </div>
                    <div class='content'>
                        <div class='info-box'>
                            <h3>Contact Details:</h3>
                            <p><strong>Name:</strong> $name</p>
                            <p><strong>Email:</strong> <a href='mailto:$email'>$email</a></p>
                            <p><strong>Phone:</strong> <a href='tel:$phone'>$phone</a></p>
                            <p><strong>Subject:</strong> $subject</p>
                            <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
                        </div>
                        
                        <div class='message-box'>
                            <h3>Message:</h3>
                            <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        $mail->AltBody = "
New Contact Form Submission

Contact Details:
Name: $name
Email: $email
Phone: $phone
Subject: $subject
Submitted: " . date('F j, Y, g:i a') . "

Message:
$message
        ";
        
        $mail->send();
        
        // Send confirmation email to the customer
        $confirmMail = new PHPMailer(true);
        try {
            $confirmMail->isSMTP();
            $confirmMail->Host = 'smtp.gmail.com';
            $confirmMail->SMTPAuth = true;
            $confirmMail->Username = 'rubanvijay1000@gmail.com';
            $confirmMail->Password = 'nryb ijpp aafa yzwr';
            $confirmMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $confirmMail->Port = 587;
            
            $confirmMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
            $confirmMail->addAddress($email, $name);
            $confirmMail->addReplyTo('info@tekksolglobal.com', 'Tekksol Global');
            
            $confirmMail->isHTML(true);
            $confirmMail->Subject = "We Received Your Message - Tekksol Global";
            $confirmMail->Body = "
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
                            <h1>Thank You for Contacting Us!</h1>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>$name</strong>,</p>
                            
                            <p>Thank you for reaching out to Tekksol Global. We have received your message and our team will review it carefully.</p>
                            
                            <div class='info-box'>
                                <h3>Your Message Summary:</h3>
                                <p><strong>Subject:</strong> $subject</p>
                                <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
                            </div>
                            
                            <p><strong>What happens next?</strong><br>
                            Our team typically responds within 24-48 hours during business days (Mon-Sat, 9:30 AM - 6:00 PM). You will receive a response at the email address you provided: <strong>$email</strong></p>
                            
                            <p>If your inquiry is urgent, feel free to call us at <strong>+91 9042527746</strong>.</p>
                        </div>
                        <div class='footer'>
                            <p><strong>Tekksol Global</strong><br>
                            OMR, Rajiv Gandhi Salai, Chennai, Tamil Nadu 600097<br>
                            Phone: +91 9042527746 | Email: info@tekksolglobal.com</p>
                            <p><a href='https://www.tekksolglobal.com'>Visit our website</a></p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $confirmMail->AltBody = "
Thank You for Contacting Us!

Dear $name,

Thank you for reaching out to Tekksol Global. We have received your message and our team will review it carefully.

Your Message Summary:
Subject: $subject
Submitted: " . date('F j, Y, g:i a') . "

What happens next?
Our team typically responds within 24-48 hours during business days (Mon-Sat, 9:30 AM - 6:00 PM). You will receive a response at: $email

If your inquiry is urgent, feel free to call us at +91 9042527746.

Tekksol Global
OMR, Rajiv Gandhi Salai, Chennai, Tamil Nadu 600097
Phone: +91 9042527746 | Email: info@tekksolglobal.com
Website: https://www.tekksolglobal.com
            ";
            
            $confirmMail->send();
            
            // Success response
            echo json_encode([
                'status' => 'success', 
                'message' => 'Thank you for contacting us! We have received your message and will respond within 24-48 hours.'
            ]);
            
        } catch (Exception $e) {
            // If confirmation email fails but main email sent, still show success
            echo json_encode([
                'status' => 'success', 
                'message' => 'Thank you for your message. We have received it and will contact you soon.'
            ]);
        }
        
    } catch (Exception $e) {
        // If email fails completely
        echo json_encode([
            'status' => 'error', 
            'message' => 'Sorry, there was a problem sending your message. Please try again later or contact us directly at info@tekksolglobal.com.'
        ]);
    }
    
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>