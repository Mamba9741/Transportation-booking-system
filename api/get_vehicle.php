<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'vehicles' => []];

$type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';

if ($type) {
    $sql = "SELECT * FROM vehicles WHERE type = '$type' AND status = 'available'";
} else {
    $sql = "SELECT * FROM vehicles WHERE status = 'available'";
}

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['vehicles'][] = $row;
    }
    $response['success'] = true;
}

echo json_encode($response);
?>