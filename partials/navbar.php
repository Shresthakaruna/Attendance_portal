<?php
// Expects an active PHP session with $_SESSION['role'] and $_SESSION['username'] already set.
$current_page = basename($_SERVER['PHP_SELF']);
$nav_role = $_SESSION['role'] ?? '';
$nav_username = $_SESSION['username'] ?? '';

function nav_class($page, $current) {
    return 'nav-link' . ($page === $current ? ' active' : '');
}
?>
<nav class="navbar">
  <div class="navbar-inner">
    <a href="<?php echo $nav_role === 'admin' ? 'admin_dashboard.php' : ($nav_role === 'teacher' ? 'teacher_dashboard.php' : 'student_dashboard.php'); ?>" class="navbar-brand">
      <span class="brand-mark">📋</span>
      <span>Smart Attendance</span>
    </a>

    <button type="button" class="navbar-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>

    <div class="navbar-links" id="navLinks">
      <?php if ($nav_role === 'admin'): ?>
        <a href="admin_dashboard.php" class="<?php echo nav_class('admin_dashboard.php', $current_page); ?>">Dashboard</a>
        <a href="admin_manage_users.php" class="<?php echo nav_class('admin_manage_users.php', $current_page); ?>">Manage Users</a>
        <a href="admin_student_report.php" class="<?php echo nav_class('admin_student_report.php', $current_page); ?>">Student Reports</a>
      <?php elseif ($nav_role === 'teacher'): ?>
        <a href="teacher_dashboard.php" class="<?php echo nav_class('teacher_dashboard.php', $current_page); ?>">Dashboard</a>
      <?php elseif ($nav_role === 'student'): ?>
        <a href="student_dashboard.php" class="<?php echo nav_class('student_dashboard.php', $current_page); ?>">Dashboard</a>
        <a href="apply_leave.php" class="<?php echo nav_class('apply_leave.php', $current_page); ?>">Apply Leave</a>
        <a href="student_attendance_report.php" class="<?php echo nav_class('student_attendance_report.php', $current_page); ?>">My Report</a>
      <?php endif; ?>
    </div>

    <div class="navbar-user">
      <span class="navbar-username">
        <span class="username-text"><?php echo htmlspecialchars($nav_username); ?></span>
        <span class="role-chip"><?php echo htmlspecialchars(ucfirst($nav_role)); ?></span>
      </span>
      <a href="backend/logout.php" class="btn-logout-nav">Logout</a>
    </div>
  </div>
</nav>
<script>
  (function () {
    var toggle = document.getElementById('navToggle');
    var links = document.getElementById('navLinks');
    if (toggle && links) {
      toggle.addEventListener('click', function () {
        var isOpen = links.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });
    }
  })();
</script>
