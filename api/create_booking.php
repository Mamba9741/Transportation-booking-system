<?php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

$response = ['success' => false, 'message' => '', 'booking_id' => null];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $vehicle_id = intval($_POST['vehicle_id']);
    $pickup_location = $conn->real_escape_string($_POST['pickup_location']);
    $dropoff_location = $conn->real_escape_string($_POST['dropoff_location']);
    $pickup_date = $conn->real_escape_string($_POST['pickup_date']);
    $pickup_time = $conn->real_escape_string($_POST['pickup_time']);
    $dropoff_date = $conn->real_escape_string($_POST['dropoff_date']);
    $dropoff_time = $conn->real_escape_string($_POST['dropoff_time']);
    $booking_type = $conn->real_escape_string($_POST['booking_type']);
    $distance_km = isset($_POST['distance_km']) ? intval($_POST['distance_km']) : null;
    $duration_hours = isset($_POST['duration_hours']) ? intval($_POST['duration_hours']) : null;
    $passengers = intval($_POST['passengers']);
    $total_price = floatval($_POST['total_price']);
    $special_requests = $conn->real_escape_string($_POST['special_requests']);

    // Validate vehicle exists
    $vehicle_check = $conn->query("SELECT id FROM vehicles WHERE id = $vehicle_id");
    if ($vehicle_check->num_rows == 0) {
        $response['message'] = 'Invalid vehicle';
        echo json_encode($response);
        exit;
    }

    $sql = "INSERT INTO bookings 
            (user_id, vehicle_id, pickup_location, dropoff_location, pickup_date, pickup_time, 
             dropoff_date, dropoff_time, booking_type, distance_km, duration_hours, passengers, 
             total_price, special_requests, status) 
            VALUES ($user_id, $vehicle_id, '$pickup_location', '$dropoff_location', '$pickup_date', 
                    '$pickup_time', '$dropoff_date', '$dropoff_time', '$booking_type', $distance_km, 
                    $duration_hours, $passengers, $total_price, '$special_requests', 'pending')";

    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Booking created successfully';
        $response['booking_id'] = $conn->insert_id;
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}

echo json_encode($response);
?>

