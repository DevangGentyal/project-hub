<?php
// Join Team Page - Where students join a team for a subject
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = intval($_SESSION['user_id']);
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if ($subject_id <= 0) {
    header("Location: student-dashboard.php");
    exit();
}

include 'includes/db_connect.php';

// Get subject details
$subject_query = "SELECT * FROM subjects WHERE subject_id = ?";
$stmt = $conn->prepare($subject_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject_result = $stmt->get_result();
$subject = $subject_result->fetch_assoc();
$stmt->close();

if (!$subject) {
    header("Location: student-dashboard.php");
    exit();
}

// Check if student is already in a team for this subject
// First get all teams for this subject
$teams_query = "SELECT team_id FROM teams WHERE subject_id = ?";
$stmt = $conn->prepare($teams_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$teams_result = $stmt->get_result();
$team_ids = [];

while ($team = $teams_result->fetch_assoc()) {
    $team_ids[] = $team['team_id'];
}
$stmt->close();

// Now check if student is in any of these teams
$student_query = "SELECT team_ids FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

$already_in_team = false;
$existing_team_id = null;

if ($student && !empty($student['team_ids'])) {
    $student_team_ids = json_decode($student['team_ids'], true);
    if (is_array($student_team_ids)) {
        foreach ($team_ids as $team_id) {
            if (in_array($team_id, $student_team_ids)) {
                $already_in_team = true;
                $existing_team_id = $team_id;
                break;
            }
        }
    }
}

// If already in a team, redirect to team view
if ($already_in_team && $existing_team_id) {
    header("Location: team_view.php?team_id=$existing_team_id&subject_id=$subject_id");
    exit();
}

// Process form submission for joining a team
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_code'])) {
    $team_code = trim($_POST['team_code']);
    
    // Validate team code
    if (empty($team_code)) {
        $error_message = 'Please enter a team code.';
    } else {
        // Check if team exists
        $team_query = "SELECT * FROM teams WHERE team_code = ? AND subject_id = ?";
        $stmt = $conn->prepare($team_query);
        $stmt->bind_param("si", $team_code, $subject_id);
        $stmt->execute();
        $team_result = $stmt->get_result();
        $team = $team_result->fetch_assoc();
        $stmt->close();
        
        if (!$team) {
            $error_message = 'Invalid team code. Please try again.';
        } else {
            $team_id = $team['team_id'];
            
            // Add student to team
            if (empty($student['team_ids'])) {
                $new_team_ids = [$team_id];
            } else {
                $existing_team_ids = json_decode($student['team_ids'], true) ?: [];
                if (!is_array($existing_team_ids)) {
                    $existing_team_ids = [];
                }
                
                // Check if already in this team
                if (in_array($team_id, $existing_team_ids)) {
                    header("Location: team_view.php?team_id=$team_id&subject_id=$subject_id");
                    exit();
                }
                
                $new_team_ids = $existing_team_ids;
                $new_team_ids[] = $team_id;
            }
            
            // Update student record
            $new_team_ids_json = json_encode($new_team_ids);
            $update_query = "UPDATE students SET team_ids = ? WHERE student_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_team_ids_json, $student_id);
            $success = $stmt->execute();
            $stmt->close();
            
            if ($success) {
                // Also update team's member list
                $team_members = json_decode($team['team_member_ids'] ?? '[]', true) ?: [];
                if (!in_array($student_id, $team_members)) {
                    $team_members[] = $student_id;
                    $team_members_json = json_encode($team_members);
                    
                    $update_team_query = "UPDATE teams SET team_member_ids = ? WHERE team_id = ?";
                    $stmt = $conn->prepare($update_team_query);
                    $stmt->bind_param("si", $team_members_json, $team_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Redirect to team view
                header("Location: team_view.php?team_id=$team_id&subject_id=$subject_id");
                exit();
            } else {
                $error_message = 'Failed to join team. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/student_dashboard.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="assets/css/join_team.css">

<main class="main-content">
    <div class="dashboard-header">
        <h1>Join a Team for <?php echo htmlspecialchars($subject['subject_name']); ?></h1>
        <a href="student-dashboard.php" class="back-btn">Back to Dashboard</a>
    </div>
    
    <div class="join-team-container">
        <div class="join-team-card">
            <h2>Enter Team Code</h2>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="post" class="join-team-form">
                <div class="form-group">
                    <label for="team-code">Team Code</label>
                    <input type="text" id="team-code" name="team_code" placeholder="Enter team code" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="join-btn">Join Team</button>
                </div>
            </form>
            
            <div class="join-team-info">
                <p>Contact your team leader or guide to get the team code.</p>
                <p>You can only join one team per subject.</p>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?> 