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

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $user_id = $decoded->user_id;

    $query = $mysqli->prepare("select * from shopping_cart where user_id=? and status='completed'");
    $query->bind_param('i', $user_id);
    $query->execute();

    $array = $query->get_result();
    while ($cart = $array->fetch_assoc()) {
        $carts[] = $cart;
    }

    $response['status'] = 'true';
    $response['data'] = $carts;

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
