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
$required_fields = ['team_id', 'title', 'assigned_to', 'due_date'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Get values and sanitize
$team_id = intval($input['team_id']);
$title = trim($input['title']);
$description = isset($input['description']) ? trim($input['description']) : '';
$assigned_to = intval($input['assigned_to']);
$due_date = trim($input['due_date']);
$assigned_by = $_SESSION['user_id'];

// Validate team exists
$team_check = $conn->prepare("SELECT team_id FROM teams WHERE team_id = ?");
$team_check->bind_param('i', $team_id);
$team_check->execute();
$team_result = $team_check->get_result();

if ($team_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Team not found']);
    exit;
}

// Validate student exists
$student_check = $conn->prepare("SELECT name FROM students WHERE student_id = ?");
$student_check->bind_param('i', $assigned_to);
$student_check->execute();
$student_result = $student_check->get_result();

if ($student_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$student_data = $student_result->fetch_assoc();
$assigned_to_name = $student_data['name'];

// Validate due date format
if (!strtotime($due_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Format date as YYYY-MM-DD
$due_date = date('Y-m-d', strtotime($due_date));

// Insert task
$insert_task = $conn->prepare("INSERT INTO tasks (team_id, title, description, assigned_to, assigned_to_name, assigned_by, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'not-started')");
$insert_task->bind_param('issiiss', $team_id, $title, $description, $assigned_to, $assigned_to_name, $assigned_by, $due_date);

if ($insert_task->execute()) {
    $task_id = $conn->insert_id;
    
    // Get task details to return
    $task_query = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
    $task_query->bind_param('i', $task_id);
    $task_query->execute();
    $task_result = $task_query->get_result();
    $task_data = $task_result->fetch_assoc();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Task assigned successfully',
        'task_id' => $task_id,
        'task' => $task_data
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to assign task', 
        'error' => $conn->error
    ]);
}

$conn->close();
?> 