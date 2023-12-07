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
$token = trim(str_replace("Bearer", '', $authorizationHeader));

if (!$token) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Unauthorized user';
    exit();
}

$seller_id = $_POST['seller_id'];
$name = $_POST['name'];
$price = $_POST['price'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    if ($decoded->usertype != 1) {
        $response['status'] = 'false';
        $response['error'] = 'permission failed';
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('insert into products(seller_id, name, price) 
        values(?,?,?)');
    $query->bind_param('isi', $seller_id, $name, $price);
    $query->execute();

    $response["status"] = "true";
    $response["msg"] = "product added successfuly";
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
