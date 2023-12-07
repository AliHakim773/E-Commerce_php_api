<?php

$host = "localhost";
$db_user = 'root';
$db_passwod = null;
$db_name = 'e-com-db';

$mysqli = new mysqli($host, $db_user, $db_passwod, $db_name);

if ($mysqli->connect_error) {
    die("" . $mysqli->connect_error);
    exit();
}

header('Access-Control-Allow-Origin:*');
