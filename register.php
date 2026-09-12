<!DOCTYPE html>
<html>

<head>
  <title>Smart Attendance Portal - Register</title>
  <link rel="stylesheet" href="css/style.css">
  <script>
    function toggleSubjectField() {
      var roleSelect = document.getElementById("role");
      var subjectGroup = document.getElementById("subject-group");
      var subjectSelect = document.getElementById("subject");

      if (roleSelect.value === "teacher") {
        subjectGroup.style.display = "block";
        subjectSelect.required = true;
      } else {
        subjectGroup.style.display = "none";
        subjectSelect.required = false;
        subjectSelect.value = ""; // Clear subject selection for non-teachers
      }
    }
  </script>
  <style>
    #togglePassword {
      display: inline;
      width: auto;
    }
  </style>
</head>

<body class="no-navbar">
  <div class="auth-page">
    <div class="auth-card">

      <div class="auth-brand">
        <span class="brand-mark">📋</span>
        <h2>Create an account</h2>
        <p>Join the Smart Attendance Portal</p>
      </div>

      <!-- Display error messages -->
      <?php
      if (isset($_GET['error'])) {
        echo "<div class='alert alert-error'>" . htmlspecialchars($_GET['error']) . "</div>";
      }
      ?>

      <form action="backend/register.php" method="POST">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" id="password" minlength="8" required>
        <small style="color: #888;">Minimum 8 characters required</small>
        <div class="show-password-container">
          <input type="checkbox" id="togglePassword" onclick="togglePasswordVisibility()">
          <label for="togglePassword" class="checkbox-label">Show Password</label>
        </div>

        <label>Role</label>
        <select name="role" id="role" onchange="toggleSubjectField()" required>
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>

        <!-- Subject Dropdown (Visible when Teacher is selected) -->
        <div id="subject-group" style="display: none; margin-top: -4px;">
          <label>Assigned Subject</label>
          <select name="subject" id="subject">
            <option value="">-- Select Subject --</option>
            <option value="Database Management System">Database Management System</option>
            <option value="Object Oriented Programming">Object Oriented Programming</option>
            <option value="Web Technology">Web Technology</option>
            <option value="Operating System">Operating System</option>
          </select>
        </div>

        <button type="submit" style="width: 100%; margin-top: 6px;">Register</button>
      </form>

      <div class="auth-footer">
        <p>Already have an account?</p>
        <a href="index.php"><button type="button" style="width: 100%;">Back to Login</button></a>
      </div>

    </div>
  </div>

  <!-- JavaScript to toggle password visibility -->
  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById("password");
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
      } else {
        passwordInput.type = "password";
      }
    }
  </script>
</body>

</html>