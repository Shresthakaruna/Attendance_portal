<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP password is empty
$db   = "attendance_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>