<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$guide_id = intval($_SESSION['user_id']);

// Determine user role
// For this page, we need to check if the current user is a student or a guide
if (!isset($_SESSION['role'])) {
    // Check if user_id exists in students table
    include 'includes/db_connect.php';
    $role_check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
    $role_check->bind_param("i", $_SESSION['user_id']);
    $role_check->execute();
    $result = $role_check->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['role'] = 'student';
    } else {
        // Check if user_id exists in guides table
        $role_check = $conn->prepare("SELECT guide_id FROM guides WHERE guide_id = ?");
        $role_check->bind_param("i", $_SESSION['user_id']);
        $role_check->execute();
        $result = $role_check->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['role'] = 'guide';
        } else {
            // Default role if not found
            $_SESSION['role'] = 'unknown';
        }
    }
    $role_check->close();
} else {
    include 'includes/db_connect.php';
}

// Get team ID and subject ID from URL
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Validate team_id and subject_id
if ($team_id <= 0 || $subject_id <= 0) {
    // Redirect or show error if invalid IDs
    header("Location: guide_dashboard.php");
    exit();
}

// Get subject name
$subject_name = "Unknown Subject";
$subject_query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
$stmt = $conn->prepare($subject_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $subject_name = $row['subject_name'];
}
$stmt->close();

// Get team data
$team_data = null;
$team_query = "SELECT * FROM teams WHERE team_id = ? AND subject_id = ?";
$stmt = $conn->prepare($team_query);
$stmt->bind_param("ii", $team_id, $subject_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $team_data = $result->fetch_assoc();
    
    // Parse team_member_ids from JSON
    $team_member_ids = [];
    if (!empty($team_data['team_member_ids'])) {
        // Try to decode JSON first
        $json_decode_result = json_decode($team_data['team_member_ids'], true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_decode_result)) {
            // Successfully decoded as JSON
            $team_member_ids = $json_decode_result;
        } else {
            // Try to treat it as a string containing array representation [1,2,3]
            if (preg_match('/^\[.*\]$/', $team_data['team_member_ids'])) {
                // Remove brackets and split by commas
                $trimmed = trim($team_data['team_member_ids'], '[]');
                $team_member_ids = array_map('intval', explode(',', $trimmed));
            } else {
                // Fall back to comma-separated string
                $team_member_ids = array_map('intval', explode(',', $team_data['team_member_ids']));
            }
        }
    }
    
    // For debugging
    error_log("Team member IDs parsed: " . print_r($team_member_ids, true));
    
    // Get team members details
    $team_members = [];
    if (!empty($team_member_ids) && is_array($team_member_ids)) {
        $placeholders = str_repeat('?,', count($team_member_ids) - 1) . '?';
        $members_query = "SELECT * FROM students WHERE student_id IN ($placeholders)";
        
        $member_stmt = $conn->prepare($members_query);
        
        // Create type string for bind_param (all integers)
        $types = str_repeat('i', count($team_member_ids));
        
        // Create reference array for bind_param
        $bind_params = array($types);
        foreach ($team_member_ids as $key => $val) {
            $bind_params[] = &$team_member_ids[$key];
        }
        
        // Call bind_param with references
        call_user_func_array(array($member_stmt, 'bind_param'), $bind_params);
        
        $member_stmt->execute();
        $members_result = $member_stmt->get_result();
        
        while ($member = $members_result->fetch_assoc()) {
            $team_members[] = $member;
        }
        
        $member_stmt->close();
    }
    
    // Get project data if any
    $project_data = null;
    $project_query = "SELECT * FROM projects WHERE team_id = ?";
    $stmt = $conn->prepare($project_query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $project_result = $stmt->get_result();
    
    if ($project_result->num_rows > 0) {
        $project_data = $project_result->fetch_assoc();
    }
    
    $stmt->close();
} else {
    // Team not found or doesn't belong to this subject
    header("Location: subject_view.php?subject_id=$subject_id");
    exit();
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Handle team creation form submission
$team_created = false;
$new_team_code = '';
$new_team_name = '';

// In PHP part at the beginning - handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_name']) && isset($_POST['team_code'])) {
    $team_name = trim($_POST['team_name']);
    $team_code = trim($_POST['team_code']);
    $progress = [];

    // Insert new team into database
    $insert_query = "INSERT INTO teams (team_name, team_code, subject_id, guide_id, progress) 
                    VALUES (?, ?, ?, ?, '0')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssii", $team_name, $team_code, $subject_id, $guide_id);

    if ($stmt->execute()) {
        // Insert inital project for the team
        $team_id = $conn->insert_id;
        $project_name = "Project Name";
        $project_abstract = "Project Abstract";
        $initial_progress = json_encode([
            ["phase_no" => 1, "phase_name" => "Project Topic Finalization", "is_completed" => false],
            ["phase_no" => 2, "phase_name" => "Ideation & Planning", "is_completed" => false],
            ["phase_no" => 3, "phase_name" => "Design & Structuring", "is_completed" => false],
            ["phase_no" => 4, "phase_name" => "Development & Execution", "is_completed" => false],
            ["phase_no" => 5, "phase_name" => "Testing and Deployment", "is_completed" => false],
            ["phase_no" => 6, "phase_name" => "Final Submission", "is_completed" => false],
        ]);

        $insert_query = "INSERT INTO projects (project_name, abstract, team_id, progress) 
                    VALUES (?, ?, ?,  ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssis", $project_name, $project_abstract, $team_id,$initial_progress);

        if ($stmt->execute()) {
            // For AJAX response
            if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                echo json_encode(['success' => true, 'team_name' => $team_name, 'team_code' => $team_code]);
                exit;
            }

            $team_created = true;
            $new_team_code = $team_code;
            $new_team_name = $team_name;
        }
    }
    $stmt->close();
}
?>
<!-- <link rel="stylesheet" href="assets/css/guide_dashboard.css"> -->
<link rel="stylesheet" href="assets/css/team_view.css">

