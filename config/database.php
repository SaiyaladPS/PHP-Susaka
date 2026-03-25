<?php
$host = "db";
$user = "root";
$pass = "96778932";
$db = "not_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>