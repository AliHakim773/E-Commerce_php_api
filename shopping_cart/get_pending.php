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

    die(json_encode($response));;
}

$authorization_header = $headers['Authorization'];
$token = trim(str_replace("Bearer", '', $authorization_header));

if (!$token) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Unauthorized user';

    die(json_encode($response));
}

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $user_id = $decoded->user_id;

    $query = $mysqli->prepare("select * from shopping_cart where user_id=? and status='pending'");
    $query->bind_param('i', $user_id);
    $query->execute();

    $cart = $query->get_result()->fetch_assoc();

    $response['status'] = 'true';
    $response['data'] = $cart;

    echo json_encode($response);
} catch (ExpiredException $e) {

    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'token expired';
    echo json_encode($response);
} catch (Exception $e) {

    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = $e->getMessage();
    echo json_encode($response);
}
