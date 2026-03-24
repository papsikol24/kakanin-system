<?php
session_start();

$response = ['valid' => false];

if (isset($_SESSION['user_id']) && isset($_SESSION['user_session_id'])) {
    if ($_SESSION['user_session_id'] === session_id()) {
        $response['valid'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>