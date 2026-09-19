<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>NCCS - Smart Attendance Portal Login</title>
  <link rel="stylesheet" href="css/style.css?v=13">
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
        <h2>NCCS</h2>
        <p>Sign in to Smart Attendance Portal</p>
      </div>

      <?php
      if (isset($_GET['error'])) {
        echo "<div class='alert alert-error'>" . htmlspecialchars($_GET['error']) . "</div>";
      }
      if (isset($_GET['success'])) {
        echo "<div class='alert alert-success'>" . htmlspecialchars($_GET['success']) . "</div>";
      }
      ?>

      <form action="backend/login.php" method="POST">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <!-- Inline Row for Show Password -->
        <div class="form-actions-inline">
          <div class="show-password-container">
            <input type="checkbox" id="togglePassword" onclick="togglePasswordVisibility()">
            <label for="togglePassword" class="checkbox-label">Show Password</label>
          </div>
        </div>

        <button type="submit" style="width: 100%;">Login</button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account?</p>
        <a href="register.php"><button type="button" class="sign-up" style="width: 100%;">Sign Up</button></a>
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