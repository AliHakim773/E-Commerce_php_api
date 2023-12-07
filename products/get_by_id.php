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

    die(json_encode($response));
}

$authorization_header = $headers['Authorization'];
$token = trim(str_replace("Bearer", '', $authorization_header));

if (!$token) {
    http_response_code(401);
    $response['status'] = 'false';
    $response['error'] = 'Unauthorized user';

    die(json_encode($response));
}

$product_id = $_GET['product_id'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    $query = $mysqli->prepare('select p.* from products p
        join users u on u.user_id=p.seller_id
        where product_id=?');
    $query->bind_param('i', $product_id);
    $query->execute();

    $product = $query->get_result()->fetch_assoc();

    if ($product == null) {
        $response['status'] = 'false';
        $response['error'] = 'products not found';

        die(json_encode($response));
    };

    $response['status'] = 'true';
    $response['data'] = $product;

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
