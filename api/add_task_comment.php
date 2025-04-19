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

// Get necessary parameters
if (!isset($_POST['task_id']) || empty($_POST['task_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

if (!isset($_POST['comment']) || empty($_POST['comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Comment text is required']);
    exit;
}

$task_id = intval($_POST['task_id']);
$comment_text = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];

// Check if the task exists and if the user has permission to comment
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
$can_comment = ($team_leader == $user_id || 
                in_array($user_id, $team_members) || 
                $task['assigned_to'] == $user_id || 
                $guide_id == $user_id);

if (!$can_comment) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to comment on this task']);
    exit;
}

// Add the comment
$insert_query = $conn->prepare("
    INSERT INTO task_comments (task_id, user_id, comment_text, created_at)
    VALUES (?, ?, ?, NOW())
");
$insert_query->bind_param('iis', $task_id, $user_id, $comment_text);

if ($insert_query->execute()) {
    $comment_id = $conn->insert_id;
    
    // Get the inserted comment with username
    $get_comment = $conn->prepare("
        SELECT tc.*, u.username
        FROM task_comments tc
        JOIN users u ON tc.user_id = u.user_id
        WHERE tc.comment_id = ?
    ");
    $get_comment->bind_param('i', $comment_id);
    $get_comment->execute();
    $comment = $get_comment->get_result()->fetch_assoc();
    
    // Also update the task's last_updated field
    $update_task = $conn->prepare("UPDATE tasks SET last_updated = NOW() WHERE task_id = ?");
    $update_task->bind_param('i', $task_id);
    $update_task->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Comment added successfully',
        'comment' => $comment
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error adding comment: ' . $conn->error
    ]);
}

$conn->close();
?> 