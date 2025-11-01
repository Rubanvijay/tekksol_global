<?php
// Database connection
$conn = new mysqli("localhost", "root", "ruban", "tekksol_global");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$officeLat = 12.811393;
$officeLon = 80.227807;
$maxDistance = 50;

function getDistance($lat1, $lon1, $lat2, $lon2) {
    $R = 6371e3;
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $deltaPhi = deg2rad($lat2 - $lat1);
    $deltaLambda = deg2rad($lon2 - $lon1);
    $a = sin($deltaPhi/2) ** 2 +
         cos($phi1) * cos($phi2) * sin($deltaLambda/2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $lat = floatval($_POST['latitude'] ?? 0);
    $lon = floatval($_POST['longitude'] ?? 0);

    $distance = getDistance($lat, $lon, $officeLat, $officeLon);

    if ($distance <= $maxDistance) {
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $employee_id, $lat, $lon);
        if ($stmt->execute()) {
            echo "✅ Attendance marked successfully (" . round($distance,2) . " m)";
        } else {
            echo "❌ Database error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "🚫 You are too far away (" . round($distance,2) . " m)";
    }

    $conn->close();
} else {
    echo "Invalid request";
}
?>
