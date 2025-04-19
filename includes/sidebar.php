<?php
// ==== PHP CODE AREA: USER PROFILE DATA ====
// TODO: Retrieve user profile data from database
// $user_name = $_SESSION['user_name'] ?? 'User';
// $user_role = $_SESSION['user_role'] ?? 'Student';
// ==== END PHP CODE AREA ====
?>
<!-- Sidebar -->
<aside class="sidebar">
    <div class="profile-section">
        <div class="profile-image">
            <i class="ri-user-line"></i>
        </div>
        <div class="profile-info">
            <span class="user-name"><?php echo htmlspecialchars($user_name ?? 'User'); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($user_role ?? 'Student'); ?></span>
            <a href="profile.php" class="profile-link">Profile</a>
            <a href="logout.php" class="logout-link">Logout</a>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student'): ?>
            <a href="student_dashboard.php" class="nav-item">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="subjects.php" class="nav-item">
                <i class="ri-book-line"></i>
                <span>Subjects</span>
            </a>
            <a href="teams.php" class="nav-item">
                <i class="ri-team-line"></i>
                <span>Teams</span>
            </a>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'guide'): ?>
            <a href="guide_dashboard.php" class="nav-item">
                <i class="ri-dashboard-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="subjects.php" class="nav-item">
                <i class="ri-book-line"></i>
                <span>Subjects</span>
            </a>
            <a href="students.php" class="nav-item">
                <i class="ri-user-line"></i>
                <span>Students</span>
            </a>
        <?php endif; ?>
    </nav>
</aside> 