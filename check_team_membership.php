<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Get parameters
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : intval($_SESSION['user_id']);
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

if ($subject_id <= 0) {
    echo json_encode(['error' => 'Invalid subject ID']);
    exit();
}

include 'includes/db_connect.php';

// Check if student is a member of any team for this subject
$response = [
    'is_member' => false,
    'team_id' => null
];

// First get all teams for this subject
$teams_query = "SELECT team_id FROM teams WHERE subject_id = ?";
$stmt = $conn->prepare($teams_query);
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$teams_result = $stmt->get_result();
$team_ids = [];

while ($team = $teams_result->fetch_assoc()) {
    $team_ids[] = $team['team_id'];
}
$stmt->close();

if (empty($team_ids)) {
    // No teams found for this subject
    echo json_encode($response);
    exit();
}

// Now check if student is in any of these teams
$student_query = "SELECT team_ids FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student || empty($student['team_ids'])) {
    // Student not found or not part of any teams
    echo json_encode($response);
    exit();
}

// Parse student's team IDs (JSON array)
$student_team_ids = json_decode($student['team_ids'], true);
if (!is_array($student_team_ids)) {
    // Invalid team_ids format
    echo json_encode($response);
    exit();
}

// Check if student is in any of the teams for this subject
foreach ($team_ids as $team_id) {
    if (in_array($team_id, $student_team_ids)) {
        $response['is_member'] = true;
        $response['team_id'] = $team_id;
        break;
    }
}

echo json_encode($response);
exit();
?> 