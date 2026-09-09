<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Guard: Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'update') {
        $role = $_POST['role'] ?? 'student';
        $status = $_POST['status'] ?? 'pending';
        $subject = trim($_POST['subject'] ?? '');

        if ($role !== 'teacher') {
            $subject = NULL;
        }

        $stmt = $conn->prepare("UPDATE users SET role = ?, status = ?, subject = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $role, $status, $subject, $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../admin_manage_users.php?msg=updated");
        exit();
    } elseif ($action === 'delete') {
        if ($user_id === intval($_SESSION['user_id'])) {
            header("Location: ../admin_manage_users.php?error=cannot_delete_self");
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: ../admin_manage_users.php?msg=deleted");
        exit();
    }
}

header("Location: ../admin_manage_users.php");
exit();