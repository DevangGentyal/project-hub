<?php
include '../includes/db_connect.php'; // Adjust path as needed


$project_id = $_POST['project_id'];
$project_name = $_POST['project_name'];
$abstract = $_POST['abstract'];
$start_date = $_POST['start_date'];
$due_date = $_POST['due_date'];


$response = ['success' => false];

if ($project_id && $project_name && $abstract && $start_date && $due_date) {
    $timeline = json_encode([
        'start_date' => $start_date,
        'due_date' => $due_date
    ]);

    $stmt = $conn->prepare("UPDATE projects SET project_name = ?, abstract = ?, timeline = ? WHERE project_id = ?");
    $stmt->bind_param("sssi", $project_name, $abstract, $timeline, $project_id);

    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['message'] = $stmt->error;
    }

    $stmt->close();
} else {
    $response['message'] = "Missing required fields.";
}

echo json_encode($response);