<style>
/* Notification styles */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #fff;
    border-radius: 4px;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
    max-width: 400px;
}

.notification.success {
    border-left: 4px solid #4CAF50;
}

.notification.error {
    border-left: 4px solid #F44336;
}

.notification i {
    margin-right: 10px;
    font-size: 20px;
}

.notification.success i {
    color: #4CAF50;
}

.notification.error i {
    color: #F44336;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<!-- Main Container -->
<div class="team-view-container">
        <!-- Team Navigation -->
        <nav class="team-nav">
            <div class="nav-section active">
                <i class="ri-team-line"></i>
                Team Details
            </div>
            <div class="nav-section">
                <i class="ri-group-line"></i>
                Team Members
            </div>
            <div class="nav-section">
                <i class="ri-file-list-3-line"></i>
                Project Details
            </div>
            <div class="nav-section">
                <i class="ri-bar-chart-line"></i>
                Project Progress
            </div>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <div class="nav-section">
                <i class="ri-task-line"></i>
                My Tasks
            </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <div class="nav-section">
                <i class="ri-clipboard-line"></i>
                Assign Task
            </div>
            <?php endif; ?>
            <div class="nav-section">
                <i class="ri-folder-shared-line"></i>
                Shared Files
            </div>
        </nav>

        <!-- Main Content -->
        <main class="team-content">
            <!-- Header with Breadcrumb -->
            <div class="team-header">
                <div class="breadcrumb">
                    <a href="guide_dashboard.php">
                        <i class="ri-dashboard-line"></i>
                        Dashboard
                    </a>
                    <span>/</span>
                    <a href="subject_view.php?subject_id=<?php echo htmlspecialchars($subject_id); ?>" id="subject-link"><?php echo htmlspecialchars($subject_name); ?></a>
                    <span>/</span>
                    <span id="team-name"><?php echo htmlspecialchars($team_data['team_name']); ?></span>
                </div>
            </div>

            <div class="content-wrapper">
                <!-- Team Details Section -->
                <section class="team-details">
                    <!-- Basic Info Section -->
                    <div class="details-section">
                        <h2 class="section-title">Basic Information</h2>
                        
                        <div class="detail-group">
                            <div class="detail-label">Team Name</div>
                            <div class="detail-value" id="display-team-name"><?php echo htmlspecialchars($team_data['team_name']); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Team Code</div>
                            <div class="team-code" id="display-team-code"><?php echo htmlspecialchars($team_data['team_code']); ?></div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-label">Team Leader</div>
                            <select class="leader-select" id="team-leader" data-current-leader="<?php echo htmlspecialchars($team_data['team_leader']); ?>">
                                <option value="">Select Team Leader</option>
                                <?php if (!empty($team_members)): ?>
                                    <?php foreach ($team_members as $member): ?>
                                        <option value="<?php echo htmlspecialchars($member['student_id']); ?>" <?php echo ($member['student_id'] == $team_data['team_leader']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($member['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Team Statistics Section -->
                    <div class="details-section">
                        <h2 class="section-title">Team Overview</h2>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo count($team_members); ?></div>
                                <div class="stat-label">Team Members</div>
                            </div>
                            <div class="stat-card">
                                <?php 
                                $progress_percentage = 0;
                                if ($project_data && isset($project_data['progress'])) {
                                    $progress = json_decode($project_data['progress'], true);
                                    if (is_array($progress)) {
                                        $total_phases = count($progress);
                                        $completed_phases = 0;
                                        foreach ($progress as $phase) {
                                            if (isset($phase['is_completed']) && $phase['is_completed']) {
                                                $completed_phases++;
                                            }
                                        }
                                        $progress_percentage = $total_phases > 0 ? round(($completed_phases / $total_phases) * 100) : 0;
                                    }
                                }
                                ?>
                                <div class="stat-value"><?php echo $progress_percentage; ?>%</div>
                                <div class="stat-label">Project Progress</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Team Members Section -->
                <section class="team-members" style="display: none;">
                    <div class="details-section">
                        <h2 class="section-title">Team Members</h2>
                        <div class="members-grid">
                            <?php if (!empty($team_members)): ?>
                                <?php foreach ($team_members as $member): ?>
                                    <?php 
                                    // Calculate member progress (randomly for now)
                                    $member_progress = isset($member['progress']) ? intval($member['progress']) : rand(50, 95);
                                    ?>
                                    <div class="member-card">
                                        <div class="member-avatar">
                                            <img src="assets/images/avatar.jpg" alt="<?php echo htmlspecialchars($member['name']); ?>">
                                        </div>
                                        <div class="member-info">
                                            <h3 class="member-name"><?php echo htmlspecialchars($member['name']); ?></h3>
                                            <p class="member-roll">ID: <?php echo htmlspecialchars($member['student_id']); ?></p>
                                            <p class="member-email"><?php echo htmlspecialchars($member['email'] ?? ''); ?></p>
                                            <div class="member-progress">
                                                <div class="progress-label">
                                                    <span>Progress</span>
                                                    <span><?php echo $member_progress; ?>%</span>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $member_progress; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-members">
                                    <p>No team members found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Project Details Section -->
                <section class="project-details" style="display: none;">
                    <div class="details-section">
                        <form class="project-info" id="project-form">
                            <div class="detail-group">
                                <div class="detail-label">Project Topic</div>
                                <input type="text" class="project-title-input" value="<?php echo htmlspecialchars($project_data['project_name'] ?? 'Project Name'); ?>" required>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label">Project Description</div>
                                <textarea class="project-description-input" rows="6" required><?php echo htmlspecialchars($project_data['abstract'] ?? 'Project description...'); ?></textarea>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label">Project Timeline</div>
                                <div class="timeline-info">
                                    <div class="timeline-item">
                                        <span class="timeline-label">Start Date:</span>
                                        <input type="date" class="timeline-input" value="<?php echo htmlspecialchars($project_data['start_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                    <div class="timeline-item">
                                        <span class="timeline-label">Expected Completion:</span>
                                        <input type="date" class="timeline-input" value="<?php echo htmlspecialchars($project_data['end_date'] ?? date('Y-m-d', strtotime('+3 months'))); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="save-btn">
                                    <i class="ri-save-line"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- Project Progress Section -->
                <section class="project-progress" style="display: none;">
                    <div class="details-section">
                        <form class="progress-info" id="progress-form">
                            <!-- Overall Progress -->
                            <div class="detail-group">
                                <div class="progress-header">
                                    <div class="detail-label">Overall Progress</div>
                                    <div class="progress-percentage"><?php echo $progress_percentage; ?>%</div>
                                </div>
                                <div class="overall-progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
                                </div>
                            </div>

                            <!-- Project Phases -->
                            <div class="detail-group">
                                <div class="detail-label">Phases</div>
                                <div class="phases-list">
                                    <?php 
                                    $phases = [];
                                    if ($project_data && isset($project_data['progress'])) {
                                        $phases = json_decode($project_data['progress'], true);
                                    }
                                    
                                    if (!empty($phases) && is_array($phases)): 
                                        foreach ($phases as $phase):
                                            $is_completed = isset($phase['is_completed']) && $phase['is_completed'];
                                            $phase_name = isset($phase['phase_name']) ? $phase['phase_name'] : 'Phase ' . ($phase['phase_no'] ?? '');
                                    ?>
                                        <div class="phase-item">
                                            <div class="phase-content">
                                                <input type="text" class="phase-name" value="<?php echo htmlspecialchars($phase_name); ?>" required>
                                                <button type="button" class="phase-status <?php echo $is_completed ? 'completed' : 'pending'; ?>">
                                                    <i class="ri-<?php echo $is_completed ? 'check' : 'close'; ?>-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php 
                                        endforeach;
                                    else:
                                        // Default phases if none are defined
                                        $default_phases = [
                                            "Project Topic Finalization",
                                            "Ideation & Planning",
                                            "Design & Structuring",
                                            "Development & Execution",
                                            "Testing and Deployment",
                                            "Final Submission"
                                        ];
                                        foreach ($default_phases as $index => $name):
                                    ?>
                                        <div class="phase-item">
                                            <div class="phase-content">
                                                <input type="text" class="phase-name" value="<?php echo ($index + 1) . '. ' . htmlspecialchars($name); ?>" required>
                                                <button type="button" class="phase-status pending">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                                <button type="button" class="add-phase-btn">
                                    <i class="ri-add-line"></i>
                                    Add Phase
                                </button>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="save-btn">
                                    <i class="ri-save-line"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- My Tasks Section -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                <section class="my-tasks" style="display: none;">
                    <div class="details-section">
                        <form class="tasks-info" id="tasks-form">
                            <div class="tasks-header">
                                <h2 class="section-title">Task Name</h2>
                                <div class="progress-header">Progress</div>
                            </div>

                            <div class="tasks-list">
                                <!-- Task Item -->
                                <div class="task-item">
                                    <div class="task-header" data-expanded="false">
                                        <div class="task-main">
                                            <div class="task-info">
                                                <div class="task-number">1.</div>
                                                <div class="task-name">Creating Frontend</div>
                                            </div>
                                            <div class="task-actions">
                                                <div class="task-progress">
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task1-progress" value="not-started" checked>
                                                        <span class="radio-circle not-started"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task1-progress" value="in-progress">
                                                        <span class="radio-circle in-progress"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task1-progress" value="completed">
                                                        <span class="radio-circle completed"></span>
                                                    </label>
                                                </div>
                                                <button type="button" class="task-toggle">
                                                    <i class="ri-arrow-down-s-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="task-details">
                                            <p class="task-description">Create a modern and responsive frontend interface for the project.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="task-item">
                                    <div class="task-header" data-expanded="false">
                                        <div class="task-main">
                                            <div class="task-info">
                                                <div class="task-number">2.</div>
                                                <div class="task-name">Develop Shopping Page</div>
                                            </div>
                                            <div class="task-actions">
                                                <div class="task-progress">
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task2-progress" value="not-started">
                                                        <span class="radio-circle not-started"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task2-progress" value="in-progress" checked>
                                                        <span class="radio-circle in-progress"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task2-progress" value="completed">
                                                        <span class="radio-circle completed"></span>
                                                    </label>
                                                </div>
                                                <button type="button" class="task-toggle">
                                                    <i class="ri-arrow-down-s-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="task-details">
                                            <p class="task-description">Please develop the shopping page with proper implementation discussed in planning, please do it asap.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="task-item">
                                    <div class="task-header" data-expanded="false">
                                        <div class="task-main">
                                            <div class="task-info">
                                                <div class="task-number">3.</div>
                                                <div class="task-name">Develop Settings Page</div>
                                            </div>
                                            <div class="task-actions">
                                                <div class="task-progress">
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task3-progress" value="not-started" checked>
                                                        <span class="radio-circle not-started"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task3-progress" value="in-progress">
                                                        <span class="radio-circle in-progress"></span>
                                                    </label>
                                                    <label class="progress-radio">
                                                        <input type="radio" name="task3-progress" value="completed">
                                                        <span class="radio-circle completed"></span>
                                                    </label>
                                                </div>
                                                <button type="button" class="task-toggle">
                                                    <i class="ri-arrow-down-s-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="task-details">
                                            <p class="task-description">Implement the settings page with user preferences and configuration options.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="progress-legend">
                                <div class="legend-item">
                                    <span class="legend-circle not-started"></span>
                                    <span class="legend-text">Not Started</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-circle in-progress"></span>
                                    <span class="legend-text">Working</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-circle completed"></span>
                                    <span class="legend-text">Completed</span>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="save-btn">
                                    <i class="ri-save-line"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Shared Files Section -->
                <section id="shared-files" class="shared-files" style="display: none;">
                    <div class="files-header">
                        <h2>Shared Files</h2>
                        <button class="upload-btn" id="uploadBtn">
                            <i class="ri-upload-cloud-line"></i>
                            Upload File
                        </button>
                        <input type="file" id="fileInput" style="display: none;" multiple>
                    </div>
                    <div class="files-grid" id="filesGrid">
                        <!-- Sample file cards -->
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="ri-file-text-line"></i>
                            </div>
                            <div class="file-info">
                                <h3 class="file-name">Project Requirements.pdf</h3>
                                <p class="file-meta">Uploaded by John Doe</p>
                                <p class="file-meta">2 days ago • 2.5 MB</p>
                            </div>
                            <div class="file-actions">
                                <button class="action-btn" title="Download">
                                    <i class="ri-download-line"></i>
                                </button>
                                <button class="action-btn delete-btn" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="file-card">
                            <div class="file-icon">
                                <i class="ri-image-line"></i>
                            </div>
                            <div class="file-info">
                                <h3 class="file-name">Design Mockup.png</h3>
                                <p class="file-meta">Uploaded by Jane Smith</p>
                                <p class="file-meta">1 week ago • 4.8 MB</p>
                            </div>
                            <div class="file-actions">
                                <button class="action-btn" title="Download">
                                    <i class="ri-download-line"></i>
                                </button>
                                <button class="action-btn delete-btn" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Assign Task Section -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'guide'): ?>
                <section class="assign-task" style="display: none;">
                    <div class="details-section">
                        <h2 class="section-title">Assign New Task</h2>
                        <form class="task-assignment-form" id="task-assignment-form">
                            <div class="detail-group">
                                <div class="detail-label">Task Description</div>
                                <input type="text" class="task-title-input" placeholder="Please develop the shopping page with proper implementation" required>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label">Task Details</div>
                                <textarea class="task-description-input" rows="4" placeholder="Please describe the task in detail..." required></textarea>
                            </div>

                            <div class="detail-group">
                                <div class="detail-label">Assign to Member</div>
                                <select class="member-select" id="assign-member" required>
                                    <option value="">Select Team Member</option>
                                    <option value="1">Elon Musk</option>
                                    <option value="2">Steve Jobs</option>
                                    <option value="3">Bill Gates</option>
                                    <option value="4">Mark Zuckerberg</option>
                                </select>
                            </div>

                            

                            <div class="form-actions">
                                <button type="submit" class="save-btn">
                                    <i class="ri-save-line"></i>
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const teamId = urlParams.get('team_id') || <?php echo $team_id; ?>;
        const subjectId = urlParams.get('subject_id') || <?php echo $subject_id; ?>;

        // Team data from server
        const teamData = {
            id: <?php echo $team_id; ?>,
            name: <?php echo json_encode($team_data['team_name'] ?? ''); ?>,
            code: <?php echo json_encode($team_data['team_code'] ?? ''); ?>,
            leader: <?php echo json_encode($team_data['team_leader'] ?? 0); ?>,
            members: [
                <?php if (!empty($team_members)): 
                    foreach ($team_members as $member): ?>
                { 
                    id: <?php echo $member['student_id']; ?>, 
                    name: <?php echo json_encode($member['name'] ?? ''); ?> 
                },
                <?php endforeach; 
                endif; ?>
            ],
            stats: {
                memberCount: <?php echo count($team_members); ?>,
                progress: <?php echo $progress_percentage; ?>
            }
        };

        // Project data from server
        const projectData = {
            name: <?php echo json_encode($project_data['project_name'] ?? ''); ?>,
            description: <?php echo json_encode($project_data['abstract'] ?? ''); ?>,
            startDate: <?php echo json_encode($project_data['start_date'] ?? date('Y-m-d')); ?>,
            endDate: <?php echo json_encode($project_data['end_date'] ?? date('Y-m-d', strtotime('+3 months'))); ?>,
            phases: <?php 
                if ($project_data && isset($project_data['progress'])) {
                    echo $project_data['progress'];
                } else {
                    echo '[]';
                }
            ?>
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            // Update breadcrumb and basic info
            document.getElementById('team-name').textContent = teamData.name;
            document.getElementById('display-team-name').textContent = teamData.name;
            document.getElementById('display-team-code').textContent = teamData.code;
            
            // Update subject link
            document.getElementById('subject-link').href = `subject_view.php?subject_id=${subjectId}`;

            // Handle team leader change
            const leaderSelect = document.getElementById('team-leader');
            leaderSelect.addEventListener('change', (e) => {
                const newLeaderId = parseInt(e.target.value);
                
                // Show loading state
                const saveBtn = leaderSelect.nextElementSibling || document.createElement('button');
                if (!saveBtn.classList.contains('save-btn')) {
                    saveBtn.className = 'save-btn';
                    saveBtn.innerHTML = '<i class="ri-save-line"></i> Saving...';
                    leaderSelect.parentNode.appendChild(saveBtn);
                } else {
                    saveBtn.innerHTML = '<i class="ri-save-line"></i> Saving...';
                    saveBtn.disabled = true;
                }
                
                console.log('Updating team leader to:', newLeaderId, 'for team:', teamId);
                
                // Send AJAX request to update team leader
                fetch('update_team_leader.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        team_id: teamId,
                        leader_id: newLeaderId
                    }),
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json().catch(error => {
                        console.error('Error parsing JSON:', error);
                        throw new Error('Invalid JSON response');
                    });
                })
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        // Update local data
                        teamData.leader = newLeaderId;
                        
                        // Show success
                        saveBtn.innerHTML = '<i class="ri-check-line"></i> Saved!';
                        saveBtn.classList.add('saved');
                        saveBtn.disabled = false;
                        
                        setTimeout(() => {
                            saveBtn.style.display = 'none';
                        }, 2000);
                        
                        showNotification('Team leader updated successfully', 'success');
                    } else {
                        console.error('Error from server:', data.message);
                        saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                        saveBtn.classList.add('error');
                        saveBtn.disabled = false;
                        
                        // Reset the select to the previous value
                        leaderSelect.value = teamData.leader;
                        
                        showNotification('Failed to update team leader: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                    saveBtn.classList.add('error');
                    saveBtn.disabled = false;
                    
                    // Reset the select to the previous value
                    leaderSelect.value = teamData.leader;
                    
                    showNotification('An error occurred: ' + error.message, 'error');
                });
            });

            // Handle navigation
            const navSections = document.querySelectorAll('.nav-section');
            const contentSections = {
                'Team Details': document.querySelector('.team-details'),
                'Team Members': document.querySelector('.team-members'),
                'Project Details': document.querySelector('.project-details'),
                'Project Progress': document.querySelector('.project-progress'),
                'My Tasks': document.querySelector('.my-tasks'),
                'Assign Task': document.querySelector('.assign-task'),
                'Shared Files': document.querySelector('.shared-files')
            };

            navSections.forEach(section => {
                section.addEventListener('click', () => {
                    const sectionName = section.textContent.trim();
                    
                    // Update navigation active state
                    navSections.forEach(s => s.classList.remove('active'));
                    section.classList.add('active');
                    
                    // Show/hide content sections
                    Object.entries(contentSections).forEach(([name, element]) => {
                        if (element) {
                            element.style.display = name === sectionName ? 'block' : 'none';
                        }
                    });
                });
            });

            // Project Details Form Handling
            const projectForm = document.getElementById('project-form');
            
            if (projectForm) {
                // Handle form submission
                projectForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Show loading state
                    const saveBtn = projectForm.querySelector('.save-btn');
                    saveBtn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';
                    saveBtn.disabled = true;
                    
                    // Collect form data
                    const formData = {
                        team_id: teamId,
                        project_name: projectForm.querySelector('.project-title-input').value,
                        abstract: projectForm.querySelector('.project-description-input').value,
                        start_date: projectForm.querySelector('.timeline-input[type="date"]:first-of-type').value,
                        end_date: projectForm.querySelector('.timeline-input[type="date"]:last-of-type').value
                    };

                    console.log('Sending project update:', formData);

                    // Send to backend
                    fetch('update_project.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData),
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json().catch(error => {
                            console.error('Error parsing JSON:', error);
                            throw new Error('Invalid JSON response');
                        });
                    })
                    .then(data => {
                        console.log('Server response:', data);
                        if (data.success) {
                            // Update local data
                            projectData.name = formData.project_name;
                            projectData.description = formData.abstract;
                            projectData.startDate = formData.start_date;
                            projectData.endDate = formData.end_date;
                            
                            showSaveSuccess(saveBtn);
                            showNotification(data.message, 'success');
                        } else {
                            console.error('Error from server:', data.message);
                            saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                            saveBtn.classList.add('error');
                            setTimeout(() => {
                                saveBtn.innerHTML = '<i class="ri-save-line"></i> Save Changes';
                                saveBtn.classList.remove('error');
                                saveBtn.disabled = false;
                            }, 2000);
                            
                            showNotification(data.message || 'Failed to save project details', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                        saveBtn.classList.add('error');
                        setTimeout(() => {
                            saveBtn.innerHTML = '<i class="ri-save-line"></i> Save Changes';
                            saveBtn.classList.remove('error');
                            saveBtn.disabled = false;
                        }, 2000);
                        
                        showNotification('An error occurred: ' + error.message, 'error');
                    });
                });
            }

            // Initialize Project Progress Handling
            const progressForm = document.getElementById('progress-form');
            const phasesList = document.querySelector('.phases-list');
            const addPhaseBtn = document.querySelector('.add-phase-btn');

            if (progressForm && phasesList && addPhaseBtn) {
                // Toggle phase status
                phasesList.addEventListener('click', (e) => {
                    if (e.target.closest('.phase-status')) {
                        const statusBtn = e.target.closest('.phase-status');
                        statusBtn.classList.toggle('completed');
                        statusBtn.classList.toggle('pending');
                        
                        if (statusBtn.classList.contains('completed')) {
                            statusBtn.innerHTML = '<i class="ri-check-line"></i>';
                        } else {
                            statusBtn.innerHTML = '<i class="ri-close-line"></i>';
                        }

                        // Update overall progress
                        updateOverallProgress();
                    }
                });

                // Add new phase
                addPhaseBtn.addEventListener('click', () => {
                    const phaseCount = phasesList.children.length + 1;
                    const phaseItem = document.createElement('div');
                    phaseItem.className = 'phase-item';
                    phaseItem.innerHTML = `
                        <div class="phase-content">
                            <input type="text" class="phase-name" value="${phaseCount}. New Phase" required>
                            <button type="button" class="phase-status pending">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    `;
                    phasesList.appendChild(phaseItem);
                    updateOverallProgress();
                });

                // Update overall progress
                function updateOverallProgress() {
                    const progressPercentage = calculateProgressPercentage();
                    document.querySelector('.progress-percentage').textContent = `${progressPercentage}%`;
                    document.querySelector('.overall-progress-bar .progress-fill').style.width = `${progressPercentage}%`;
                }

                // Calculate progress percentage from phases
                function calculateProgressPercentage() {
                    const totalPhases = phasesList.children.length;
                    const completedPhases = phasesList.querySelectorAll('.phase-status.completed').length;
                    return Math.round((completedPhases / totalPhases) * 100);
                }

                // Handle progress form submission
                progressForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Show loading state
                    const saveBtn = progressForm.querySelector('.save-btn');
                    saveBtn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';
                    saveBtn.disabled = true;
                    
                    // Collect form data
                    const phases = Array.from(phasesList.children).map((phase, index) => ({
                        phase_no: index + 1,
                        phase_name: phase.querySelector('.phase-name').value,
                        is_completed: phase.querySelector('.phase-status').classList.contains('completed')
                    }));
                    
                    const formData = {
                        team_id: teamId,
                        progress: phases
                    };

                    console.log('Sending progress update:', formData);

                    // Send to backend
                    fetch('update_project_progress.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData),
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json().catch(error => {
                            console.error('Error parsing JSON:', error);
                            throw new Error('Invalid JSON response');
                        });
                    })
                    .then(data => {
                        console.log('Server response:', data);
                        if (data.success) {
                            // Update the overall progress display
                            const progressPercentage = data.progress_percentage || calculateProgressPercentage();
                            document.querySelector('.progress-percentage').textContent = `${progressPercentage}%`;
                            document.querySelector('.overall-progress-bar .progress-fill').style.width = `${progressPercentage}%`;
                            
                            // Update project data
                            projectData.phases = phases;
                            
                            // Update the stat card on team details page
                            const progressStatValue = document.querySelector('.stat-card .stat-value');
                            if (progressStatValue && progressStatValue.nextElementSibling && 
                                progressStatValue.nextElementSibling.textContent.includes('Project Progress')) {
                                progressStatValue.textContent = `${progressPercentage}%`;
                            }
                            
                            showSaveSuccess(saveBtn);
                            showNotification(data.message || 'Progress updated successfully', 'success');
                        } else {
                            console.error('Error from server:', data.message);
                            saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                            saveBtn.classList.add('error');
                            setTimeout(() => {
                                saveBtn.innerHTML = '<i class="ri-save-line"></i> Save Changes';
                                saveBtn.classList.remove('error');
                                saveBtn.disabled = false;
                            }, 2000);
                            
                            showNotification(data.message || 'Failed to save progress', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        saveBtn.innerHTML = '<i class="ri-error-warning-line"></i> Failed';
                        saveBtn.classList.add('error');
                        setTimeout(() => {
                            saveBtn.innerHTML = '<i class="ri-save-line"></i> Save Changes';
                            saveBtn.classList.remove('error');
                            saveBtn.disabled = false;
                        }, 2000);
                        
                        showNotification('An error occurred: ' + error.message, 'error');
                    });
                });
            }

            // Shared Files functionality
            const uploadBtn = document.getElementById('uploadBtn');
            const fileInput = document.getElementById('fileInput');
            const filesGrid = document.getElementById('filesGrid');

            if (uploadBtn && fileInput && filesGrid) {
                uploadBtn.addEventListener('click', () => {
                    fileInput.click();
                });

                fileInput.addEventListener('change', (e) => {
                    const files = e.target.files;
                    if (files.length > 0) {
                        const formData = new FormData();
                        formData.append('team_id', teamId);
                        
                        for (let i = 0; i < files.length; i++) {
                            formData.append('files[]', files[i]);
                        }
                        
                        // Upload files to server
                        fetch('upload_file.php', {
                            method: 'POST',
                            body: formData,
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                for (let file of data.files) {
                                    addFileCard(file);
                                }
                                showNotification('Files uploaded successfully', 'success');
                            } else {
                                showNotification('Failed to upload files', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred', 'error');
                        });
                    }
                });

                // Add file card to the grid
                function addFileCard(file) {
                    const fileCard = document.createElement('div');
                    fileCard.className = 'file-card';
                    fileCard.dataset.fileId = file.id;
                    
                    const fileIcon = getFileIcon(file.type);
                    
                    fileCard.innerHTML = `
                        <div class="file-icon">
                            <i class="ri-${fileIcon}"></i>
                        </div>
                        <div class="file-info">
                            <h3 class="file-name">${file.name}</h3>
                            <p class="file-meta">Uploaded by ${file.uploaded_by}</p>
                            <p class="file-meta">${file.upload_date} • ${file.size}</p>
                        </div>
                        <div class="file-actions">
                            <button class="action-btn download-btn" title="Download" data-url="${file.url}">
                                <i class="ri-download-line"></i>
                            </button>
                            <button class="action-btn delete-btn" title="Delete" data-id="${file.id}">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    `;

                    // Add event listeners for actions
                    const downloadBtn = fileCard.querySelector('.download-btn');
                    downloadBtn.addEventListener('click', () => {
                        window.location.href = downloadBtn.dataset.url;
                    });
                    
                    const deleteBtn = fileCard.querySelector('.delete-btn');
                    deleteBtn.addEventListener('click', () => {
                        const fileId = deleteBtn.dataset.id;
                        
                        // Delete file from server
                        fetch('delete_file.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                file_id: fileId
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fileCard.remove();
                                showNotification('File deleted', 'success');
                            } else {
                                showNotification('Failed to delete file', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred', 'error');
                        });
                    });

                    filesGrid.insertBefore(fileCard, filesGrid.firstChild);
                }
            }

            // Helper function to get appropriate icon based on file type
            function getFileIcon(fileType) {
                if (fileType.includes('image')) return 'image-line';
                if (fileType.includes('pdf')) return 'file-pdf-line';
                if (fileType.includes('word')) return 'file-word-line';
                if (fileType.includes('excel')) return 'file-excel-line';
                if (fileType.includes('zip') || fileType.includes('rar')) return 'file-zip-line';
                if (fileType.includes('audio')) return 'file-music-line';
                if (fileType.includes('video')) return 'film-line';
                return 'file-text-line';
            }

            // Helper function to format file size
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }

            // Add delete functionality to existing files
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const fileCard = btn.closest('.file-card');
                    const fileId = btn.dataset.id;
                    
                    if (fileId) {
                        // Delete file from server
                        fetch('delete_file.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                file_id: fileId
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                fileCard.remove();
                                showNotification('File deleted', 'success');
                            } else {
                                showNotification('Failed to delete file', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred', 'error');
                        });
                    } else {
                        fileCard.remove();
                    }
                });
            });

            // My Tasks Functionality
            const tasksForm = document.getElementById('tasks-form');
            const tasksList = document.querySelector('.tasks-list');

            if (tasksForm && tasksList) {
                // Toggle task details
                document.querySelectorAll('.task-toggle').forEach(toggle => {
                    toggle.addEventListener('click', () => {
                        const taskHeader = toggle.closest('.task-header');
                        const isExpanded = taskHeader.getAttribute('data-expanded') === 'true';
                        taskHeader.setAttribute('data-expanded', !isExpanded);
                        toggle.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
                    });
                });

                // Handle form submission
                tasksForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Collect form data
                    const tasks = Array.from(tasksList.children).map(task => {
                        const taskId = task.dataset.taskId;
                        const status = task.querySelector('input[type="radio"]:checked').value;
                        
                        return {
                            task_id: taskId,
                            status: status
                        };
                    });
                    
                    // Send to backend
                    fetch('update_tasks.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            team_id: teamId,
                            tasks: tasks
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSaveSuccess(tasksForm.querySelector('.save-btn'));
                        } else {
                            showNotification('Failed to update tasks', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred', 'error');
                    });
                });
            }

            // Assign Task Functionality
            const assignTaskForm = document.getElementById('task-assignment-form');
            
            if (assignTaskForm) {
                // Set today's date as the minimum for due date input
                const dueDateInput = assignTaskForm.querySelector('.due-date-input');
                if (dueDateInput) {
                    const today = new Date().toISOString().split('T')[0];
                    dueDateInput.setAttribute('min', today);
                    dueDateInput.value = today;
                }
                
                // Handle form submission
                assignTaskForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    // Collect form data
                    const taskTitle = assignTaskForm.querySelector('.task-title-input').value;
                    const taskDescription = assignTaskForm.querySelector('.task-description-input').value;
                    const assignedTo = assignTaskForm.querySelector('#assign-member').value;
                    const dueDate = dueDateInput ? dueDateInput.value : '';
                    
                    const formData = {
                        team_id: teamId,
                        title: taskTitle,
                        description: taskDescription,
                        assigned_to: assignedTo,
                        due_date: dueDate
                    };
                    
                    // Send to backend
                    fetch('assign_task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSaveSuccess(assignTaskForm.querySelector('.save-btn'));
                            assignTaskForm.reset();
                            if (dueDateInput) {
                                dueDateInput.value = today;
                            }
                        } else {
                            showNotification('Failed to assign task', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred', 'error');
                    });
                });
            }
            
            // Helper function to show save success animation
            function showSaveSuccess(saveBtn) {
                if (!saveBtn) return;
                
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="ri-check-line"></i> Saved!';
                saveBtn.classList.add('saved');
                saveBtn.disabled = false;
                
                setTimeout(() => {
                    saveBtn.innerHTML = originalText;
                    saveBtn.classList.remove('saved');
                }, 2000);
            }
            
            // Helper function to show notification
            function showNotification(message, type = 'success') {
                // Remove any existing notifications
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => notification.remove());
                
                // Create new notification
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <i class="ri-${type === 'success' ? 'check-line' : 'error-warning-line'}"></i>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(notification);
                
                // Auto remove after 3 seconds
                setTimeout(() => {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
        });
    </script>

<?php require_once 'includes/footer.php'; ?>