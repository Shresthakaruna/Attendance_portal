<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Guard: Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all users
$query = "SELECT user_id, username, role, status, subject FROM users ORDER BY user_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="dashboard-container">
        <header class="dashboard-header">
            <h2>User Management</h2>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php 
                    if ($_GET['msg'] === 'updated') echo "User details updated successfully!";
                    elseif ($_GET['msg'] === 'deleted') echo "User deleted successfully!";
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <h3>Registered Users List</h3>
            <div class="table-responsive">
                <table class="decorative-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Assigned Subject</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <form action="backend/manage_user_action.php" method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <td>
                                        <select name="role" class="status-select-custom">
                                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                            <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="subject" value="<?php echo htmlspecialchars($user['subject'] ?? ''); ?>" placeholder="Subject" style="padding: 4px; border-radius: 4px; border: 1px solid #ccc;">
                                    </td>
                                    <td>
                                        <select name="status" class="status-select-custom">
                                            <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $user['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="rejected" <?php echo $user['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" name="action" value="update" class="btn-action btn-approve">Save</button>
                                </form>

                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form action="backend/manage_user_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn-action btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>