<?php
session_start();
include 'config.php';

// Ensure the request came from a form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            // CHECK STATUS: Only allow approved users (or admins)
            if ($user['status'] !== 'approved' && $user['role'] !== 'admin') {
                header("Location: ../index.php?error=" . urlencode("Your account is pending admin approval."));
                exit();
            }

           // Inside backend/login.php after verifying password and status = 'approved':

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

if ($user['role'] === 'student') {
    // MUST use ../ to step out of backend/ into root directory
    header("Location: ../student_dashboard.php");
    exit();
} elseif ($user['role'] === 'teacher') {
    header("Location: ../teacher_dashboard.php");
    exit();
} elseif ($user['role'] === 'admin') {
    header("Location: ../admin_dashboard.php");
    exit();
}

        } else {
            header("Location: ../index.php?error=" . urlencode("Invalid password"));
            exit();
        }
    } else {
        header("Location: ../index.php?error=" . urlencode("User not found"));
        exit();
    }

} else {
    // Redirect if accessed directly without POST
    header("Location: ../index.php");
    exit();
}
?>