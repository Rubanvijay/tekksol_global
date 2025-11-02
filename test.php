<?php
session_start();

$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "Please submit the form.";
    exit();
}

$username = $_POST['username'];
$password = $_POST['password'];

$conn = new mysqli($servername, $dbusername, $dbpassword, $db);
if($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Check user
$sql = "SELECT username, password FROM students WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    if($username == $row["username"] && $password == $row["password"]) {
        $_SESSION['student_username'] = $username;
        $_SESSION['student_logged_in'] = true;

        // redirect safely
        header("Location: student-dashboard.php");
        exit();
    } else {
        echo "<script>
            alert('Invalid password!');
            window.location.href='student-login.html';
        </script>";
        exit();
    }
} else {
    echo "<script>
        alert('Username not found!');
        window.location.href='student-login.html';
    </script>";
    exit();
}

$stmt->close();
$conn->close();
?>
