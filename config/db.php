<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "tutor_booking";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

ipidi pannatha vithu