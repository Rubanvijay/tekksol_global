<?php
$host = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$port = "3306";
$user = "uwgxq8otzk6mhome";
$pass = "8oQDCXxH6aqYgvkG7g8t"; // copy the password from Clever Cloud
$db   = "bzbnom7tqqucjcivbuxo";

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Database connection successful!";
?>
