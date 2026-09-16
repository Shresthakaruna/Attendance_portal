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
        <a href="admin_manage_users.php" class="<?php echo nav_class('admin_manage_users.php', $current_page); ?>">Users</a>
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
      <div class="user-menu" id="userMenu">
        <button type="button" class="user-menu-toggle" id="userMenuToggle" aria-expanded="false" aria-haspopup="true">
          <span class="role-chip"><?php echo htmlspecialchars(ucfirst($nav_role)); ?></span>
          <span class="user-menu-caret" aria-hidden="true">▾</span>
        </button>
        <div class="user-menu-dropdown" id="userMenuDropdown">
          <div class="user-menu-header">
            <span class="user-menu-name"><?php echo htmlspecialchars($nav_username); ?></span>
          </div>
          <a href="backend/logout.php" class="user-menu-logout">Logout</a>
        </div>
      </div>
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

    var userMenu = document.getElementById('userMenu');
    var userToggle = document.getElementById('userMenuToggle');
    var userDropdown = document.getElementById('userMenuDropdown');
    if (userMenu && userToggle && userDropdown) {
      userToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleUserMenu();
      });
      document.addEventListener('click', function (e) {
        if (!userMenu.contains(e.target)) {
          closeUserMenu();
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeUserMenu();
        }
      });
    }
    function closeUserMenu() {
      if (userDropdown) userDropdown.classList.remove('is-open');
      if (userToggle) userToggle.setAttribute('aria-expanded', 'false');
    }
    function toggleUserMenu() {
      var isOpen = userDropdown.classList.toggle('is-open');
      userToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
  })();
</script>
