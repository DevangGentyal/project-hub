<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate input
if (!isset($_POST['team_id']) || !isset($_FILES['files'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

include 'includes/db_connect.php';

$team_id = intval($_POST['team_id']);
$guide_id = intval($_SESSION['user_id']);

// Check if team exists and belongs to this guide
$check_query = "SELECT t.* FROM teams t WHERE t.team_id = ? AND t.guide_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $team_id, $guide_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Team not found or not authorized']);
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/teams/' . $team_id;
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$uploaded_files = [];
$errors = [];

// Handle multiple file uploads
foreach ($_FILES['files']['name'] as $key => $name) {
    $tmp_name = $_FILES['files']['tmp_name'][$key];
    $error = $_FILES['files']['error'][$key];
    $size = $_FILES['files']['size'][$key];
    $type = $_FILES['files']['type'][$key];
    
    // Skip files with errors
    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading $name: " . upload_error_message($error);
        continue;
    }
    
    // Validate file size (max 10MB)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($size > $max_size) {
        $errors[] = "File $name is too large. Maximum file size is 10MB.";
        continue;
    }
    
    // Generate a unique filename to prevent overwriting
    $filename = time() . '_' . sanitize_filename($name);
    $file_path = $upload_dir . '/' . $filename;
    
    // Move the uploaded file to the destination
    if (move_uploaded_file($tmp_name, $file_path)) {
        // Insert file record into database
        $file_url = 'download_file.php?team_id=' . $team_id . '&file=' . urlencode($filename);
        $uploaded_by = $guide_id;
        $file_size = format_file_size($size);
        
        $insert_query = "INSERT INTO files (team_id, name, path, type, size, uploaded_by, upload_date) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isssii", $team_id, $name, $file_path, $type, $size, $uploaded_by);
        
        if ($stmt->execute()) {
            $file_id = $conn->insert_id;
            
            // Get uploader name from users or guides table
            $uploader_query = "SELECT name FROM guides WHERE guide_id = ?";
            $uploader_stmt = $conn->prepare($uploader_query);
            $uploader_stmt->bind_param("i", $uploaded_by);
            $uploader_stmt->execute();
            $uploader_result = $uploader_stmt->get_result();
            $uploader_name = 'Unknown';
            
            if ($uploader_result->num_rows > 0) {
                $uploader_data = $uploader_result->fetch_assoc();
                $uploader_name = $uploader_data['name'];
            }
            
            $uploaded_files[] = [
                'id' => $file_id,
                'name' => $name,
                'url' => $file_url,
                'type' => $type,
                'size' => $file_size,
                'uploaded_by' => $uploader_name,
                'upload_date' => date('M d, Y')
            ];
        } else {
            $errors[] = "Database error while uploading $name: " . $stmt->error;
        }
    } else {
        $errors[] = "Failed to move uploaded file $name to destination";
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => count($uploaded_files) > 0,
    'message' => count($uploaded_files) . ' files uploaded successfully',
    'files' => $uploaded_files,
    'errors' => $errors
]);

$stmt->close();
$conn->close();

// Helper functions
function sanitize_filename($filename) {
    // Remove any invalid characters
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    // Ensure the filename is not too long
    return substr($filename, 0, 255);
}

function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function upload_error_message($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}
?> 