<?php
session_start();
include 'config.php';

// Verify Admin session
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $user_id = intval($_GET['id']);
    $action = strtolower(trim($_GET['action'])); // Normalize string

    if ($action === 'approve') {
        $sql = "UPDATE users SET status = 'approved' WHERE user_id = $user_id";
        $msg = "User approved successfully!";
    } elseif ($action === 'reject') {
        $sql = "UPDATE users SET status = 'rejected' WHERE user_id = $user_id";
        $msg = "User request rejected.";
    } elseif ($action === 'delete') {
        // Permanently delete user account
        $sql = "DELETE FROM users WHERE user_id = $user_id";
        $msg = "User account deleted successfully.";
    } else {
        header("Location: ../admin_dashboard.php?error=" . urlencode("Invalid action"));
        exit();
    }

    if ($conn->query($sql) === TRUE) {
        header("Location: ../admin_dashboard.php?msg=" . urlencode($msg));
        exit();
    } else {
        header("Location: ../admin_dashboard.php?error=" . urlencode("Database error: " . $conn->error));
        exit();
    }
} else {
    header("Location: ../admin_dashboard.php");
    exit();
}
?>