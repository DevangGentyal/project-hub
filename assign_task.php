<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input
if (!isset($data['team_id']) || !isset($data['title']) || !isset($data['assigned_to'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

include 'includes/db_connect.php';

$team_id = intval($data['team_id']);
$title = trim($data['title']);
$description = isset($data['description']) ? trim($data['description']) : '';
$assigned_to = intval($data['assigned_to']);
$due_date = isset($data['due_date']) && !empty($data['due_date']) ? $data['due_date'] : date('Y-m-d', strtotime('+1 week'));
$guide_id = intval($_SESSION['user_id']);
$assigned_by = $guide_id;
$status = 'not-started';

// Check if team exists and belongs to this guide
$check_query = "SELECT t.* FROM teams t WHERE t.team_id = ? AND t.guide_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $team_id, $guide_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Team not found or not authorized']);
    exit();
}

// Check if assigned_to is a valid member of the team
$check_member_query = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($check_member_query);
$stmt->bind_param("i", $assigned_to);
$stmt->execute();
$member_result = $stmt->get_result();

if ($member_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Assigned team member not found']);
    exit();
}

// Get the team member's name and other details
$member_data = $member_result->fetch_assoc();
$member_name = $member_data['name'] ?? 'Team Member';
$member_email = $member_data['email'] ?? '';

// Insert the new task
$insert_query = "INSERT INTO tasks (team_id, title, description, assigned_to, assigned_to_name, assigned_by, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ississs", $team_id, $title, $description, $assigned_to, $member_name, $assigned_by, $due_date, $status);

if ($stmt->execute()) {
    $task_id = $conn->insert_id;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Task assigned successfully',
        'task_id' => $task_id
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 