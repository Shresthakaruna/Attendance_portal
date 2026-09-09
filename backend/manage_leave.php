<?php
session_start();
require_once 'config.php';

// Ensure user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $leave_id = intval($_GET['id']);
    $action = $_GET['action'];

    // Map action to database status
    if ($action === 'approve') {
        $status = 'Approved';
    } elseif ($action === 'reject') {
        $status = 'Rejected';
    } else {
        header("Location: ../admin_dashboard.php");
        exit();
    }

    // Update leave status in leave_application table
    $stmt = $conn->prepare("UPDATE leave_application SET status = ? WHERE leave_id = ?");
    $stmt->bind_param("si", $status, $leave_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../admin_dashboard.php?msg=leave_updated");
exit();