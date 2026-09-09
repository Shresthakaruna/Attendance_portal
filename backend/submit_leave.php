<?php
session_start();
include 'config.php';

// Ensure user is logged in as student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    // Validation: Ensure end date is not before start date
    if (strtotime($end_date) < strtotime($start_date)) {
        header("Location: ../apply_leave.php?error=" . urlencode("End date cannot be earlier than start date."));
        exit();
    }

    // Insert leave request into leave_application table using prepared statement
    $stmt = $conn->prepare("INSERT INTO leave_application (student_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $student_id, $start_date, $end_date, $reason);

    if ($stmt->execute()) {
        header("Location: ../student_dashboard.php?msg=" . urlencode("Leave application submitted successfully. Pending admin approval."));
        exit();
    } else {
        header("Location: ../student_dashboard.php?error=" . urlencode("Error submitting request: " . $conn->error));
        exit();
    }

    $stmt->close();
} else {
    header("Location: ../student_dashboard.php");
    exit();
}
?>