<?php
// ==== PHP CODE AREA: SESSION HANDLING ====
// TODO: Add session handling code
// session_start();
// if (isset($_SESSION['user_id'])) {
//     header('Location: dashboard.php');
//     exit();
// }
// ==== END PHP CODE AREA ====

require_once 'includes/header.php';
?>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">
                    Easily manage & control<br>
                    your project workflow
                </h1>
                <p class="hero-subtitle">
                    From task management to progress tracking, streamline your project workflow and collaborate with your team efficiently.
                </p>
                <div class="hero-buttons">
                    <a href="register.php" class="primary-btn">Get started</a>
                    <a href="#features" class="secondary-btn">Learn more</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="dashboard-preview">
                    <img src="assets/images/landing_page.svg" alt="Dashboard Preview">
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <h2 class="section-title">Everything you need for project management</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon blue">
                        <i class="ri-team-line"></i>
                    </div>
                    <h3>Team Collaboration</h3>
                    <p>Work together seamlessly with your team members in real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon purple">
                        <i class="ri-task-line"></i>
                    </div>
                    <h3>Task Management</h3>
                    <p>Organize and track tasks efficiently with our intuitive interface.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon green">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <h3>Progress Tracking</h3>
                    <p>Monitor project progress and stay updated with real-time analytics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon orange">
                        <i class="ri-share-box-line"></i>
                    </div>
                    <h3>File Sharing</h3>
                    <p>Share and manage project files securely in one place.</p>
                </div>
            </div>
        </section>

<?php require_once 'includes/footer.php'; ?> 