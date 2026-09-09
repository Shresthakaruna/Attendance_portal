<?php
session_start();
require_once 'config.php';

// Verify student login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['user_id'];
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');

    $today = date('Y-m-d');

    // Rule 1: Empty Fields Check
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        header("Location: ../student_leave.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // Rule 2: Start Date Cannot Be in the Past
    if ($start_date < $today) {
        header("Location: ../student_leave.php?error=" . urlencode("Start date cannot be in the past."));
        exit();
    }

    // Rule 3: End Date Cannot Be Before Start Date
    if ($end_date < $start_date) {
        header("Location: ../student_leave.php?error=" . urlencode("End date must be on or after the start date."));
        exit();
    }

    // Rule 4: Reason Minimum Length Check
    if (strlen($reason) < 10) {
        header("Location: ../student_leave.php?error=" . urlencode("Please provide a detailed reason (at least 10 characters)."));
        exit();
    }

    // Rule 5: Prevent Overlapping Leave Dates
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
        header("Location: ../student_leave.php?error=" . urlencode("You already have an active or pending leave request overlapping with these dates."));
        exit();
    }
    $overlap_stmt->close();

    // Insertion if all validations pass
    $stmt = $conn->prepare("INSERT INTO leave_application (student_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $student_id, $start_date, $end_date, $reason);

    if ($stmt->execute()) {
        header("Location: ../student_leave.php?msg=" . urlencode("Leave application submitted successfully."));
    } else {
        header("Location: ../student_leave.php?error=" . urlencode("Failed to submit request. Please try again."));
    }

    $stmt->close();
    exit();
}