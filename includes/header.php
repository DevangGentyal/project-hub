<?php
// ==== PHP CODE AREA: SESSION HANDLING ====
// Start the session to access session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ==== END PHP CODE AREA ====
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body>
    <div class="landing-container">
        <!-- Navigation -->
        <nav class="nav-bar">
            <a href="/project_hub" style="text-decoration:none">
                <div class="nav-logo">
                    <i class="ri-code-box-line"></i>
                    <span>ProjectHub</span>
                </div>
            </a>

            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#about">About</a>
                <a href="#contact">Contact</a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="nav-btn login-btn">Login</a>
                    <a href="register.php" class="nav-btn signup-btn" style="color: white;">Sign up</a>
                <?php else: ?>
                    <a href="<?php
                    // Check user role in session and set appropriate dashboard link
                    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'guide') {
                        echo 'guide-dashboard.php';
                    } else {
                        echo 'student-dashboard.php'; // Default to student dashboard
                    }
                    ?>" class="nav-btn">Dashboard</a>
                    <a href="logout.php" class="nav-btn login-btn">Logout</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</body>

</html>