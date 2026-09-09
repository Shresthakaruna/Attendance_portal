<?php
session_start();
include 'config.php';

if ($_SESSION['role'] != 'teacher') {
    die("Unauthorized access");
}

$leave_id = $_POST['leave_id'];
$status = $_POST['status']; // Approved or Rejected

$sql = "UPDATE leave_applications SET status='$status' WHERE leave_id='$leave_id'";

if ($conn->query($sql) === TRUE) {
    echo "Leave status updated.";
} else {
    echo "Error: " . $conn->error;
}
?>
