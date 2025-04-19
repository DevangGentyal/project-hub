<?php
ob_start();
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'], $_SESSION['user_role'])) {
  $dest = $_SESSION['user_role'] === 'student'
    ? 'student-dashboard.php'
    : 'guide-dashboard.php';
  header("Location: $dest");
  exit;
}

$login_error = "";
$registration_success = isset($_GET['registered']) && $_GET['registered'] === 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  include 'includes/db_connect.php';

  $role = trim($_POST['role'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($role) || empty($email) || empty($password)) {
    $login_error = "All fields are required.";
  } elseif (!in_array($role, ['student', 'guide'], true)) {
    $login_error = "Please select a valid role.";
  } else {
    // Pick table based on role
    if ($role === 'student') {
      $stmt = $conn->prepare("SELECT student_id, password FROM students WHERE email = ?");
    } else {
      $stmt = $conn->prepare("SELECT guide_id, password FROM guides WHERE email = ?");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      // After you fetch $user from either students or guides table:
      if (password_verify($password, $user['password'])) {
        // **DO NOT** call session_start() again here
        if ($role === 'student') {
          $_SESSION['user_id'] = $user['student_id'];
        } else {
          $_SESSION['user_id'] = $user['guide_id'];
        }
        $_SESSION['user_role'] = $role;
        $_SESSION['email'] = $email;

        // Redirect and exit immediately
        $dest = $role === 'student'
          ? 'student-dashboard.php'
          : 'guide-dashboard.php';
        header("Location: $dest");
        // echo '<pre>';
        // print_r($_SESSION);
        // echo '</pre>';
        exit;
      } else {
        $login_error = 'Incorrect password!';
      }
    } else {
      $login_error = "No {$role} found with that email.";
    }
    $stmt->close();
  }
  $conn->close();
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/auth.css">
<style>

</style>

<section class="auth-section">
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <a href="index.php" class="auth-logo">
          <i class="ri-code-box-line"></i><span>ProjectHub</span>
        </a>
        <h1>Welcome back</h1>
        <p>Please select your role and login</p>
      </div>

      <?php if ($registration_success): ?>
        <div class="success-message">
          Registration successful! Please log in with your new account.
        </div>
      <?php endif; ?>

      <?php if ($login_error): ?>
        <div class="error-message">
          <?= htmlspecialchars($login_error) ?>
        </div>
      <?php endif; ?>

      <form id="loginForm" class="auth-form" method="POST" action="">
        <!-- Custom Role Selector -->
        <div class="role-selector">
          <label class="role-option">
            <input type="radio" name="role" value="guide" <?= ($_POST['role'] ?? '') === 'guide' ? 'checked' : '' ?>>
            <div class="role-content">
              <div class="role-icon"><i class="ri-user-star-line"></i></div>
              <div class="role-info">
                <h3>Guide</h3>
                <p>Teacher/Supervisor</p>
              </div>
            </div>
          </label>

          <label class="role-option">
            <input type="radio" name="role" value="student" <?= ($_POST['role'] ?? 'student') === 'student' ? 'checked' : '' ?>>
            <div class="role-content">
              <div class="role-icon"><i class="ri-user-line"></i></div>
              <div class="role-info">
                <h3>Student</h3>
                <p>Team Member</p>
              </div>
            </div>
          </label>
        </div>

        <!-- Email Field -->
        <div class="form-group">
          <label for="email">Email address</label>
          <div class="input-wrapper">
            <i class="ri-mail-line"></i>
            <input type="email" id="email" name="email" placeholder="Enter your email" required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <i class="ri-lock-line"></i>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="toggle-password">
              <i class="ri-eye-line"></i>
            </button>
          </div>
        </div>

        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" name="remember"><span>Remember me</span>
          </label>
          <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
        </div>

        <button type="submit" class="auth-submit">
          Sign in <i class="ri-arrow-right-line"></i>
        </button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <a href="register.php">Sign up</a></p>
      </div>
    </div>
  </div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Toggle password visibility
    const toggleBtn = document.querySelector('.toggle-password');
    const pwdInput = document.getElementById('password');
    toggleBtn.addEventListener('click', () => {
      const type = pwdInput.type === 'password' ? 'text' : 'password';
      pwdInput.type = type;
      toggleBtn.innerHTML = type === 'password'
        ? '<i class="ri-eye-line"></i>'
        : '<i class="ri-eye-off-line"></i>';
    });
  });
</script>