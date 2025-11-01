<?php
$host = "tekksol_global.clever-cloud.com";
$port = "3306";
$user = "ruban";
$pass = "ruban123";
$db   = "tekksol_global";

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Database connection successful!";
?>
