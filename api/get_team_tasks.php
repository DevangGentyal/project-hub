<?php
session_start();
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get team ID from query parameters
if (!isset($_GET['team_id']) || empty($_GET['team_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Team ID is required']);
    exit;
}

$team_id = intval($_GET['team_id']);
$user_id = $_SESSION['user_id'];

// Check if the team exists and user has access to it
$team_check = $conn->prepare("
    SELECT * FROM teams 
    WHERE team_id = ? AND (
        team_leader = ? OR 
        FIND_IN_SET(?, team_member_ids) OR 
        guide_id = ?
    )
");
$team_check->bind_param('iisi', $team_id, $user_id, $user_id, $user_id);
$team_check->execute();

if ($team_check->get_result()->num_rows === 0) {
    // Check if team exists at all
    $team_exists = $conn->prepare("SELECT team_id FROM teams WHERE team_id = ?");
    $team_exists->bind_param('i', $team_id);
    $team_exists->execute();
    
    if ($team_exists->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Team not found']);
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to view this team\'s tasks']);
    }
    exit;
}

// Optional filters
$status_filter = "";
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $status_filter = "AND t.status = '$status'";
}

$assigned_to_filter = "";
if (isset($_GET['assigned_to']) && !empty($_GET['assigned_to'])) {
    $assigned_to = intval($_GET['assigned_to']);
    $assigned_to_filter = "AND t.assigned_to = $assigned_to";
}

// Fetch tasks
$query = "
    SELECT t.*, 
           a.username as assigned_to_name,
           b.username as assigned_by_name
    FROM tasks t
    LEFT JOIN users a ON t.assigned_to = a.user_id
    LEFT JOIN users b ON t.assigned_by = b.user_id
    WHERE t.team_id = ? $status_filter $assigned_to_filter
    ORDER BY 
        CASE 
            WHEN t.status = 'blocked' THEN 1
            WHEN t.status = 'in-progress' THEN 2
            WHEN t.status = 'not-started' THEN 3
            WHEN t.status = 'completed' THEN 4
            ELSE 5
        END,
        t.due_date ASC
";

$tasks_query = $conn->prepare($query);
$tasks_query->bind_param('i', $team_id);
$tasks_query->execute();
$result = $tasks_query->get_result();

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}

echo json_encode([
    'success' => true,
    'team_id' => $team_id,
    'count' => count($tasks),
    'tasks' => $tasks
]);

$conn->close();
?> 