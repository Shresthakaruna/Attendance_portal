<?php
require_once 'backend/config.php';

// Define your desired password here
$new_password = 'adminpassword'; 
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Check if admin exists
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin password
    $update_stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin', status = 'approved' WHERE username = 'admin'");
    $update_stmt->bind_param("s", $hashed_password);
    if ($update_stmt->execute()) {
        echo "<h2 style='color: green;'>Admin password reset successfully!</h2>";
        echo "<p>Username: <strong>admin</strong><br>Password: <strong>{$new_password}</strong></p>";
    }
    $update_stmt->close();
} else {
    // Create new admin account if missing
    $insert_stmt = $conn->prepare("INSERT INTO users (username, password, role, status) VALUES ('admin', ?, 'admin', 'approved')");
    $insert_stmt->bind_param("s", $hashed_password);
    if ($insert_stmt->execute()) {
        echo "<h2 style='color: green;'>Admin account created successfully!</h2>";
        echo "<p>Username: <strong>admin</strong><br>Password: <strong>{$new_password}</strong></p>";
    }
    $insert_stmt->close();
}
$stmt->close();
?>