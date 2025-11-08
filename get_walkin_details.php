<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    echo '<div class="alert alert-danger">Unauthorized access.</div>';
    exit();
}

// Database configuration
$servername = "bzbnom7tqqucjcivbuxo-mysql.services.clever-cloud.com";
$dbusername = "uwgxq8otzk6mhome";
$dbpassword = "8oQDCXxH6aqYgvkG7g8t";
$db = "bzbnom7tqqucjcivbuxo";

if (isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    
    try {
        $conn = new mysqli($servername, $dbusername, $dbpassword, $db);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $sql = "SELECT * FROM walkin_details WHERE report_id = ? ORDER BY id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $report_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<div class="row">';
            $counter = 1;
            while ($detail = $result->fetch_assoc()) {
                echo '
                <div class="col-md-6 mb-3">
                    <div class="detail-item">
                        <h6>Walk-in Person ' . $counter . '</h6>
                        <div class="row">
                            <div class="col-12">
                                <strong>Name:</strong> ' . htmlspecialchars($detail['name']) . '<br>
                                <strong>Email:</strong> ' . ($detail['email'] ? htmlspecialchars($detail['email']) : 'N/A') . '<br>
                                <strong>Phone:</strong> ' . ($detail['phone_no'] ? htmlspecialchars($detail['phone_no']) : 'N/A') . '<br>
                                <strong>Location:</strong> ' . ($detail['location'] ? htmlspecialchars($detail['location']) : 'N/A') . '<br>
                                <strong>Qualification:</strong> ' . ($detail['qualification'] ? htmlspecialchars($detail['qualification']) : 'N/A') . '<br>
                                <strong>Status:</strong> ' . ($detail['status'] ? htmlspecialchars($detail['status']) : 'N/A') . '
                            </div>
                        </div>
                    </div>
                </div>';
                $counter++;
            }
            echo '</div>';
        } else {
            echo '<div class="alert alert-info">No walk-in details found for this report.</div>';
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
} else {
    echo '<div class="alert alert-danger">No report ID specified.</div>';
}
?>