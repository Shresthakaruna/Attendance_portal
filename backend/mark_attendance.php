<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Role Guard: Teacher check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $today = date('Y-m-d');
    $subject = $_SESSION['subject'] ?? '';

    if (empty($subject)) {
        // Fallback: Fetch assigned subject if not in session
        $teacher_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT subject FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $subject = $result['subject'] ?? '';
        $stmt->close();
    }

    // 1. Check if attendance for today and subject has already been submitted
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM attendance WHERE date = ? AND subject = ?");
    $check_stmt->bind_param("ss", $today, $subject);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($check_result['count'] > 0) {
        // Attendance already submitted for today
        header("Location: ../teacher_dashboard.php?msg=already_submitted");
        exit();
    }

    // 2. Prepare Insert Statement without teacher_id
    $insert_stmt = $conn->prepare("INSERT INTO attendance (student_id, date, subject, status) VALUES (?, ?, ?, ?)");

    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_id = intval($student_id);
        
        // Clean/validate status input
        $valid_statuses = ['Present', 'Absent', 'Late'];
        if (!in_array($status, $valid_statuses)) {
            $status = 'Absent';
        }

        $insert_stmt->bind_param("isss", $student_id, $today, $subject, $status);
        $insert_stmt->execute();
    }

    $insert_stmt->close();

    // Redirect back to dashboard with success message
    header("Location: ../teacher_dashboard.php?msg=attendance_saved");
    exit();
} else {
    header("Location: ../teacher_dashboard.php");
    exit();
}