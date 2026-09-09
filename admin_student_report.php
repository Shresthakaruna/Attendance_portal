<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Role Guard: Ensure user is logged in as Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 1. Fetch all approved students for the selector dropdown
$students_query = $conn->query("
    SELECT user_id, username 
    FROM users 
    WHERE role = 'student' AND status = 'approved' 
    ORDER BY username ASC
");

$selected_student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

$student_info = null;
$attendance_logs = [];
$total_records = 0;
$present_count = 0;
$absent_count = 0;
$late_count = 0;
$overall_pct = 0;

// 2. If a student is selected, fetch their detailed report
if ($selected_student_id > 0) {
    // Get student details
    $stmt_user = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ? AND role = 'student'");
    $stmt_user->bind_param("i", $selected_student_id);
    $stmt_user->execute();
    $student_info = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();

    if ($student_info) {
        // Fetch detailed attendance history with teacher info
        $stmt_att = $conn->prepare("
            SELECT date, subject, status
            FROM attendance
            WHERE student_id = ?
            ORDER BY date DESC, subject ASC
        ");
        $stmt_att->bind_param("i", $selected_student_id);
        $stmt_att->execute();
        $result = $stmt_att->get_result();

        while ($row = $result->fetch_assoc()) {
            $attendance_logs[] = $row;
            $total_records++;
            if ($row['status'] === 'Present') {
                $present_count++;
            } elseif ($row['status'] === 'Absent') {
                $absent_count++;
            } elseif ($row['status'] === 'Late') {
                $late_count++;
            }
        }
        $stmt_att->close();

        // Calculate attendance rate (Present + Late counted as attended)
        $attended = $present_count + $late_count;
        $overall_pct = ($total_records > 0) ? round(($attended / $total_records) * 100, 1) : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Attendance Report - Admin</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    .btn-print {
      background: #0d6efd;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
    }
    .btn-print:hover { background: #0b5ed7; }
    @media print {
      .btn-print, .navbar, .filter-form, .back-link { display: none !important; }
      .dashboard-container { padding: 0; }
      .report-card { display: none !important; }
      body { background: #fff !important; }
    }
  </style>
</head>
<body>
  <?php include 'partials/navbar.php'; ?>

  <div class="dashboard-container">
    <header class="dashboard-header">
      <h2>Attendance Reports</h2>
    </header>

    <!-- Student Selector Card -->
    <div class="dashboard-card report-card">
      <h3>🔍 View Student Attendance</h3>
      <form action="admin_student_report.php" method="GET" class="filter-form">
        <select name="student_id" class="filter-select" required>
          <option value="">-- Select a Student --</option>
          <?php while ($s = $students_query->fetch_assoc()): ?>
            <option value="<?php echo $s['user_id']; ?>" <?php echo ($selected_student_id == $s['user_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($s['username']); ?> (ID: #<?php echo $s['user_id']; ?>)
            </option>
          <?php endwhile; ?>
        </select>
        <button type="submit" class="btn-primary">Generate Report</button>
      </form>
    </div>

    <?php if ($selected_student_id > 0 && $student_info): ?>
      <!-- Student Stats Summary Card -->
      <div class="dashboard-card">
        <h3>📊 Summary for <?php echo htmlspecialchars($student_info['username']); ?></h3>
        <button onclick="window.print()" class="btn-print" style="float:right; margin-top:-5px;">🖨️ Print Preview</button>
        
        <div class="stats-grid">
          <div class="stat-box">
            <h5>Attendance Rate</h5>
            <div class="number text-primary-blue"><?php echo $overall_pct; ?>%</div>
          </div>
          <div class="stat-box">
            <h5>Total Lectures</h5>
            <div class="number"><?php echo $total_records; ?></div>
          </div>
          <div class="stat-box">
            <h5>Present</h5>
            <div class="number text-success-green"><?php echo $present_count; ?></div>
          </div>
          <div class="stat-box">
            <h5>Absent</h5>
            <div class="number text-danger-red"><?php echo $absent_count; ?></div>
          </div>
          <div class="stat-box">
            <h5>Late</h5>
            <div class="number text-warning-amber"><?php echo $late_count; ?></div>
          </div>
        </div>

        <!-- Detailed History Table -->
        <?php if ($total_records > 0): ?>
          <table class="decorative-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($attendance_logs as $log): ?>
                <tr>
                  <td><strong><?php echo date('M d, Y', strtotime($log['date'])); ?></strong></td>
                  <td><?php echo htmlspecialchars($log['subject']); ?></td>
                  <td>
                    <?php if ($log['status'] === 'Present'): ?>
                      <span class="badge-present">Present</span>
                    <?php elseif ($log['status'] === 'Absent'): ?>
                      <span class="badge-absent">Absent</span>
                    <?php else: ?>
                      <span class="badge-late">Late</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="empty-state-text">No attendance records found for this student.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div>
      <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
    </div>
  </div>
</body>
</html>