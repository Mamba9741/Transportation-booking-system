<?php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

$response = ['success' => false, 'bookings' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.*, v.type, v.name, v.image_url 
        FROM bookings b 
        JOIN vehicles v ON b.vehicle_id = v.id 
        WHERE b.user_id = $user_id 
        ORDER BY b.created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['bookings'][] = $row;
    }
    $response['success'] = true;
}

echo json_encode($response);
?>
