<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Verify Student Session
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

$student_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+1 month'));

// 1. Fetch Student Profile Details
$user_stmt = $conn->prepare("SELECT user_id, username, role, status FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $student_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$student_info = $user_result ? $user_result->fetch_assoc() : null;
$user_stmt->close();

// 2. Fetch Attendance Summary Stats
$summary_stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS total_present,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absent,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS total_late
    FROM attendance
    WHERE student_id = ?
");
$summary_stmt->bind_param("i", $student_id);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

$total_classes = $summary['total_classes'] ?? 0;
$total_present = $summary['total_present'] ?? 0;
$total_absent  = $summary['total_absent'] ?? 0;
$total_late    = $summary['total_late'] ?? 0;

// Calculate Overall Percentage
$attended_classes = $total_present + $total_late;
$overall_pct = ($total_classes > 0) ? round(($attended_classes / $total_classes) * 100, 1) : 0;

// 3. Fetch Recent Attendance Records
$records_stmt = $conn->prepare("
    SELECT date, subject, status 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC 
    LIMIT 10
");
$records_stmt->bind_param("i", $student_id);
$records_stmt->execute();
$attendance_records = $records_stmt->get_result();
$records_stmt->close();

// 4. Fetch Leave Applications History
$leave_stmt = $conn->prepare("SELECT * FROM leave_application WHERE student_id = ? ORDER BY leave_id DESC");
$leave_stmt->bind_param("i", $student_id);
$leave_stmt->execute();
$leave_result = $leave_stmt->get_result();
$total_applied = $leave_result ? $leave_result->num_rows : 0;
$leave_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard - Attendance Portal</title>
  <link rel="stylesheet" href="css/style.css?v=7">
  
  <!-- Include Chart.js Library -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include 'partials/navbar.php'; ?>

  <div class="dashboard-container">

    <!-- PAGE HEADER -->
    <div class="dashboard-header">
      <h2>Student Dashboard</h2>
    </div>

    <!-- Feedback Messages -->
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- SECTION 1: STUDENT PROFILE -->
    <div class="dashboard-card">
      <h3>Student Profile Details</h3>
      <table class="profile-table">
        <tr>
          <td class="label-col"><strong>Student ID:</strong></td>
          <td>#<?php echo htmlspecialchars($student_info['user_id'] ?? $student_id); ?></td>
        </tr>
        <tr>
          <td class="label-col"><strong>Username:</strong></td>
          <td><?php echo htmlspecialchars($student_info['username'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
          <td class="label-col"><strong>Role:</strong></td>
          <td><?php echo ucfirst(htmlspecialchars($student_info['role'] ?? 'student')); ?></td>
        </tr>
        <tr>
          <td class="label-col"><strong>Account Status:</strong></td>
          <td>
            <span class="status-approved"><?php echo ucfirst(htmlspecialchars($student_info['status'] ?? 'Approved')); ?></span>
          </td>
        </tr>
      </table>
    </div>

    <!-- SECTION 2: ATTENDANCE REPORT WITH DISK / DONUT CHART -->
    <div class="dashboard-card report-card">
      <h3>Personal Attendance Overview</h3>
      
      <div class="chart-flex-container">
        <!-- Disk / Donut Chart Container -->
        <div class="chart-wrapper">
          <canvas id="attendanceDiskChart"></canvas>
        </div>

        <!-- Metric Details -->
        <div class="chart-details">
          <h4>Overall Score: <span class="pct-big"><?php echo $overall_pct; ?>%</span></h4>
          <p>Attended <strong><?php echo $attended_classes; ?></strong> out of <strong><?php echo $total_classes; ?></strong> recorded classes.</p>
          
          <div class="stats-grid">
            <div class="stat-box">
              <h5>Total</h5>
              <div class="number"><?php echo $total_classes; ?></div>
            </div>
            <div class="stat-box">
              <h5>Present</h5>
              <div class="number text-success"><?php echo $total_present; ?></div>
            </div>
            <div class="stat-box">
              <h5>Absent</h5>
              <div class="number text-danger"><?php echo $total_absent; ?></div>
            </div>
            <div class="stat-box">
              <h5>Late</h5>
              <div class="number text-warning"><?php echo $total_late; ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Attendance Log Table -->
      <h4 style="margin-top: 25px;">Recent Attendance Records</h4>
      <?php if ($attendance_records && $attendance_records->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="decorative-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $attendance_records->fetch_assoc()): ?>
                <tr>
                  <td><strong><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></td>
                  <td><?php echo htmlspecialchars($row['subject']); ?></td>
                  <td>
                    <?php 
                      $status = $row['status'];
                      $badge_class = 'badge-present';
                      if ($status === 'Absent') $badge_class = 'badge-absent';
                      if ($status === 'Late') $badge_class = 'badge-late';
                    ?>
                    <span class="<?php echo $badge_class; ?>">
                      <?php echo htmlspecialchars($status); ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty-state">No attendance records logged yet.</p>
      <?php endif; ?>
    </div>

    <!-- SECTION 3: APPLY FOR LEAVE FORM -->
    <div class="dashboard-card leave-card">
      <h3>Apply for Leave</h3>
      <form action="backend/submit_leave.php" method="POST" class="leave-form" id="dashboardLeaveForm" onsubmit="return validateDashboardLeaveForm()">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" min="<?php echo $today; ?>" max="<?php echo $max_date; ?>" required>

        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" min="<?php echo $today; ?>" max="<?php echo $max_date; ?>" required>

        <label for="reason">Reason for Leave:</label>
        <textarea id="reason" name="reason" rows="4" minlength="10" placeholder="Enter reason for leave (minimum 10 characters)..." required></textarea>

        <button type="submit" class="btn-submit">Submit Application</button>
      </form>
    </div>

    <!-- SECTION 4: LEAVE APPLICATION STATUS TABLE -->
    <div class="dashboard-card">
      <h3>My Leave Applications (<?php echo $total_applied; ?>)</h3>
      <div class="table-responsive">
        <table class="decorative-table">
          <thead>
            <tr>
              <th>Application ID</th>
              <th>Start Date</th>
              <th>End Date</th>
              <th>Reason</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($leave_result && $leave_result->num_rows > 0): ?>
              <?php while ($row = $leave_result->fetch_assoc()): ?>
                <tr>
                  <td><strong>#<?php echo $row['leave_id']; ?></strong></td>
                  <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                  <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                  <td><?php echo htmlspecialchars($row['reason']); ?></td>
                  <td>
                    <?php 
                      $status = $row['status'];
                      $status_class = ($status === 'Approved') ? 'badge-present' : (($status === 'Rejected') ? 'badge-absent' : 'badge-late');
                    ?>
                    <span class="<?php echo $status_class; ?>">
                      <?php echo htmlspecialchars($status); ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="empty-state">No leave applications submitted yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- JAVASCRIPT FOR LEAVE FORM VALIDATION -->
  <script>
    function validateDashboardLeaveForm() {
      const startDate = document.getElementById('start_date').value;
      const endDate = document.getElementById('end_date').value;
      const reason = document.getElementById('reason').value.trim();

      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const maxDate = new Date(today);
      maxDate.setMonth(maxDate.getMonth() + 1);

      const startObj = new Date(startDate);
      if (startObj < today) {
        alert("Start date cannot be in the past.");
        return false;
      }

      if (startObj > maxDate) {
        alert("Leave must be applied within one month from today.");
        return false;
      }

      if (new Date(endDate) < startObj) {
        alert("End date cannot be earlier than the start date.");
        return false;
      }

      if (reason.length < 10) {
        alert("Please provide a reason with at least 10 characters.");
        return false;
      }

      return true;
    }
  </script>

  <!-- JAVASCRIPT FOR DISK / DONUT CHART -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const ctx = document.getElementById('attendanceDiskChart').getContext('2d');
      
      const presentCount = <?php echo (int)$total_present; ?>;
      const absentCount = <?php echo (int)$total_absent; ?>;
      const lateCount = <?php echo (int)$total_late; ?>;

      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Present', 'Absent', 'Late'],
          datasets: [{
            data: [presentCount, absentCount, lateCount],
            backgroundColor: [
              '#2ecc71', // Green for Present
              '#e74c3c', // Red for Absent
              '#f1c40f'  // Yellow for Late
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                boxWidth: 12,
                padding: 15
              }
            }
          },
          cutout: '65%' // Donut inner space
        }
      });
    });
  </script>
</body>
</html>