<?php
session_start(); // Add this at the top

$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "Today Date and Time is: " . date('d-m-Y H:i:s') . "<br>";
    
    // Get and sanitize input
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
} else {
    echo "Please submit the form.";
    exit();
}

$conn = new mysqli($servername, $dbusername, $dbpassword, $db);
if($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
} else {
    echo "Connection Success <br>";
}

// Better approach: Check only the specific user
$sql = "SELECT username, password FROM students WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "DB Username: ". $row["username"]."<br>";
    echo "DB Password: " . $row["password"]."<br>";
    
    if($username == $row["username"] && $password == $row["password"]) {
        // Set session variables
        $_SESSION['student_username'] = $username;
        $_SESSION['student_logged_in'] = true;
        
        echo "Login successful! Redirecting...<br>";
        header("Location: student-dashboard.php");
        exit();
    } else {
        echo "Password incorrect!<br>";
        echo "<script>
            alert('Invalid password!');
            setTimeout(function() {
                window.location.href = 'student-login.html';
            }, 1000);
        </script>";
    }
} else {
    echo "User not found!<br>";
    echo "<script>
        alert('Username not found!');
        setTimeout(function() {
            window.location.href = 'student-login.html';
        }, 1000);
    </script>";
}

$stmt->close();
$conn->close();
?>