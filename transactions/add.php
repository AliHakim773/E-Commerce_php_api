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

$today = date('Y-m-d');

try {
    $key = "ez4me";
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $user_id = $decoded->user_id;

    if ($decoded->user_type != 1) {
        $response['status'] = 'false';
        $response['error'] = 'permission failed';
        echo json_encode($response);
        exit();
    }

    $query = $mysqli->prepare('select user_id from users where user_id=?');
    $query->bind_param('i', $user_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    if ($num_rows == 0) {
        $response['status'] = 'false';
        $response['error'] = 'user not found';

        die(json_encode($response));
    }
    $query->close();

    $query = $mysqli->prepare("select cart_id from shopping_carts where user_id=? and status='pending'");
    $query->bind_param('i', $user_id);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    $query->bind_result($cart_id);
    $query->fetch();

    if ($num_rows == 0) {
        $response['status'] = 'false';
        $response['error'] = 'no open carts found';

        die(json_encode($response));
    }
    $query->close();

    $query = $mysqli->prepare('insert into transactions(user_id, cart_id, date) values(?,?,?)');
    $query->bind_param('iss', $user_id, $cart_id, $today);
    $query->execute();

    $query = $mysqli->prepare('update shopping_carts set status="completed" where cart_id=?');
    $query->bind_param('i', $cart_id);
    $query->execute();

    $response["status"] = "true";
    $response["msg"] = "transaction complete";
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
