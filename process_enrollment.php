<?php
// process_enrollment.php - Course Enrollment Handler
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include PHPMailer files
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://tekksol-global.onrender.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Turn off error display for production
error_reporting(0);
ini_set('display_errors', 0);

// Function to clean input
function clean_input($data) {
    return trim(htmlspecialchars($data ?? ''));
}

// Function to format date
function format_date() {
    return date('F d, Y h:i A');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and clean form data
    $fullName = clean_input($_POST["fullName"]);
    $email = clean_input($_POST["email"]);
    $phone = clean_input($_POST["phone"]);
    $qualification = clean_input($_POST["qualification"]);
    $address = clean_input($_POST["address"]);
    $mode = clean_input($_POST["mode"]);
    $message = clean_input($_POST["message"]);
    
    // Get selected courses
    $selectedCourses = isset($_POST["courses"]) ? $_POST["courses"] : [];
    
    // Validate required fields
    if (empty($fullName) || empty($email) || empty($phone) || empty($mode)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please provide a valid email address.'
        ]);
        exit;
    }
    
    // Validate course selection
    if (empty($selectedCourses)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Please select at least one course.'
        ]);
        exit;
    }
    
    // Format courses list
    $coursesList = implode(', ', $selectedCourses);
    $coursesHtml = '<ul style="margin: 10px 0; padding-left: 20px;">';
    foreach ($selectedCourses as $course) {
        $coursesHtml .= '<li style="margin: 5px 0;">' . htmlspecialchars($course) . '</li>';
    }
    $coursesHtml .= '</ul>';
    
    try {
        // ==========================================
        // EMAIL 1: Send to Admin/Company
        // ==========================================
        $adminMail = new PHPMailer(true);
        
        // SMTP Configuration
        $adminMail->isSMTP();
        $adminMail->Host = 'smtp.gmail.com';
        $adminMail->SMTPAuth = true;
        $adminMail->Username = 'rubanvijay1000@gmail.com';
        $adminMail->Password = 'nryb ijpp aafa yzwr';
        $adminMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $adminMail->Port = 587;
        $adminMail->Timeout = 30;
        $adminMail->SMTPDebug = 0;
        
        // Admin email content
        $adminMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global Enrollment System');
        $adminMail->addAddress('rubanvijay1000@gmail.com', 'Tekksol Global Admin');
        $adminMail->addReplyTo($email, $fullName);
        
        $adminMail->isHTML(true);
        $adminMail->Subject = "New Course Enrollment - $fullName";
        
        $adminMail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #06BBCC, #0596a8); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                .info-box { background: white; padding: 20px; margin: 15px 0; border-left: 4px solid #06BBCC; border-radius: 5px; }
                .info-row { margin: 10px 0; }
                .label { font-weight: bold; color: #06BBCC; display: inline-block; width: 150px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                h2 { margin: 0; font-size: 24px; }
                .badge { background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🎓 New Course Enrollment</h2>
                    <p style='margin: 10px 0 0 0;'>Received on " . format_date() . "</p>
                </div>
                <div class='content'>
                    <div class='info-box'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>Student Information</h3>
                        <div class='info-row'><span class='label'>Name:</span> $fullName</div>
                        <div class='info-row'><span class='label'>Email:</span> <a href='mailto:$email'>$email</a></div>
                        <div class='info-row'><span class='label'>Phone:</span> <a href='tel:$phone'>$phone</a></div>
                        <div class='info-row'><span class='label'>Qualification:</span> " . ($qualification ?: 'Not provided') . "</div>
                        <div class='info-row'><span class='label'>Address:</span> " . ($address ?: 'Not provided') . "</div>
                    </div>
                    
                    <div class='info-box'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>Course Details</h3>
                        <div class='info-row'><span class='label'>Selected Courses:</span></div>
                        $coursesHtml
                        <div class='info-row'><span class='label'>Learning Mode:</span> <span class='badge'>$mode</span></div>
                    </div>
                    
                    " . (!empty($message) ? "
                    <div class='info-box'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>Additional Message</h3>
                        <p style='margin: 10px 0;'>" . nl2br($message) . "</p>
                    </div>
                    " : "") . "
                    
                    <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                        <strong>⚡ Action Required:</strong> Please contact the student within 24 hours to confirm enrollment and provide next steps.
                    </div>
                </div>
                <div class='footer'>
                    <p>This is an automated notification from Tekksol Global Enrollment System</p>
                    <p>© " . date('Y') . " Tekksol Global. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $adminMail->AltBody = "New Course Enrollment\n\n" .
                              "Student: $fullName\n" .
                              "Email: $email\n" .
                              "Phone: $phone\n" .
                              "Courses: $coursesList\n" .
                              "Mode: $mode\n" .
                              ($message ? "Message: $message\n" : "");
        
        // Send admin email
        $adminMailSent = $adminMail->send();
        
        // ==========================================
        // EMAIL 2: Send confirmation to Student
        // ==========================================
        $studentMail = new PHPMailer(true);
        
        // SMTP Configuration
        $studentMail->isSMTP();
        $studentMail->Host = 'smtp.gmail.com';
        $studentMail->SMTPAuth = true;
        $studentMail->Username = 'rubanvijay1000@gmail.com';
        $studentMail->Password = 'nryb ijpp aafa yzwr';
        $studentMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $studentMail->Port = 587;
        $studentMail->Timeout = 30;
        $studentMail->SMTPDebug = 0;
        
        // Student email content
        $studentMail->setFrom('noreply@tekksolglobal.com', 'Tekksol Global');
        $studentMail->addAddress($email, $fullName);
        $studentMail->addReplyTo('info@tekksolglobal.com', 'Tekksol Global Support');
        
        $studentMail->isHTML(true);
        $studentMail->Subject = "Enrollment Confirmation - Welcome to Tekksol Global!";
        
        $studentMail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #06BBCC, #0596a8); color: white; padding: 40px 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
                .welcome-box { background: #e7f9fb; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
                .info-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
                .course-list { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #06BBCC; }
                .button { display: inline-block; background: #06BBCC; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; border-radius: 0 0 10px 10px; }
                .highlight { color: #06BBCC; font-weight: bold; }
                h2 { margin: 0; font-size: 28px; }
                .icon { font-size: 48px; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='icon'>🎓</div>
                    <h2>Welcome to Tekksol Global!</h2>
                    <p style='margin: 10px 0 0 0; font-size: 16px;'>Thank you for enrolling with us</p>
                </div>
                
                <div class='content'>
                    <div class='welcome-box'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>🎉 Enrollment Received Successfully!</h3>
                        <p style='margin: 10px 0; font-size: 14px;'>Your journey to excellence begins here</p>
                    </div>
                    
                    <p>Dear <strong>$fullName</strong>,</p>
                    
                    <p>We are thrilled to confirm that we have received your enrollment request. Our team is excited to have you join the Tekksol Global family!</p>
                    
                    <div class='info-section'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>📋 Your Enrollment Details</h3>
                        <p><strong>Student Name:</strong> $fullName</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Phone:</strong> $phone</p>
                        <p><strong>Learning Mode:</strong> <span class='highlight'>$mode</span></p>
                    </div>
                    
                    <div class='course-list'>
                        <h4 style='color: #06BBCC; margin-top: 0;'>Selected Courses:</h4>
                        $coursesHtml
                    </div>
                    
                    <div class='info-section'>
                        <h3 style='color: #06BBCC; margin-top: 0;'>📞 What Happens Next?</h3>
                        <ul style='line-height: 1.8;'>
                            <li>Our admission team will contact you within <strong>24-48 hours</strong></li>
                            <li>You'll receive detailed information about course schedule and fees</li>
                            <li>We'll guide you through the enrollment completion process</li>
                            <li>You'll get access to our learning management system</li>
                        </ul>
                    </div>
                    
                    <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>⏰ Urgent queries?</strong></p>
                        <p style='margin: 5px 0;'>Call us: <a href='tel:+919042527746'>+91 9042527746</a></p>
                        <p style='margin: 5px 0;'>Email: <a href='mailto:info@tekksolglobal.com'>info@tekksolglobal.com</a></p>
                        <p style='margin: 5px 0;'>WhatsApp: <a href='https://wa.me/919042527746'>+91 9042527746</a></p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='https://tekksol-global.onrender.com/courses.html' class='button' style='color: white;'>Explore More Courses</a>
                    </div>
                    
                    <p style='margin-top: 30px;'>We look forward to helping you achieve your career goals!</p>
                    
                    <p>Best regards,<br>
                    <strong>The Tekksol Global Team</strong><br>
                    <em>Empowering Careers Through Technology</em></p>
                </div>
                
                <div class='footer'>
                    <p><strong>Tekksol Global</strong></p>
                    <p>OMR, Rajiv Gandhi Salai, Chennai</p>
                    <p>📞 +91 9042527746 | 📧 info@tekksolglobal.com</p>
                    <p style='margin-top: 15px;'>© " . date('Y') . " Tekksol Global. All rights reserved.</p>
                    <p style='font-size: 10px; color: #999;'>This email was sent because you enrolled for courses at Tekksol Global</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $studentMail->AltBody = "Welcome to Tekksol Global!\n\n" .
                                "Dear $fullName,\n\n" .
                                "Thank you for enrolling with us!\n\n" .
                                "Your Enrollment Details:\n" .
                                "Student Name: $fullName\n" .
                                "Email: $email\n" .
                                "Phone: $phone\n" .
                                "Selected Courses: $coursesList\n" .
                                "Learning Mode: $mode\n\n" .
                                "What happens next?\n" .
                                "- Our team will contact you within 24-48 hours\n" .
                                "- You'll receive course schedule and fee details\n" .
                                "- We'll guide you through enrollment completion\n\n" .
                                "For urgent queries:\n" .
                                "Call: +91 9042527746\n" .
                                "Email: info@tekksolglobal.com\n\n" .
                                "Best regards,\n" .
                                "The Tekksol Global Team";
        
        // Send student email
        $studentMailSent = $studentMail->send();
        
        // Check if both emails were sent successfully
        if ($adminMailSent && $studentMailSent) {
            echo json_encode([
                'status' => 'success',
                'message' => '<p><strong>Thank you for enrolling!</strong></p>' .
                            '<p>We have sent a confirmation email to <strong>' . $email . '</strong></p>' .
                            '<p>Our team will contact you within 24-48 hours with further details.</p>' .
                            '<p style="margin-top: 15px;"><small>Check your spam folder if you don\'t see the email.</small></p>'
            ]);
        } else {
            // If admin email sent but student email failed
            if ($adminMailSent) {
                echo json_encode([
                    'status' => 'success',
                    'message' => '<p>Your enrollment has been received successfully!</p>' .
                                '<p>However, we couldn\'t send a confirmation email. Our team will contact you shortly.</p>'
                ]);
            } else {
                throw new Exception('Failed to send enrollment emails');
            }
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'We received your enrollment request, but there was an issue sending confirmation emails. Our team will contact you shortly at ' . $phone . '. For immediate assistance, please call +91 9042527746.'
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method.'
    ]);
}
?>