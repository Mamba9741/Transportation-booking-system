<?php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT id, name, password FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $response['success'] = true;
            $response['message'] = 'Login successful';
            $response['user_id'] = $row['id'];
        } else {
            $response['message'] = 'Invalid password';
        }
    } else {
        $response['message'] = 'User not found';
    }
}

echo json_encode($response);
?>