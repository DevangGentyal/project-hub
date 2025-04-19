<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// For debugging
error_log("Join Subject Request: " . file_get_contents('php://input'));

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

try {
    // Get JSON data from request
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Log decoded data
    error_log("Decoded data: " . print_r($data, true));

    // Validate input
    if (!isset($data['subject_code']) || empty($data['subject_code'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Subject code is required']);
        exit();
    }

    include 'includes/db_connect.php';

    // Clean the subject code
    $code = trim($data['subject_code']);
    $code = $conn->real_escape_string($code);

    // Log the code we're searching for
    error_log("Searching for subject code: " . $code);

    // Check if subject exists
    $subject_query = "SELECT subject_id FROM subjects WHERE subject_code = '$code'";
    $result = $conn->query($subject_query);

    if ($result && $result->num_rows > 0) {
        $subject = $result->fetch_assoc();
        $subject_id = $subject['subject_id'];
        $student_id = intval($_SESSION['user_id']);
        
        error_log("Found subject ID: " . $subject_id . " for student ID: " . $student_id);
        
        // Get current subject_ids for the student
        $student_query = "SELECT subject_ids FROM students WHERE student_id = $student_id";
        $student_result = $conn->query($student_query);
        
        if ($student_result && $student_result->num_rows > 0) {
            $student_data = $student_result->fetch_assoc();
            
            // Handle the case where subject_ids might be null, empty or invalid JSON
            $subject_ids_value = $student_data['subject_ids'];
            $current_subjects = [];
            
            if (!empty($subject_ids_value) && $subject_ids_value !== '[]' && $subject_ids_value !== 'null') {
                // Try to decode the JSON array
                $decoded = json_decode($subject_ids_value, true);
                if (is_array($decoded)) {
                    $current_subjects = $decoded;
                }
            }
            
            error_log("Current subjects (decoded from JSON): " . print_r($current_subjects, true));
            
            // Check if student is already enrolled in this subject
            if (in_array($code, $current_subjects)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You are already enrolled in this subject']);
                exit();
            }
            
            // Add the new subject code
            $current_subjects[] = $code;
            
            // Encode back to JSON
            $new_subject_ids_json = json_encode($current_subjects);
            error_log("New subject IDs (JSON): " . $new_subject_ids_json);
            
            // Update student record
            $update_query = "UPDATE students SET subject_ids = ? WHERE student_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_subject_ids_json, $student_id);
            
            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Successfully joined subject']);
                exit();
            } else {
                error_log("Database error: " . $stmt->error);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
                exit();
            }
        } else {
            error_log("Student record not found, creating new one");
            // Student record doesn't exist yet, create a new one
            // Store just the subject code as a JSON array
            $initial_subjects = json_encode([$code]);
            
            $insert_query = "INSERT INTO students (student_id, name, subject_ids) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            
            // Get user name
            $user_query = "SELECT name FROM users WHERE student_id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $student_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user = $user_result->fetch_assoc();
            $user_name = $user ? $user['name'] : 'Unknown Student';
            
            $stmt->bind_param("iss", $student_id, $user_name, $initial_subjects);
            
            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Successfully joined subject']);
                exit();
            } else {
                error_log("Could not create student record: " . $stmt->error);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Could not create student record: ' . $stmt->error]);
                exit();
            }
        }
    } else {
        error_log("Invalid subject code: " . $code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid subject code']);
        exit();
    }
} catch (Exception $e) {
    error_log("Exception in join_subject.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit();
} 