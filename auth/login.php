<?php

include('../vendor/autoload.php');
include('../connection.php');

use Firebase\JWT\JWT;

$username = $_POST['username'];
$password = $_POST['password'];

$response = [];
try {
    $query = $mysqli->prepare('select user_id, username, password, user_type from users where username=?');
    $query->bind_param('s', $username);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    if ($num_rows == 0) {
        $response['status'] = 'false';
        $response['error'] = 'user not found';

        die(json_encode($response));
    }

    $query->bind_result($id, $name, $hashed_password, $user_type);
    $query->fetch();

    if (password_verify($password, $hashed_password)) {
        $key = 'ez4me';
        $payload = [
            'user_id' => $id,
            'username' => $name,
            'user_type' => $user_type,
            'exp' => time() + 3600
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');

        $response['status'] = 'true';
        $response['msg'] = 'loged in';
        $response['jwt'] = $jwt;
        echo json_encode($response);
    }
} catch (Exception $e) {
    $response["status"] = "false";
    $response["error"] = $e->getMessage();

    echo json_encode($response);
}
