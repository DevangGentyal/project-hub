<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming requests for debugging
error_log("Team leader update request received");

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Debug - log the received data
error_log("Received leader update request: " . print_r($data, true));

// Validate input
if (!isset($data['team_id']) || !isset($data['leader_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

include 'includes/db_connect.php';

$team_id = intval($data['team_id']);
$leader_id = intval($data['leader_id']);
$guide_id = intval($_SESSION['user_id']);

// Debug log the parameters
error_log("Processing update - Team ID: $team_id, Leader ID: $leader_id, Guide ID: $guide_id");

// Check if team exists and belongs to this guide
$check_query = "SELECT * FROM teams WHERE team_id = ? AND guide_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $team_id, $guide_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Team not found or not authorized: Team ID $team_id, Guide ID $guide_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Team not found or not authorized']);
    exit();
}

// Get the team data
$team_data = $result->fetch_assoc();
error_log("Team data: " . print_r($team_data, true));

// Verify that the assigned leader is a member of the team
if ($leader_id > 0) {
    // Get team_member_ids from the team
    $team_member_ids = [];
    
    if (!empty($team_data['team_member_ids'])) {
        // Try to decode as JSON first
        $decoded_members = json_decode($team_data['team_member_ids'], true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_members)) {
            $team_member_ids = $decoded_members;
            error_log("Decoded team_member_ids as JSON: " . print_r($team_member_ids, true));
        } else {
            // Fall back to comma-separated if not valid JSON
            $team_member_ids = array_map('intval', explode(',', $team_data['team_member_ids']));
            error_log("Parsed team_member_ids as comma-separated: " . print_r($team_member_ids, true));
        }
    }
    
    // Check if leader_id is in team_member_ids
    if (!in_array($leader_id, $team_member_ids)) {
        error_log("Leader ID $leader_id not found in team members. Adding to team.");
        
        // Try to check if the student exists
        $check_student_query = "SELECT student_id FROM students WHERE student_id = ?";
        $stmt = $conn->prepare($check_student_query);
        $stmt->bind_param("i", $leader_id);
        $stmt->execute();
        $student_result = $stmt->get_result();
        
        if ($student_result->num_rows === 0) {
            error_log("Student ID $leader_id not found in students table");
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Selected leader is not a valid student']);
            exit();
        }
        
        // If the student exists but is not in team_member_ids, add them
        $team_member_ids[] = $leader_id;
        
        // Encode team_member_ids as JSON
        $member_ids_json = json_encode($team_member_ids);
        error_log("New team_member_ids JSON: $member_ids_json");
        
        // Update team_member_ids
        $update_members_query = "UPDATE teams SET team_member_ids = ? WHERE team_id = ?";
        $stmt = $conn->prepare($update_members_query);
        $stmt->bind_param("si", $member_ids_json, $team_id);
        
        if (!$stmt->execute()) {
            error_log("Error updating team_member_ids: " . $stmt->error);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating team members: ' . $stmt->error]);
            exit();
        }
        
        error_log("Successfully added leader to team members");
    }
}

// Update the team leader
$update_query = "UPDATE teams SET team_leader = ? WHERE team_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $leader_id, $team_id);

if ($stmt->execute()) {
    error_log("Team leader updated successfully. Team ID: $team_id, Leader ID: $leader_id");
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Team leader updated successfully']);
} else {
    error_log("Database error updating team leader: " . $stmt->error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 