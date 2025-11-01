<?php
// logout.php
session_start();

// Store username for farewell message
$username = $_SESSION['student_username'] ?? 'Dear';

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - Tekksol Global</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #06BBCC 0%, #0596a3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        
        .logout-card {
            background: white;
            padding: 30px 25px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .logout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #06BBCC, #0596a3);
        }
        
        .logout-icon {
            font-size: 3.5rem;
            color: #06BBCC;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }
        
        .logout-card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .logout-message {
            font-size: 1.1rem;
            margin-bottom: 25px;
            color: #555;
            line-height: 1.5;
        }
        
        .username {
            color: #06BBCC;
            font-weight: 600;
            display: inline-block;
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            margin: 5px 0;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #06BBCC;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        .redirect-text {
            color: #666;
            font-size: 0.95rem;
            margin-top: 10px;
        }
        
        .countdown {
            color: #06BBCC;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .footer-text {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 0.9rem;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 480px) {
            body {
                padding: 15px;
                align-items: flex-start;
                padding-top: 50px;
            }
            
            .logout-container {
                max-width: 100%;
            }
            
            .logout-card {
                padding: 25px 20px;
                border-radius: 15px;
            }
            
            .logout-icon {
                font-size: 3rem;
                margin-bottom: 15px;
            }
            
            .logout-card h2 {
                font-size: 1.3rem;
                margin-bottom: 12px;
            }
            
            .logout-message {
                font-size: 1rem;
                margin-bottom: 20px;
            }
            
            .loading-spinner {
                width: 30px;
                height: 30px;
                margin: 15px auto;
            }
            
            .redirect-text {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 360px) {
            .logout-card {
                padding: 20px 15px;
            }
            
            .logout-icon {
                font-size: 2.5rem;
            }
            
            .logout-card h2 {
                font-size: 1.2rem;
            }
            
            .logout-message {
                font-size: 0.95rem;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .logout-card {
                background: #2d3748;
                color: #e2e8f0;
            }
            
            .logout-card h2 {
                color: #e2e8f0;
            }
            
            .logout-message {
                color: #cbd5e0;
            }
            
            .username {
                background: #4a5568;
                color: #06BBCC;
            }
            
            .redirect-text {
                color: #a0aec0;
            }
            
            .footer-text {
                color: #718096;
                border-top-color: #4a5568;
            }
        }
        
        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .logout-icon {
                animation: none;
            }
            
            .loading-spinner {
                animation-duration: 2s;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2>Logging Out</h2>
            <p class="logout-message">
                Goodbye, <span class="username"><?php echo htmlspecialchars($username); ?></span>!<br>
                You have been successfully logged out.
            </p>
            <div class="loading-spinner"></div>
            <p class="redirect-text">
                Redirecting in <span class="countdown" id="countdown">3</span> seconds...
            </p>
            <div class="footer-text">
                Thank you for using Tekksol Global
            </div>
        </div>
    </div>

    <script>
        // Countdown and redirect
        let seconds = 3;
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            countdownElement.textContent = seconds;
            seconds--;
            
            if (seconds >= 0) {
                setTimeout(updateCountdown, 1000);
            }
        }
        
        // Start countdown immediately
        updateCountdown();
        
        // Redirect after 3 seconds
        setTimeout(function() {
            window.location.href = 'index.html';
        }, 3000);
        
        // Optional: Allow immediate redirect on click/tap
        document.addEventListener('click', function() {
            window.location.href = 'index.html';
        });
        
        // Handle keyboard redirect (Enter key)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                window.location.href = 'index.html';
            }
        });
    </script>
</body>
</html>