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

$product_id = $_GET['product_id'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $seller_id = $decoded->user_id;

    if ($decoded->user_type != 1) {
        $response['status'] = 'false';
        $response['error'] = 'permission failed';
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('select product_id from products where product_id=?');
    $query->bind_param('i', $product_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    if ($num_rows == 0) {
        $response["status"] = "false";
        $response["msg"] = "product doesnt exist";
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('select product_id from products where product_id=? and seller_id=?');
    $query->bind_param('ii', $product_id, $seller_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    if ($num_rows == 0) {
        $response["status"] = "false";
        $response["msg"] = "product doesnt belong to the seller";
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('delete from products
        where product_id=?');
    $query->bind_param('i', $product_id);
    $query->execute();

    $response['status'] = 'true';
    $response['msg'] = 'product deleted successfuly';

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
