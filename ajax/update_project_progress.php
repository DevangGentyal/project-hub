<?php
include '../includes/db_connect.php';
header('Content-Type: application/json');

// Read raw input once
$rawInput = file_get_contents('php://input');
error_log("update_project_progress.php - Raw POST body: $rawInput");
error_log("update_project_progress.php - \$_POST: " . print_r($_POST, true));

$response = ['success' => false];

// Check required fields
if (!isset($_POST['project_id'], $_POST['project_progress'])) {
    $response['message'] = "Missing required data.";
    $response['debug']   = [
        'post_data' => $_POST,
        'raw_input' => $rawInput
    ];
    echo json_encode($response);
    exit;
}

$project_id            = (int) $_POST['project_id'];
$project_progress_json = $_POST['project_progress'];

error_log("Received project_id: $project_id");
error_log("Received project_progress JSON: $project_progress_json");

// Decode and validate JSON
$decoded = json_decode($project_progress_json, true);
if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
    $response['message'] = "Invalid JSON: " . json_last_error_msg();
    $response['debug']   = [
        'json_error'         => json_last_error(),
        'json_error_message' => json_last_error_msg(),
        'raw_json'           => $project_progress_json
    ];
    echo json_encode($response);
    exit;
}

// (Optional) Further structure validation here…


// Update
$sql  = "UPDATE projects SET `progress` = ? WHERE project_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $project_progress_json, $project_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = "Updated $columnName successfully.";
    $response['debug']   = [
        'column'        => $columnName,
        'project_id'    => $project_id,
        'affected_rows' => $stmt->affected_rows
    ];
    error_log("Update OK, rows: " . $stmt->affected_rows);
} else {
    $response['message'] = "SQL Error: " . $stmt->error;
    $response['debug']   = [
        'column'     => $columnName,
        'project_id' => $project_id,
        'sql_error'  => $stmt->error
    ];
    error_log("SQL Error: " . $stmt->error);
}

$stmt->close();
echo json_encode($response);
