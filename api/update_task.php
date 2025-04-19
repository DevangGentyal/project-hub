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

// Get task ID and validate it
if (!isset($_POST['task_id']) || empty($_POST['task_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = intval($_POST['task_id']);
$user_id = $_SESSION['user_id'];

// Get the task and check permissions
$task_query = $conn->prepare("
    SELECT t.*, tm.team_leader, tm.team_member_ids, tm.guide_id
    FROM tasks t
    JOIN teams tm ON t.team_id = tm.team_id
    WHERE t.task_id = ?
");
$task_query->bind_param('i', $task_id);
$task_query->execute();
$result = $task_query->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

$task = $result->fetch_assoc();
$team_leader = $task['team_leader'];
$team_members = explode(',', $task['team_member_ids']);
$guide_id = $task['guide_id'];

// Only team leader, task assigned_to user (if it's assigned), and guide can edit tasks
$can_edit = ($team_leader == $user_id || 
             ($task['assigned_to'] == $user_id) || 
             $guide_id == $user_id);

if (!$can_edit) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to update this task']);
    exit;
}

// Build update query based on provided fields
$updateFields = [];
$types = '';
$params = [];

// Optional fields that can be updated
$allowedFields = [
    'title' => 's', 
    'description' => 's', 
    'status' => 's',
    'priority' => 's',
    'assigned_to' => 'i',
    'due_date' => 's'
];

foreach ($allowedFields as $field => $type) {
    if (isset($_POST[$field]) && $_POST[$field] !== '') {
        // Special handling for dates
        if ($field === 'due_date') {
            // Convert date to MySQL format if needed
            $date = date('Y-m-d H:i:s', strtotime($_POST[$field]));
            $updateFields[] = "$field = ?";
            $types .= 's';
            $params[] = $date;
        } else {
            $updateFields[] = "$field = ?";
            $types .= $type;
            $params[] = $_POST[$field];
        }
    }
}

// If updating assigned_to, update assigned_by as well
if (isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '') {
    $updateFields[] = "assigned_by = ?";
    $types .= 'i';
    $params[] = $user_id;
}

// Add last_updated field
$updateFields[] = "last_updated = NOW()";

// If there are no fields to update, return error
if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No fields to update']);
    exit;
}

// Build the final query
$query = "UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE task_id = ?";
$types .= 'i';
$params[] = $task_id;

// Execute the update
$update_query = $conn->prepare($query);

// Bind parameters dynamically
if (!empty($params)) {
    $update_query->bind_param($types, ...$params);
}

if ($update_query->execute()) {
    // Fetch the updated task
    $get_updated = $conn->prepare("
        SELECT t.*, 
               u1.username as assigned_to_name,
               u2.username as assigned_by_name
        FROM tasks t
        LEFT JOIN users u1 ON t.assigned_to = u1.user_id
        LEFT JOIN users u2 ON t.assigned_by = u2.user_id
        WHERE t.task_id = ?
    ");
    $get_updated->bind_param('i', $task_id);
    $get_updated->execute();
    $updated_task = $get_updated->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Task updated successfully',
        'task' => $updated_task
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error updating task: ' . $conn->error
    ]);
}

$conn->close();
?> 