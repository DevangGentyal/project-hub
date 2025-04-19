<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send error response
function send_error($code, $message) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    send_error(401, 'Unauthorized access');
}

include 'includes/db_connect.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['name']) || !isset($data['code'])) {
    send_error(400, 'Invalid data');
}

$subject_name = trim($conn->real_escape_string($data['name']));
$subject_code = strtoupper(trim($conn->real_escape_string($data['code'])));
$guide_id = intval($_SESSION['user_id']);

// Validate inputs
if (empty($subject_name)) {
    send_error(400, 'Subject name cannot be empty');
}

if (empty($subject_code)) {
    send_error(400, 'Subject code cannot be empty');
}

// Check for uniqueness of subject code
$check = $conn->prepare("SELECT * FROM subjects WHERE subject_code = ?");
$check->bind_param("s", $subject_code);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    send_error(400, 'Subject code already exists');
}

// Insert the new subject
$stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, guide_id) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $subject_name, $subject_code, $guide_id);

if ($stmt->execute()) {
    $new_subject_id = $stmt->insert_id;
    $stmt->close();
    
    // Get the team count for the new subject (should be 0 for a new subject)
    $countResult = $conn->prepare("SELECT COUNT(*) as total FROM teams WHERE subject_id = ?");
    $countResult->bind_param("i", $new_subject_id);
    $countResult->execute();
    $count = $countResult->get_result()->fetch_assoc();
    
    // Return success response with subject data
    echo json_encode([
        'success' => true,
        'subject' => [
            'id' => $new_subject_id,
            'name' => $subject_name,
            'code' => $subject_code,
            'teams' => intval($count['total'])
        ]
    ]);
} else {
    send_error(500, 'Failed to add subject: ' . $stmt->error);
}

$conn->close();
?> 