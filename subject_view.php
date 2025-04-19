<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$guide_id = intval($_SESSION['user_id']);

include 'includes/db_connect.php';

// Get subject ID from URL
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
if ($subject_id <= 0) {
    // Redirect or show error if invalid subject ID
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
<link rel="stylesheet" href="assets/css/guide_dashboard.css">
<link rel="stylesheet" href="assets/css/subject_view.css">

<!-- Main Content -->
<main class="main-content">
    <div class="subject-header">
        <div class="breadcrumb">
            <a href="guide-dashboard.php" class="nav-link">
                Back to Dashboard
            </a>
            <span>/</span>
            <span id="subject-name"><?php echo htmlspecialchars($subject_name); ?></span>
        </div>
        <button class="create-team-btn" id="create-team">
            <i class="ri-add-line"></i>
            Create Team
        </button>
    </div>

    <div class="team-grid">
        <?php
        // Fetch teams for this subject
        // Fetch teams that have this subject in their subjects array
// Using JSON_CONTAINS function to properly search within JSON arrays
        $teams_query = "SELECT team_id, team_name, team_code, subject_id 
FROM teams
WHERE guide_id = ? 
AND subject_id = ?
ORDER BY team_name ASC";

        // Prepare the subject ID as a JSON value to search for
        $subject_json = json_encode($subject_id);

        $stmt = $conn->prepare($teams_query);
        $stmt->bind_param("is", $guide_id, $subject_json);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if teams exist
        if ($result->num_rows > 0) {
            while ($team = $result->fetch_assoc()) {
                // Create team card for each team
                ?>
                <div class="team-card"
                    onclick="window.location.href='team_view.php?team_id=<?php echo htmlspecialchars($team['team_id']); ?>&subject_id=<?php echo urlencode($subject_id); ?>'">

                    <h3 class="team-name"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                    <p class="team-code">#<?php echo htmlspecialchars($team['team_code']); ?></p>
                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-label">Progress</span>
                            <span class="progress-value">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            // No teams found
            echo '<div class="no-teams-message">No teams created yet for this subject.</div>';
        }
        $stmt->close();
        ?>
    </div>
</main>

<!-- Create Team Modal -->
<div class="modal" id="create-team-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New Team</h3>
            <button class="modal-close">&times;</button>
        </div>

        <form id="create-team-form" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <div class="form-group">
                <label for="team-name" class="form-label">Team Name</label>
                <input type="text" id="team-name" name="team_name" class="form-control" required>
            </div>

            <div class="generated-code" style="display: none;">
                <span class="generated-code-text" id="team-code"></span>
                <button type="button" class="copy-btn" id="copy-code">
                    <i class="ri-file-copy-line"></i>
                </button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Team</button>
            </div>
        </form>
    </div>
</div>

<!-- Copy Success Message -->
<div class="copy-success" id="copy-success">
    Code copied to clipboard!
</div>

<script>
    // Modal handling
    const createTeamBtn = document.getElementById('create-team');
    const createTeamModal = document.getElementById('create-team-modal');
    const modalClose = document.querySelector('.modal-close');
    const teamNameInput = document.getElementById('team-name');
    const teamCodeElement = document.getElementById('team-code');
    const copyBtn = document.getElementById('copy-code');
    const copySuccess = document.getElementById('copy-success');
    const teamForm = document.getElementById('create-team-form');

    // Generate random team code
    function generateTeamCode() {
        const timestamp = Date.now().toString().slice(-3);
        const randomNum = Math.floor(Math.random() * 90) + 10;
        return `${timestamp}${randomNum}`;
    }

    createTeamBtn.addEventListener('click', () => {
        createTeamModal.style.display = 'block';
        teamNameInput.value = '';
        teamCodeElement.textContent = '';
        document.querySelector('.generated-code').style.display = 'none';
    });

    modalClose.addEventListener('click', () => {
        createTeamModal.style.display = 'none';
    });

    // Generate code after team name input
    teamNameInput.addEventListener('keyup', () => {
        if (teamNameInput.value.length > 0) {
            const generatedCode = generateTeamCode();
            teamCodeElement.textContent = generatedCode;
            document.querySelector('.generated-code').style.display = 'flex';
        } else {
            document.querySelector('.generated-code').style.display = 'none';
        }
    });

    // Form submission
    teamForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const teamName = teamNameInput.value;
        const teamCode = teamCodeElement.textContent;

        if (!teamName || !teamCode) {
            alert('Please enter a team name');
            return;
        }

        // Create form data for AJAX request
        const formData = new FormData();
        formData.append('team_name', teamName);
        formData.append('team_code', teamCode);
        // formData.append('ajax', 1);

        formData.append('ajax', '1');
        // Send AJAX request
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {

                // Reload page or update UI
                alert(`Team '${data.team_name}' created successfully with code: ${data.team_code}`);
                window.location.reload();

            })
            .catch(error => {
                console.error('Error:', error);
            });

        createTeamModal.style.display = 'none';
    });
</script>

<?php require_once 'includes/footer.php'; ?>