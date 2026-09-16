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
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    $today = date('Y-m-d');
    $max_date = date('Y-m-d', strtotime('+1 month'));

    // 1. Check Empty Fields
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        header("Location: ../student_dashboard.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // 2. Check if Start Date is in the Past
    if ($start_date < $today) {
        header("Location: ../student_dashboard.php?error=" . urlencode("Start date cannot be in the past."));
        exit();
    }

    // 3. Check if Start Date is more than 1 month in the Future
    if ($start_date > $max_date) {
        header("Location: ../student_dashboard.php?error=" . urlencode("Leave must be applied within one month from today."));
        exit();
    }

    // 4. Validate: Ensure end date is not before start date
    if (strtotime($end_date) < strtotime($start_date)) {
        header("Location: ../apply_leave.php?error=" . urlencode("End date cannot be earlier than start date."));
        exit();
    }

    // 5. Check Reason Length
    if (strlen($reason) < 10) {
        header("Location: ../student_dashboard.php?error=" . urlencode("Please provide a detailed reason (at least 10 characters)."));
        exit();
    }

    // 6. Prevent Overlapping Leave Requests
    $overlap_stmt = $conn->prepare("
        SELECT leave_id FROM leave_application 
        WHERE student_id = ? 
          AND status != 'Rejected' 
          AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
    ");
    $overlap_stmt->bind_param("issss", $student_id, $end_date, $start_date, $start_date, $end_date);
    $overlap_stmt->execute();
    $overlap_result = $overlap_stmt->get_result();

    if ($overlap_result->num_rows > 0) {
        $overlap_stmt->close();
        header("Location: ../student_dashboard.php?error=" . urlencode("You already have an active or pending leave request overlapping with these dates."));
        exit();
    }
    $overlap_stmt->close();

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