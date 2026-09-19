<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Role Guard: Ensure user is logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['username'];

// 1. Fetch Summary Stats (Total Present, Absent, Late, and Overall Percentage)
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

// Calculate overall percentage (Counting 'Present' and 'Late' as attended)
$attended_classes = $total_present + $total_late;
$overall_pct = ($total_classes > 0) ? round(($attended_classes / $total_classes) * 100, 1) : 0;

// 2. Fetch Detailed Attendance Records
$records_stmt = $conn->prepare("
    SELECT date, subject, status 
    FROM attendance 
    WHERE student_id = ? 
    ORDER BY date DESC
");
$records_stmt->bind_param("i", $student_id);
$records_stmt->execute();
$records = $records_stmt->get_result();
$records_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Attendance Report - Smart Attendance</title>
    <link rel="stylesheet" href="css/style.css?v=13">
</head>
<body>
    <?php include 'partials/navbar.php'; ?>

    <div class="dashboard-container">

        <!-- PAGE HEADER -->
        <div class="dashboard-header">
            <h2>My Attendance Report</h2>
        </div>

        <!-- Summary Stats Card -->
        <div class="dashboard-card report-card">
            <h3>📊 Attendance Overview</h3>
            
            <div class="overall-summary-card">
                <h4>Overall Attendance Score</h4>
                <p>
                    <span class="pct-big"><?php echo $overall_pct; ?>%</span>
                    (Attended <strong><?php echo $attended_classes; ?></strong> out of <strong><?php echo $total_classes; ?></strong> recorded classes)
                </p>
            </div>

            <div class="stats-grid">
                <div class="stat-box">
                    <h5>Total Classes</h5>
                    <div class="number"><?php echo $total_classes; ?></div>
                </div>
                <div class="stat-box">
                    <h5>Present</h5>
                    <div class="number text-success-green"><?php echo $total_present; ?></div>
                </div>
                <div class="stat-box">
                    <h5>Absent</h5>
                    <div class="number text-danger-red"><?php echo $total_absent; ?></div>
                </div>
                <div class="stat-box">
                    <h5>Late</h5>
                    <div class="number text-warning-amber"><?php echo $total_late; ?></div>
                </div>
            </div>
        </div>

        <!-- Detailed Attendance Log Card -->
        <div class="dashboard-card">
            <h3>📋 Detailed Log</h3>

            <?php if ($records->num_rows > 0): ?>
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
                            <?php while ($row = $records->fetch_assoc()): ?>
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
                <p class="empty-state-text">No attendance records found yet.</p>
            <?php endif; ?>

            <div class="action-links">
                <a href="student_dashboard.php" class="back-link">← Back to Dashboard</a>
            </div>
        </div>

    </div>
</body>
</html>