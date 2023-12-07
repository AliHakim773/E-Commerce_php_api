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

$product_id = $_POST['product_id'];
$amount = $_POST['amount'];

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $user_id = $decoded->user_id;

    $query = $mysqli->prepare('select product_id from products where product_id=?');
    $query->bind_param('i', $product_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows;

    if ($num_rows == 0) {
        $response["status"] = "false";
        $response["error"] = "product doesnt exist";

        die(json_encode($response));
    }

    $query = $mysqli->prepare('select user_id from users where user_id=?');
    $query->bind_param('i', $user_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows;

    if ($num_rows == 0) {
        $response["status"] = "false";
        $response["error"] = "user doesnt exist";

        die(json_encode($response));
    }

    $query = $mysqli->prepare("select cart_id from shopping_carts where user_id=? and status='pending'");
    $query->bind_param('i', $user_id);
    $query->execute();

    $query->bind_result($cart_id);
    $query->fetch();
    $query->close();

    if ($cart_id == null) {
        $today = date('Y-m-d');
        $query = $mysqli->prepare("insert into shopping_carts(user_id, created_at) 
            values(?,?)");
        $query->bind_param('is', $user_id, $today);
        $query->execute();

        $cart_id = $mysqli->insert_id;
    }

    $query = $mysqli->prepare('insert into orders(user_id, product_id, cart_id, amount) values(?,?,?,?)');
    $query->bind_param('iiii', $user_id, $product_id, $cart_id, $amount);
    $query->execute();

    $response["status"] = "true";
    $response["msg"] = "oreder added successfuly";
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
