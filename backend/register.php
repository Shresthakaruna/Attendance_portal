<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate password requirements
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (strlen($password) > 12) {
        $errors[] = "Password must be at most 12 characters long";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one digit";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one capital letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one small letter";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    if (!empty($errors)) {
        header("Location: ../register.php?error=" . urlencode(implode(" ", $errors)));
        exit();
    }

    // Capture subject if role is teacher; otherwise set to NULL
    $subject = ($role === 'teacher' && !empty($_POST['subject'])) ? $_POST['subject'] : null;

    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username='$username'";
    $check_result = $conn->query($check_sql);

    if ($check_result && $check_result->num_rows > 0) {
        header("Location: ../register.php?error=" . urlencode("Username already taken"));
        exit();
    } else {
        // Hash password for login security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update subject column for teachers
        if ($subject) {
            $sql = "INSERT INTO users (username, password, role, subject, status) 
                    VALUES ('$username', '$hashed_password', '$role', '$subject', 'pending')";
        } else {
            $sql = "INSERT INTO users (username, password, role, status) 
                    VALUES ('$username', '$hashed_password', '$role', 'pending')";
        }

        if ($conn->query($sql) === TRUE) {
            header("Location: ../index.php?success=" . urlencode("Registration successful! Your account is pending admin approval."));
            exit();
        } else {
            header("Location: ../register.php?error=" . urlencode("Error creating account"));
            exit();
        }
    }
} else {
    header("Location: ../register.php");
    exit();
}
?>