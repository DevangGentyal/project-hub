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
error_log("Received project progress update request: " . print_r($data, true));

// Validate input
if (!isset($data['team_id']) || !isset($data['progress']) || !is_array($data['progress'])) {
    send_error(400, 'Missing required parameters or invalid progress format');
}

include 'includes/db_connect.php';

$team_id = intval($data['team_id']);
$progress = $data['progress'];
$guide_id = intval($_SESSION['user_id']);

// Verify the team belongs to this guide
$check_query = "SELECT t.team_id 
                FROM teams t 
                JOIN subjects s ON t.subject_id = s.subject_id 
                WHERE t.team_id = ? AND s.guide_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $team_id, $guide_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    send_error(403, 'You do not have permission to update this project');
}

// First check if project exists for this team
$check_project = $conn->prepare("SELECT project_id FROM projects WHERE team_id = ?");
$check_project->bind_param("i", $team_id);
$check_project->execute();
$project_result = $check_project->get_result();

if ($project_result->num_rows > 0) {
    // Project exists, update it
    $project_row = $project_result->fetch_assoc();
    $project_id = $project_row['project_id'];
    
    // Validate and clean up the progress data
    $progress_data = [];
    foreach ($progress as $index => $phase) {
        if (!isset($phase['phase_name']) || !isset($phase['is_completed'])) {
            continue; // Skip invalid phases
        }
        
        $progress_data[] = [
            'phase_no' => $index + 1, 
            'phase_name' => trim($phase['phase_name']),
            'is_completed' => (bool)$phase['is_completed']
        ];
    }
    
    // Encode the progress data
    $progress_json = json_encode($progress_data);
    
    // Update the project progress
    $update_query = "UPDATE projects SET 
                    progress = ?,
                    last_updated = NOW()
                    WHERE project_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $progress_json, $project_id);
    
    if ($stmt->execute()) {
        // Calculate the progress percentage
        $total_phases = count($progress_data);
        $completed_phases = 0;
        foreach ($progress_data as $phase) {
            if ($phase['is_completed']) {
                $completed_phases++;
            }
        }
        $progress_percentage = $total_phases > 0 ? round(($completed_phases / $total_phases) * 100) : 0;
        
        // Update the team progress field
        $update_team = $conn->prepare("UPDATE teams SET progress = ? WHERE team_id = ?");
        $update_team->bind_param("ii", $progress_percentage, $team_id);
        $update_team->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Project progress updated successfully',
            'progress_percentage' => $progress_percentage
        ]);
    } else {
        send_error(500, 'Error updating project progress: ' . $stmt->error);
    }
} else {
    send_error(404, 'Project not found for this team');
}

$stmt->close();
$conn->close();
?> 