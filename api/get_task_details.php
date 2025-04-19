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

// Get task ID from query parameters
if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = intval($_GET['task_id']);
$user_id = $_SESSION['user_id'];

// Fetch the task with team and user details
$query = "
    SELECT t.*, 
           u1.username as assigned_to_name,
           u2.username as assigned_by_name,
           tm.team_name,
           tm.team_leader,
           tm.team_member_ids,
           tm.guide_id
    FROM tasks t
    JOIN teams tm ON t.team_id = tm.team_id
    LEFT JOIN users u1 ON t.assigned_to = u1.user_id
    LEFT JOIN users u2 ON t.assigned_by = u2.user_id
    WHERE t.task_id = ?
";

$task_query = $conn->prepare($query);
$task_query->bind_param('i', $task_id);
$task_query->execute();
$result = $task_query->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

$task = $result->fetch_assoc();

// Check if user has permission to view this task (team leader, member, guide, or task is assigned to them)
$team_leader = $task['team_leader'];
$team_members = explode(',', $task['team_member_ids']);
$guide_id = $task['guide_id'];

if ($team_leader != $user_id && 
    !in_array($user_id, $team_members) && 
    $guide_id != $user_id && 
    $task['assigned_to'] != $user_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to view this task']);
    exit;
}

// Remove team structure data from response
unset($task['team_leader']);
unset($task['team_member_ids']);
unset($task['guide_id']);

// Fetch comments for this task if they exist
$comments_query = $conn->prepare("
    SELECT c.*, u.username, u.profile_pic 
    FROM task_comments c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.task_id = ?
    ORDER BY c.created_at ASC
");

$comments_query->bind_param('i', $task_id);
$comments_query->execute();
$comments_result = $comments_query->get_result();

$comments = [];
while ($comment = $comments_result->fetch_assoc()) {
    $comments[] = $comment;
}

// Add comments to task data
$task['comments'] = $comments;

echo json_encode([
    'success' => true,
    'task' => $task
]);

$conn->close();
?> 