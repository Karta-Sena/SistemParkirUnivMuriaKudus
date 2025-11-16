<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "project_rpl";
$port = 3307;

$conn = new mysqli($host, $user, $pass, $db, $port);

$conn->set_charset("utf8mb4");

?>