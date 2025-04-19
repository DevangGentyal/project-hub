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

// Get task ID from the query parameters
if (!isset($_GET['task_id']) || empty($_GET['task_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

$task_id = intval($_GET['task_id']);
$user_id = $_SESSION['user_id'];

// Check if the task exists and if the user has permission to view comments
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

// Check if the user is a team member, team leader, assigned to the task, or guide
$can_view = ($team_leader == $user_id || 
             in_array($user_id, $team_members) || 
             $task['assigned_to'] == $user_id || 
             $guide_id == $user_id);

if (!$can_view) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to view comments for this task']);
    exit;
}

// Optional pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

// Get total comments count for pagination
$count_query = $conn->prepare("SELECT COUNT(*) as total FROM task_comments WHERE task_id = ?");
$count_query->bind_param('i', $task_id);
$count_query->execute();
$total_comments = $count_query->get_result()->fetch_assoc()['total'];

// Get the comments with user information
$comments_query = $conn->prepare("
    SELECT tc.*, u.username, u.profile_image
    FROM task_comments tc
    JOIN users u ON tc.user_id = u.user_id
    WHERE tc.task_id = ?
    ORDER BY tc.created_at DESC
    LIMIT ? OFFSET ?
");
$comments_query->bind_param('iii', $task_id, $limit, $offset);
$comments_query->execute();
$result = $comments_query->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

// Return the comments with pagination info
echo json_encode([
    'success' => true,
    'comments' => $comments,
    'pagination' => [
        'total' => $total_comments,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total_comments / $limit)
    ]
]);

$conn->close();
?> 