<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo 'Not authenticated';
    exit();
}

// Validate input
if (!isset($_GET['team_id']) || !isset($_GET['file'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Missing required parameters';
    exit();
}

include 'includes/db_connect.php';

$team_id = intval($_GET['team_id']);
$filename = trim($_GET['file']);
$user_id = intval($_SESSION['user_id']);

// Sanitize the filename to prevent directory traversal attacks
$filename = basename($filename);

// Check if the user is authorized to access files from this team
$auth_query = "SELECT t.* FROM teams t 
              LEFT JOIN team_members tm ON t.team_id = tm.team_id
              WHERE t.team_id = ? AND (t.guide_id = ? OR tm.student_id = ?)";
$stmt = $conn->prepare($auth_query);
$stmt->bind_param("iii", $team_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Not authorized to access files from this team';
    exit();
}

// Get the file data from the database
$file_query = "SELECT * FROM files WHERE team_id = ? AND path LIKE ?";
$file_path_pattern = '%' . $filename;
$stmt = $conn->prepare($file_query);
$stmt->bind_param("is", $team_id, $file_path_pattern);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found';
    exit();
}

$file_data = $result->fetch_assoc();
$actual_path = $file_data['path'];

// Check if the file exists on the server
if (!file_exists($actual_path)) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found on server';
    exit();
}

// Set appropriate headers for the file download
$original_filename = $file_data['name'];
$file_size = filesize($actual_path);
$file_type = $file_data['type'];

header('Content-Description: File Transfer');
header('Content-Type: ' . $file_type);
header('Content-Disposition: attachment; filename="' . $original_filename . '"');
header('Content-Length: ' . $file_size);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Read the file and output it
readfile($actual_path);

$stmt->close();
$conn->close();
exit();
?> 