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

$product_id = $_POST['product_id'];
$seller_id = $_POST['seller_id'];
$name = $_POST['name'];
$price = $_POST['price'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

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
    $num_rows = $query->num_rows;

    if ($num_rows == 0) {
        $response["status"] = "false";
        $response["msg"] = "product doesnt exist";
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('select user_type from users where user_id=?');
    $query->bind_param('i', $seller_id);
    $query->execute();

    $query->bind_result($user_type);
    $query->fetch();

    if ($user_type != 1) {
        $response['status'] = 'false';
        $response['error'] = 'user is not a seller';
        echo json_encode($response);
        exit();
    }

    $query->close();
    $query = $mysqli->prepare('update products 
    set seller_id=?, name=?, price=?
    where product_id=?');
    $query->bind_param('isii', $seller_id, $name, $price, $product_id);
    $query->execute();

    $response["status"] = "true";
    $response["msg"] = "product edited successfuly";
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
