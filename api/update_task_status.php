<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Collect and sanitize input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

// Validate required fields
$required_fields = ['task_id', 'status'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Get values and sanitize
$task_id = intval($input['task_id']);
$status = trim($input['status']);
$user_id = $_SESSION['user_id'];

// Validate status
$valid_statuses = ['not-started', 'in-progress', 'completed', 'blocked'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid status value. Must be one of: ' . implode(', ', $valid_statuses)
    ]);
    exit;
}

// Check if task exists and user has permission to update it
$task_check = $conn->prepare("
    SELECT t.* FROM tasks t
    JOIN teams tm ON t.team_id = tm.team_id
    WHERE t.task_id = ? AND (
        t.assigned_to = ? OR 
        t.assigned_by = ? OR 
        tm.team_leader = ? OR 
        FIND_IN_SET(?, tm.team_member_ids) OR
        tm.guide_id = ?
    )
");
$task_check->bind_param('iiiisi', $task_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$task_check->execute();
$task_result = $task_check->get_result();

if ($task_result->num_rows === 0) {
    // Check if task exists at all
    $task_exists = $conn->prepare("SELECT task_id FROM tasks WHERE task_id = ?");
    $task_exists->bind_param('i', $task_id);
    $task_exists->execute();
    
    if ($task_exists->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this task']);
    }
    exit;
}

// Get current task data
$task_data = $task_result->fetch_assoc();

// Add completion date if status is changing to completed
$completion_date = null;
$completion_sql = "";
if ($status === 'completed' && $task_data['status'] !== 'completed') {
    $completion_date = date('Y-m-d H:i:s');
    $completion_sql = ", completion_date = ?";
}

// Update task
$update_task = $conn->prepare("UPDATE tasks SET status = ?" . $completion_sql . " WHERE task_id = ?");

if ($completion_date) {
    $update_task->bind_param('ssi', $status, $completion_date, $task_id);
} else {
    $update_task->bind_param('si', $status, $task_id);
}

if ($update_task->execute()) {
    // Get updated task details
    $task_query = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
    $task_query->bind_param('i', $task_id);
    $task_query->execute();
    $updated_task = $task_query->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Task status updated successfully',
        'task' => $updated_task
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update task status', 
        'error' => $conn->error
    ]);
}

$conn->close();
?> 