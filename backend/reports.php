<?php
session_start();
include 'config.php';

if ($_SESSION['role'] == 'teacher') {
    $subject_id = $_SESSION['subject_id'];
    $sql = "SELECT * FROM attendance WHERE subject_id='$subject_id'";
} else {
    $student_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM attendance WHERE student_id='$student_id'";
}

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo $row['date'] . " - " . $row['status'] . "<br>";
}
?>
