<?php

include('../connection.php');

$username = $_POST['username'];
$user_type = $_POST['user_type'];
$password = $_POST['password'];

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$response = [];
try {
    $query = $mysqli->prepare('select username from users where username=?');
    $query->bind_param('s', $username);
    $query->execute();

    $query->store_result();
    $num_rows = $query->num_rows();

    if ($num_rows != 0) {
        $response["status"] = "false";
        $response["error"] = "username already exist";

        die(json_encode($response));
    }

    $query = $mysqli->prepare('insert into users(username, password, user_type) 
        values(?,?,?)');
    $query->bind_param('ssi', $username, $hashed_password, $user_type);
    $query->execute();

    $response["status"] = "true";
    $response["msg"] = "register completed";

    echo json_encode($response);
} catch (Exception $e) {
    $response["status"] = "false";
    $response["error"] = $e->getMessage();

    echo json_encode($response);
}
