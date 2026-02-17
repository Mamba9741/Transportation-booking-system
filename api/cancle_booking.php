<?php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = intval($_POST['booking_id']);
    $user_id = $_SESSION['user_id'];

    // Verify booking belongs to user
    $check = $conn->query("SELECT id FROM bookings WHERE id = $booking_id AND user_id = $user_id");
    if ($check->num_rows == 0) {
        $response['message'] = 'Booking not found';
        echo json_encode($response);
        exit;
    }

    $sql = "UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id";
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
        $response['message'] = 'Booking cancelled successfully';
    } else {
        $response['message'] = 'Error: ' . $conn->error;
    }
}

echo json_encode($response);
?>

