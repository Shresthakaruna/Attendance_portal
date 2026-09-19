<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Verify Admin Session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

// --- DATA RETRIEVAL (Prepared Statements) ---
$today = date('Y-m-d');

// 1. Overview Metrics (KPI Cards)
$total_pending  = $conn->query("SELECT COUNT(*) AS count FROM users WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
$total_teachers = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'teacher' AND status = 'approved'")->fetch_assoc()['count'] ?? 0;
$total_students = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role = 'student' AND status = 'approved'")->fetch_assoc()['count'] ?? 0;
$total_leaves   = $conn->query("SELECT COUNT(*) AS count FROM leave_application WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;

// 2. Fetch Pending Registration Requests
$pending_stmt = $conn->prepare("SELECT * FROM users WHERE status = 'pending' ORDER BY user_id DESC");
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

// 3. Fetch Student Leave Requests
$leave_stmt = $conn->prepare("
    SELECT l.*, u.username 
    FROM leave_application l 
    JOIN users u ON l.student_id = u.user_id 
    ORDER BY l.leave_id DESC
");
$leave_stmt->execute();
$leave_result = $leave_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Smart Attendance Portal</title>
  <link rel="stylesheet" href="css/style.css?v=13">
</head>
<body>

  <?php include 'partials/navbar.php'; ?>

  <div class="dashboard-container">

    <!-- PAGE HEADER -->
    <header class="dashboard-header">
      <div class="header-title">
        <h2>Admin Overview</h2>
        <p class="subtitle">System Administration & Portal Control Center</p>
      </div>
    </header>

    <!-- ALERT / FEEDBACK MESSAGES -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- SUMMARY METRICS AS CARDS -->
    <div class="metrics-cards-container">
      
      <div class="metric-card border-warning">
        <span class="metric-label">Pending Accounts</span>
        <div class="metric-value"><?php echo $total_pending; ?></div>
        <span class="metric-subtext">Awaiting approval</span>
      </div>

      <div class="metric-card border-primary">
        <span class="metric-label">Active Teachers</span>
        <div class="metric-value"><?php echo $total_teachers; ?></div>
        <span class="metric-subtext">Approved faculty</span>
      </div>

      <div class="metric-card border-success">
        <span class="metric-label">Active Students</span>
        <div class="metric-value"><?php echo $total_students; ?></div>
        <span class="metric-subtext">Registered learners</span>
      </div>

      <div class="metric-card border-danger">
        <span class="metric-label">Pending Leaves</span>
        <div class="metric-value"><?php echo $total_leaves; ?></div>
        <span class="metric-subtext">Requests to review</span>
      </div>

    </div>

    <!-- SECTION 1: PENDING REGISTRATION REQUESTS -->
    <section class="dashboard-card">
      <div class="card-header">
        <h3>Pending Account Registration Requests</h3>
        <span class="count-badge"><?php echo $pending_result->num_rows; ?> Requests</span>
      </div>
      <div class="table-responsive">
        <table class="decorative-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Role</th>
              <th>Assigned Subject</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($pending_result && $pending_result->num_rows > 0): ?>
              <?php while ($row = $pending_result->fetch_assoc()): ?>
                <tr>
                  <td><strong>#<?php echo $row['user_id']; ?></strong></td>
                  <td><?php echo htmlspecialchars($row['username']); ?></td>
                  <td><span class="badge-role"><?php echo ucfirst($row['role']); ?></span></td>
                  <td><?php echo !empty($row['subject']) ? htmlspecialchars($row['subject']) : '—'; ?></td>
                  <td class="text-right">
                    <div class="action-stack">
                      <a href="backend/approve_user.php?id=<?php echo $row['user_id']; ?>&action=approve" class="btn-action btn-approve">
                        Approve
                      </a>
                      <a href="backend/approve_user.php?id=<?php echo $row['user_id']; ?>&action=reject" 
                         onclick="return confirm('Are you sure you want to reject this registration request?');" 
                         class="btn-action btn-reject">
                        Reject
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="empty-state">No pending account registration requests found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- SECTION 2: LEAVE REQUESTS -->
    <section class="dashboard-card">
      <div class="card-header">
        <h3>Student Leave Applications</h3>
        <span class="count-badge"><?php echo $leave_result->num_rows; ?> Requests</span>
      </div>
      <div class="leave-filters" style="padding: 0 16px 12px; display: flex; gap: 20px; align-items: center;">
        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
          <input type="checkbox" id="showExpired"> <span>Show Expired</span>
        </label>
        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
          <input type="checkbox" id="showProcessed"> <span>Show Processed</span>
        </label>
      </div>
      <div class="table-responsive">
        <table class="decorative-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Student Name</th>
              <th>Duration</th>
              <th>Reason</th>
              <th>Status</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="leaveTableBody">
            <?php if ($leave_result && $leave_result->num_rows > 0): ?>
              <?php while ($leave = $leave_result->fetch_assoc()): ?>
                <?php
                $is_expired = ($leave['end_date'] < $today);
                $leave_class = $is_expired ? 'expired' : (($leave['status'] !== 'Pending') ? 'processed' : 'pending');
                ?>
                <tr data-status="<?php echo $leave_class; ?>">
                  <td><strong>#<?php echo $leave['leave_id']; ?></strong></td>
                  <td><strong><?php echo htmlspecialchars($leave['username']); ?></strong></td>
                  <td>
                    <?php echo date('M d', strtotime($leave['start_date'])); ?> - 
                    <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                  </td>
                  <td><span class="reason-text"><?php echo htmlspecialchars($leave['reason']); ?></span></td>
                  <td>
                    <?php
                    $status = $leave['status'];

                    if ($is_expired) {
                        $display_status = 'Expired';
                        $status_class = 'badge-expired';
                    } else {
                        $display_status = $status;
                        $status_class = ($status === 'Approved') ? 'badge-present' : (($status === 'Rejected') ? 'badge-absent' : 'badge-late');
                    }
                    ?>
                    <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($display_status); ?></span>
                  </td>
                  <td class="text-right">
                    <?php if ($leave['status'] === 'Pending' && !$is_expired): ?>
                      <div class="action-stack">
                        <a href="backend/manage_leave.php?id=<?php echo $leave['leave_id']; ?>&action=approve" class="btn-action btn-approve">Approve</a>
                        <a href="backend/manage_leave.php?id=<?php echo $leave['leave_id']; ?>&action=reject" 
                           onclick="return confirm('Reject this leave request?');" 
                           class="btn-action btn-reject">Reject</a>
                      </div>
                    <?php elseif ($is_expired): ?>
                      <span class="text-muted">Expired</span>
                    <?php else: ?>
                      <span class="text-muted">Processed</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="empty-state">No student leave requests submitted yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- SECTION 3: USERS MANAGEMENT -->
    <section class="dashboard-card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h3>Users</h3>
        <div style="display: flex; gap: 8px; align-items: center;">
          <a href="admin_manage_users.php" class="btn-action btn-approve" style="text-decoration: none; padding: 8px 16px;">Go to Users</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="decorative-table">
          <thead>
            <tr>
              <th>Metric</th>
              <th>Count</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Active Teachers</strong></td>
              <td><span class="badge-role">Approved</span> <?php echo $total_teachers; ?></td>
            </tr>
            <tr>
              <td><strong>Active Students</strong></td>
              <td><span class="badge-role">Approved</span> <?php echo $total_students; ?></td>
            </tr>
            <tr>
              <td><strong>Pending Accounts</strong></td>
              <td><span class="badge-role">Pending</span> <?php echo $total_pending; ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </div>

  <script>
    function applyLeaveFilters() {
      const showExpired = document.getElementById('showExpired').checked;
      const showProcessed = document.getElementById('showProcessed').checked;
      document.querySelectorAll('#leaveTableBody tr').forEach(row => {
        if (!row.dataset.status) return;
        const hidden = (row.dataset.status === 'expired' && !showExpired) ||
                       (row.dataset.status === 'processed' && !showProcessed);
        row.style.display = hidden ? 'none' : '';
      });
    }

    document.getElementById('showExpired').addEventListener('change', applyLeaveFilters);
    document.getElementById('showProcessed').addEventListener('change', applyLeaveFilters);
    applyLeaveFilters();
  </script>

</body>
</html>