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
if (!isset($data['team_id']) || !isset($data['tasks']) || !is_array($data['tasks'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

include 'includes/db_connect.php';

$team_id = intval($data['team_id']);
$tasks = $data['tasks'];
$guide_id = intval($_SESSION['user_id']);

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

// Update each task status
$updated_count = 0;
$error_messages = [];

foreach ($tasks as $task) {
    if (!isset($task['task_id']) || !isset($task['status'])) {
        continue;
    }
    
    $task_id = intval($task['task_id']);
    $status = $task['status'];
    
    // Validate status
    if (!in_array($status, ['not-started', 'in-progress', 'completed'])) {
        $error_messages[] = "Invalid status for task $task_id";
        continue;
    }
    
    // Check if task belongs to this team
    $check_task_query = "SELECT * FROM tasks WHERE task_id = ? AND team_id = ?";
    $stmt = $conn->prepare($check_task_query);
    $stmt->bind_param("ii", $task_id, $team_id);
    $stmt->execute();
    $task_result = $stmt->get_result();
    
    if ($task_result->num_rows === 0) {
        $error_messages[] = "Task $task_id not found or does not belong to this team";
        continue;
    }
    
    // Update task status
    $update_query = "UPDATE tasks SET status = ? WHERE task_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $task_id);
    
    if ($stmt->execute()) {
        $updated_count++;
    } else {
        $error_messages[] = "Error updating task $task_id: " . $stmt->error;
    }
}

if ($updated_count > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => "$updated_count tasks updated successfully",
        'errors' => $error_messages
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'No tasks were updated',
        'errors' => $error_messages
    ]);
}

$stmt->close();
$conn->close();
?> 