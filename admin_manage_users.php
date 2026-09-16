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

// --- SEARCH PARAMETER ---
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- FETCH DATA ---

// 1. Approved Teachers
$teachers_sql = "SELECT * FROM users WHERE status = 'approved' AND role = 'teacher'";
$teachers_params = [];
$teachers_types = "";

if (!empty($search_term)) {
    $teachers_sql .= " AND (username LIKE ? OR user_id LIKE ? OR subject LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $teachers_params = [$like_term, $like_term, $like_term];
    $teachers_types = "sss";
}

$teachers_sql .= " ORDER BY username ASC";
$teachers_stmt = $conn->prepare($teachers_sql);
if (!empty($teachers_params)) {
    $teachers_stmt->bind_param($teachers_types, ...$teachers_params);
}
$teachers_stmt->execute();
$teachers_result = $teachers_stmt->get_result();

// 2. Approved Students
$students_sql = "SELECT * FROM users WHERE status = 'approved' AND role = 'student'";
$students_params = [];
$students_types = "";

if (!empty($search_term)) {
    $students_sql .= " AND (username LIKE ? OR user_id LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $students_params = [$like_term, $like_term];
    $students_types = "ss";
}

$students_sql .= " ORDER BY username ASC";
$students_stmt = $conn->prepare($students_sql);
if (!empty($students_params)) {
    $students_stmt->bind_param($students_types, ...$students_params);
}
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// 3. All Users (for management table)
$query = "SELECT user_id, username, role, status, subject FROM users ORDER BY user_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css?v=7">
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

        <!-- SEARCH FORM -->
        <div class="dashboard-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3>Search Users</h3>
                <form method="GET" action="admin_manage_users.php" style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" name="search" placeholder="Search name, ID, or subject..." 
                           value="<?php echo htmlspecialchars($search_term); ?>" 
                           style="padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px;">
                    <button type="submit" class="btn-action btn-approve" style="cursor: pointer; border: none;">Search</button>
                    <?php if (!empty($search_term)): ?>
                        <a href="admin_manage_users.php" class="btn-action btn-reject" style="text-decoration: none;">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- APPROVED TEACHERS -->
        <div class="dashboard-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3>Approved Teachers</h3>
                <span class="count-badge"><?php echo $teachers_result->num_rows; ?> Teachers</span>
            </div>
            <div class="table-responsive">
                <table class="decorative-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Subject</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($teachers_result && $teachers_result->num_rows > 0): ?>
                            <?php $serial = 1; while ($user = $teachers_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $serial++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo !empty($user['subject']) ? htmlspecialchars($user['subject']) : '—'; ?></td>
                                    <td class="text-right">
                                        <a href="backend/approve_user.php?id=<?php echo $user['user_id']; ?>&action=delete" 
                                           onclick="return confirm('Permanently delete this teacher account?');" 
                                           class="btn-action btn-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="empty-state">No approved teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- APPROVED STUDENTS -->
        <div class="dashboard-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3>Approved Students</h3>
                <span class="count-badge"><?php echo $students_result->num_rows; ?> Students</span>
            </div>
            <div class="table-responsive">
                <table class="decorative-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students_result && $students_result->num_rows > 0): ?>
                            <?php $serial = 1; while ($user = $students_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $serial++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td class="text-right">
                                        <a href="admin_student_report.php?student_id=<?php echo $user['user_id']; ?>" class="btn-action btn-report">Report</a>
                                        <a href="backend/approve_user.php?id=<?php echo $user['user_id']; ?>&action=delete" 
                                           onclick="return confirm('Permanently delete this student account?');" 
                                           class="btn-action btn-danger">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">No approved students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MANAGE USERS BUTTON -->
        <div style="text-align: center; margin: 16px 0;">
            <button type="button" id="toggleManageUsersBtn" onclick="toggleManageUsers()" class="btn-action btn-approve" style="cursor: pointer; border: none; padding: 8px 16px;">Show Manage Users</button>
        </div>

        <!-- ALL USERS MANAGEMENT TABLE (hidden by default) -->
        <div class="dashboard-card" id="manage-users-table" style="display: none;">
            <div class="card-header">
                <h3>All Registered Users</h3>
                <span class="count-badge"><?php echo $result->num_rows; ?> Total</span>
            </div>
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
                        <?php $serial = 1; while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $serial++; ?></td>
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

    <script>
        function toggleManageUsers() {
            const table = document.getElementById("manage-users-table");
            const btn = document.getElementById("toggleManageUsersBtn");
            const isHidden = table.style.display === "none";
            table.style.display = isHidden ? "" : "none";
            btn.textContent = isHidden ? "Hide Manage Users" : "Show Manage Users";
        }
    </script>
</body>
</html>
