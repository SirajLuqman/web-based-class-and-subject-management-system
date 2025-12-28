<?php
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db_name = "class_scheduler_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
