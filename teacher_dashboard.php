<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Role Guard: Teacher check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$today = date('Y-m-d');

// 1. Fetch Teacher Details (Subject)
$stmt = $conn->prepare("SELECT subject FROM users WHERE user_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();
$subject = $teacher_data['subject'] ?? 'Not Assigned';
$_SESSION['subject'] = $subject;
$stmt->close();

// 2. Check if Attendance is ALREADY Submitted for Today
$check_stmt = $conn->prepare("
    SELECT COUNT(*) as submitted 
    FROM attendance 
    WHERE date = ? AND subject = ?
");
$check_stmt->bind_param("ss", $today, $subject);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();
$is_already_submitted = ($check_result['submitted'] > 0);
$check_stmt->close();

// 3. Calculate TODAY's Overall Class Attendance Percentage
$today_pct = 0;
$today_present_count = 0;
$today_total_count = 0;

if ($is_already_submitted) {
    $summary_stmt = $conn->prepare("
        SELECT 
            COUNT(*) AS total_students,
            SUM(CASE WHEN status IN ('Present', 'Late') THEN 1 ELSE 0 END) AS present_students
        FROM attendance
        WHERE date = ? AND subject = ?
    ");
    $summary_stmt->bind_param("ss", $today, $subject);
    $summary_stmt->execute();
    $summary_data = $summary_stmt->get_result()->fetch_assoc();
    $today_total_count = $summary_data['total_students'];
    $today_present_count = $summary_data['present_students'];
    $today_pct = ($today_total_count > 0) ? round(($today_present_count / $today_total_count) * 100, 1) : 0;
    $summary_stmt->close();
}

// 4. Fetch Students on Approved Leave Today
$leave_stmt = $conn->prepare("
    SELECT u.user_id, u.username, l.start_date, l.end_date, l.reason 
    FROM leave_application l
    JOIN users u ON l.student_id = u.user_id
    WHERE l.status = 'Approved' 
      AND ? BETWEEN l.start_date AND l.end_date
    ORDER BY u.username ASC
");
$leave_stmt->bind_param("s", $today);
$leave_stmt->execute();
$approved_leaves_result = $leave_stmt->get_result();

$on_leave_ids = [];
$approved_leaves = [];

while ($leave = $approved_leaves_result->fetch_assoc()) {
    $on_leave_ids[] = $leave['user_id'];
    $approved_leaves[] = $leave;
}

// 5. Fetch Approved Students & Today's Attendance Status
$student_stmt = $conn->prepare("
    SELECT u.user_id, u.username, a.status AS today_status
    FROM users u
    LEFT JOIN attendance a ON u.user_id = a.student_id AND a.date = ? AND a.subject = ?
    WHERE u.role = 'student' AND u.status = 'approved'
    ORDER BY u.username ASC
");
$student_stmt->bind_param("ss", $today, $subject);
$student_stmt->execute();
$students = $student_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Smart Attendance</title>
    <link rel="stylesheet" href="css/style.css?v=13">
</head>
<body class="teacher-dashboard-body">
    <?php include 'partials/navbar.php'; ?>

    <div class="dashboard-container">

        <!-- PAGE HEADER -->
        <header class="dashboard-header">
            <div class="header-title">
                <h2>Teacher Dashboard</h2>
            </div>
        </header>
        
        <!-- Header Info Card -->
        <div class="dashboard-card">
            <h3>Welcome, <?php echo htmlspecialchars($username); ?>!</h3>
            <p><strong>Today's Date:</strong> <?php echo date('F j, Y'); ?></p>
            <p><strong>Assigned Subject:</strong> <?php echo htmlspecialchars($subject); ?></p>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'attendance_saved'): ?>
            <div class="msg-success">Attendance recorded successfully for today!</div>
        <?php endif; ?>

        <!-- Approved Leaves Today Section -->
        <div class="dashboard-card leave-card">
            <h3>Students on Approved Leave Today</h3>
            <?php if (count($approved_leaves) > 0): ?>
                <table class="decorative-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Leave Period</th>
                            <th>Reason for Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_leaves as $leave): ?>
                            <?php 
                                $start = new DateTime($leave['start_date']);
                                $end = new DateTime($leave['end_date']);
                                $days = $start->diff($end)->days + 1;
                            ?>
                            <tr>
                                <td><strong>#<?php echo $leave['user_id']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($leave['username']); ?></strong></td>
                                <td>
                                    <span class="date-range-badge">
                                        <?php echo date('M d', strtotime($leave['start_date'])); ?> — <?php echo date('M d, Y', strtotime($leave['end_date'])); ?>
                                    </span>
                                    <span class="days-pill"><?php echo $days; ?> <?php echo ($days > 1) ? 'Days' : 'Day'; ?></span>
                                </td>
                                <td>
                                    <span class="reason-highlight">
                                        <?php echo htmlspecialchars($leave['reason']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-leave-banner">
                    <span>No students are on approved leave today. All students are expected in class!</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mark Daily Attendance Section -->
        <div class="dashboard-card attendance-card">
            <h3>Mark Daily Attendance</h3>
            <div class="info-note">
                <strong>Note:</strong> Students entering class after 10 minutes are marked as <strong>Late</strong>.
            </div>

            <?php if ($is_already_submitted): ?>
                <!-- Overall Class Attendance Summary Box -->
                <div class="overall-summary-card">
                    <h4>Today's Total Class Attendance Summary</h4>
                    <p>
                        Overall Attendance for Today: 
                        <span class="pct-big"><?php echo $today_pct; ?>%</span> 
                        (<?php echo $today_present_count; ?> out of <?php echo $today_total_count; ?> students present)
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($students->num_rows > 0): ?>
                <form action="backend/mark_attendance.php" method="POST" class="daily-attendance-form" onsubmit="return confirmSubmission();">
                    <table class="decorative-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; while ($student = $students->fetch_assoc()): ?>
                                <?php 
                                    $is_on_leave = in_array($student['user_id'], $on_leave_ids);
                                    
                                    if ($is_on_leave) {
                                        $current_status = 'Absent';
                                    } else {
                                        $current_status = $student['today_status'] ?? 'Present';
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo $serial++; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['username']); ?></strong>
                                        <?php if ($is_on_leave): ?>
                                            <span class="on-leave-badge">On Approved Leave</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="attendance[<?php echo $student['user_id']; ?>]" 
                                                class="status-select-custom" 
                                                <?php echo ($is_on_leave || $is_already_submitted) ? 'disabled' : ''; ?>>
                                            <option value="Present" <?php echo ($current_status === 'Present') ? 'selected' : ''; ?>>Present</option>
                                            <option value="Absent" <?php echo ($current_status === 'Absent') ? 'selected' : ''; ?>>Absent</option>
                                            <option value="Late" <?php echo ($current_status === 'Late') ? 'selected' : ''; ?>>Late</option>
                                        </select>
                                        
                                        <?php if ($is_on_leave): ?>
                                            <input type="hidden" name="attendance[<?php echo $student['user_id']; ?>]" value="Absent">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <br>

                    <?php if (!$is_already_submitted): ?>
                        <button type="submit" class="btn-primary">Save Attendance</button>
                    <?php else: ?>
                        <div class="locked-badge">Submitted for Today</div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <p class="empty-state">No active students found in the system.</p>
            <?php endif; ?>
        </div>

        <script>
        function confirmSubmission() {
            return confirm("Are you sure you want to submit today's attendance? Once submitted, records cannot be altered for today.");
        }
        </script>
    </div>
</body>
</html>