<?php
include '../includes/db_connect.php';
header('Content-Type: application/json');

// Debug: Log all incoming data
error_log("assign_task.php - Raw POST data: " . file_get_contents('php://input'));
error_log("assign_task.php - POST array: " . print_r($_POST, true));

$response = ['success' => false];

// Check if form data is properly received
if (!isset($_POST['project_id']) || !isset($_POST['task_title']) || !isset($_POST['student_id']) || !isset($_POST['task_description']) ) {
    $response['message'] = "Missing required data. All fields are required.";
    $response['debug'] = [
        'post_data' => $_POST,
        'raw_input' => file_get_contents('php://input')
    ];
    echo json_encode($response);
    exit;
}

$project_id = intval($_POST['project_id']);
$task_title = $_POST['task_title'];
$task_description = $_POST['task_description'];
$student_id = intval($_POST['student_id']);
$due_date = $_POST['due_date'];
$is_completed = 0; // Default to not completed

// Debug: Log the received data
error_log("assign_task.php - Project ID: " . $project_id);
error_log("assign_task.php - Task title: " . $task_title);
error_log("assign_task.php - Student ID: " . $student_id);

// Insert the task into the database
$stmt = $conn->prepare("INSERT INTO tasks (project_id, task_name, task_description, student_id, is_completed) VALUES (?, ?, ?, ?, ?, )");
$stmt->bind_param("issis", $project_id, $task_title, $task_description, $student_id, $is_completed);

if ($stmt->execute()) {
    $task_id = $conn->insert_id;
    $response['success'] = true;
    $response['message'] = "Task assigned successfully.";
    $response['task_id'] = $task_id;
    
    error_log("assign_task.php - Task assigned successfully with ID: " . $task_id);
} else {
    $response['message'] = "SQL Error: " . $stmt->error;
    $response['debug'] = [
        'sql_error' => $stmt->error
    ];
    error_log("assign_task.php - SQL Error: " . $stmt->error);
}

$stmt->close();
echo json_encode($response); 