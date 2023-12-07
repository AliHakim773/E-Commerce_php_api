<?php

include('../vendor/autoload.php');
include('../connection.php');

use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;

$headers = getallheaders();
$response = [];
if (!isset($headers['Authorization']) || empty($headers['Authorization'])) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Unauthorized user';
    echo json_encode($response);
    exit();
}

$authorization_header = $headers['Authorization'];
$token = trim(str_replace("Bearer", '', $authorization_header));

if (!$token) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Unauthorized user';
    exit();
}

$user_id = $_GET['user_id'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    $query = $mysqli->prepare('select * from orders where user_id=?');
    $query->bind_param('i', $user_id);
    $query->execute();

    $array = $query->get_result();
    while ($order = $array->fetch_assoc()) {
        $orders[] = $order;
    }

    $response['status'] = 'true';
    $response['data'] = $orders;

    echo json_encode($response);
} catch (ExpiredException $e) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'token expired';
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Invalid token';
    echo json_encode($response);
}
