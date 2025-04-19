<?php
include '../includes/db_connect.php';
header('Content-Type: application/json');

// Get and sanitize input (from JSON POST body)
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$response = ['success' => false];

// Debug: Log the raw input
error_log("delete_task.php - Raw input: " . $json_data);

if (!isset($data['task_id'])) {
    $response['message'] = "Missing required task_id parameter.";
    echo json_encode($response);
    exit;
}

$task_id = intval($data['task_id']);

// Debug: Log the task ID
error_log("delete_task.php - Task ID: " . $task_id);

// Delete the task
$stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $task_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = "Task deleted successfully.";
        error_log("delete_task.php - Task deleted successfully: " . $task_id);
    } else {
        $response['message'] = "Task not found or already deleted.";
        error_log("delete_task.php - Task not found or already deleted: " . $task_id);
    }
} else {
    $response['message'] = "SQL Error: " . $stmt->error;
    error_log("delete_task.php - SQL Error: " . $stmt->error);
}

$stmt->close();
echo json_encode($response); 