<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate input
if (!isset($data['file_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

include 'includes/db_connect.php';

$file_id = intval($data['file_id']);
$guide_id = intval($_SESSION['user_id']);

// Check if the file exists and who owns it
$check_query = "SELECT f.*, t.guide_id 
                FROM files f
                JOIN teams t ON f.team_id = t.team_id
                WHERE f.file_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit();
}

$file_data = $result->fetch_assoc();

// Check if the current user is authorized to delete the file
if ($file_data['guide_id'] != $guide_id && $file_data['uploaded_by'] != $guide_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized to delete this file']);
    exit();
}

// Get the file path
$file_path = $file_data['path'];

// Delete the file from the database
$delete_query = "DELETE FROM files WHERE file_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param("i", $file_id);

if ($stmt->execute()) {
    // Try to delete the physical file if it exists
    if (file_exists($file_path)) {
        if (unlink($file_path)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
        } else {
            // DB record deleted but file not deleted
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'File record deleted but could not remove file from disk']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'File record deleted (file not found on disk)']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?> 