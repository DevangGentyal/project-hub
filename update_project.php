<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send error response
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_error(401, 'User not logged in');
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Debug - log the received data
error_log("Received project update request: " . print_r($data, true));

// Validate input
if (!isset($data['team_id']) || 
    !isset($data['project_name']) || 
    !isset($data['abstract']) || 
    !isset($data['start_date']) || 
    !isset($data['end_date'])) {
    send_error(400, 'Missing required parameters');
}

include 'includes/db_connect.php';

$team_id = intval($data['team_id']);
$project_name = trim($conn->real_escape_string($data['project_name']));
$abstract = trim($conn->real_escape_string($data['abstract']));
$start_date = $conn->real_escape_string($data['start_date']);
$end_date = $conn->real_escape_string($data['end_date']);
$guide_id = intval($_SESSION['user_id']);

// Check if start_date is valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
    send_error(400, 'Invalid start date format. Required format: YYYY-MM-DD');
}

// Check if end_date is valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    send_error(400, 'Invalid end date format. Required format: YYYY-MM-DD');
}

// // Verify the team belongs to this guide
// $check_query = "SELECT t.team_id 
//                 FROM teams t 
//                 JOIN subjects s ON t.subject_id = s.subject_id 
//                 WHERE t.team_id = ? AND s.guide_id = ?";
// $stmt = $conn->prepare($check_query);
// $stmt->bind_param("ii", $team_id, $guide_id);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($result->num_rows === 0) {
//     send_error(403, 'You do not have permission to update this project');
// }

// First check if project exists for this team
$check_project = $conn->prepare("SELECT project_id FROM projects WHERE team_id = ?");
$check_project->bind_param("i", $team_id);
$check_project->execute();
$project_result = $check_project->get_result();

if ($project_result->num_rows > 0) {
    // Project exists, update it
    $project_row = $project_result->fetch_assoc();
    $project_id = $project_row['project_id'];
    
    // Create timeline JSON
    $timeline = json_encode([
        'start_date' => $start_date,
        'due_date' => $end_date
    ]);
    
    $update_query = "UPDATE projects SET 
                    project_name = ?, 
                    abstract = ?, 
                    timeline = ?,
                    last_updated = NOW()
                    WHERE project_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $project_name, $abstract, $timeline, $project_id);
    
    if ($stmt->execute()) {
        // Get the updated project
        $get_project = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
        $get_project->bind_param("i", $project_id);
        $get_project->execute();
        $updated_project = $get_project->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Project updated successfully',
            'project' => $updated_project
        ]);
    } else {
        send_error(500, 'Error updating project: ' . $stmt->error);
    }
} else {
    // Project doesn't exist, create it
    $timeline = json_encode([
        'start_date' => $start_date,
        'due_date' => $end_date
    ]);
    
    $initial_progress = json_encode([
        ["phase_no" => 1, "phase_name" => "Project Topic Finalization", "is_completed" => false],
        ["phase_no" => 2, "phase_name" => "Ideation & Planning", "is_completed" => false],
        ["phase_no" => 3, "phase_name" => "Design & Structuring", "is_completed" => false],
        ["phase_no" => 4, "phase_name" => "Development & Execution", "is_completed" => false],
        ["phase_no" => 5, "phase_name" => "Testing and Deployment", "is_completed" => false],
        ["phase_no" => 6, "phase_name" => "Final Submission", "is_completed" => false],
    ]);
    
    $insert_query = "INSERT INTO projects (project_name, abstract, team_id, timeline, progress, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssiss", $project_name, $abstract, $team_id, $timeline, $initial_progress);
    
    if ($stmt->execute()) {
        $project_id = $conn->insert_id;
        
        // Get the new project
        $get_project = $conn->prepare("SELECT * FROM projects WHERE project_id = ?");
        $get_project->bind_param("i", $project_id);
        $get_project->execute();
        $new_project = $get_project->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Project created successfully',
            'project' => $new_project
        ]);
    } else {
        send_error(500, 'Error creating project: ' . $stmt->error);
    }
}

$stmt->close();
$conn->close();
?> 