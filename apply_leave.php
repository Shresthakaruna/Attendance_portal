<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/config.php';

// Verify Student Session
/////jhgjhghjghghjgjhghjghjghjgh
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php?error=" . urlencode("Unauthorized access"));
    exit();
}

$student_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// --- PROCESS LEAVE SUBMISSION (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date   = trim($_POST['end_date'] ?? '');
    $reason     = trim($_POST['reason'] ?? '');

    // 1. Check Empty Fields
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        header("Location: student_dashboard.php?error=" . urlencode("All fields are required."));
        exit();
    }

    // 2. Check if Start Date is in the Past
    if ($start_date < $today) {
        header("Location: student_dashboard.php?error=" . urlencode("Start date cannot be in the past."));
        exit();
    }

    // 3. Check if End Date is before Start Date
    if ($end_date < $start_date) {
        header("Location: apply_leave.php?error=" . urlencode("End date must be on or after the start date."));
        exit();
    }

    // 4. Check Reason Length
    if (strlen($reason) < 10) {
        header("Location: apply_leave.php?error=" . urlencode("Please provide a detailed reason (at least 10 characters)."));
        exit();
    }

    // 5. Prevent Overlapping Leave Requests
    $overlap_stmt = $conn->prepare("
        SELECT leave_id FROM leave_application 
        WHERE student_id = ? 
          AND status != 'Rejected' 
          AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))
    ");
    $overlap_stmt->bind_param("issss", $student_id, $end_date, $start_date, $start_date, $end_date);
    $overlap_stmt->execute();
    $overlap_result = $overlap_stmt->get_result();

    if ($overlap_result->num_rows > 0) {
        $overlap_stmt->close();
        header("Location: apply_leave.php?error=" . urlencode("You already have an active or pending leave request overlapping with these dates."));
        exit();
    }
    $overlap_stmt->close();

    // 6. Insert Leave Request into DB
    $stmt = $conn->prepare("INSERT INTO leave_application (student_id, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $student_id, $start_date, $end_date, $reason);

    if ($stmt->execute()) {
        header("Location: student_dashboard.php?msg=" . urlencode("Leave application submitted successfully!"));
    } else {
        header("Location: student_dashboard.php?error=" . urlencode("Failed to submit request. Please try again."));
    }

    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Leave | Student Portal</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="dashboard-body">

  <?php include 'partials/navbar.php'; ?>

  <div class="app-layout">

    <!-- MAIN CONTENT AREA -->
    <main class="main-content-area" style="max-width: 700px; margin: 30px auto;">
      
      <header class="content-header">
        <h1 class="page-title">Apply for Leave</h1>
      </header>

      <!-- ALERT MESSAGES -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
      <?php endif; ?>

      <!-- FORM CARD -->
      <div class="dashboard-card">
        <form action="apply_leave.php" method="POST" id="leaveForm" onsubmit="return validateLeaveForm()">
          
          <div class="form-group" style="margin-bottom: 15px;">
            <label for="start_date" style="display: block; font-weight: bold; margin-bottom: 5px;">Start Date:</label>
            <input type="date" id="start_date" name="start_date" min="<?php echo $today; ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
          </div>

          <div class="form-group" style="margin-bottom: 15px;">
            <label for="end_date" style="display: block; font-weight: bold; margin-bottom: 5px;">End Date:</label>
            <input type="date" id="end_date" name="end_date" min="<?php echo $today; ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
          </div>

          <div class="form-group" style="margin-bottom: 20px;">
            <label for="reason" style="display: block; font-weight: bold; margin-bottom: 5px;">Reason for Leave:</label>
            <textarea id="reason" name="reason" rows="4" minlength="10" placeholder="Please state your reason clearly (minimum 10 characters)..." required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;"></textarea>
          </div>

          <button type="submit" class="btn-action btn-approve" style="padding: 12px 24px; font-size: 1rem; cursor: pointer;">
            Submit Leave Request
          </button>
          
        </form>
      </div>

    </main>

  </div>

  <!-- FRONTEND VALIDATION SCRIPT -->
  <script>
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    // Automatically restrict End Date based on selected Start Date
    startDateInput.addEventListener('change', function() {
      const selectedStartDate = this.value;
      endDateInput.min = selectedStartDate;
      
      if (endDateInput.value && endDateInput.value < selectedStartDate) {
        endDateInput.value = selectedStartDate;
      }
    });

    // Client-side validation before submission
    function validateLeaveForm() {
      const startDate = startDateInput.value;
      const endDate = endDateInput.value;
      const reason = document.getElementById('reason').value.trim();

      if (new Date(endDate) < new Date(startDate)) {
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

</body>
</html>