<?php
include '../includes/db_connect.php'; // or wherever your DB config is

$data = json_decode(file_get_contents("php://input"), true);
$team_id = $data['team_id'];
$leader_id = $data['leader_id'];
$team_member_ids = $data['team_member_ids'];

$response = ['success' => false];

// Unset any existing leaders in the team
$placeholders = implode(',', array_fill(0, count($team_member_ids), '?'));
$query = "UPDATE students SET is_leader = 0 WHERE student_id IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('i', count($team_member_ids)), ...$team_member_ids);
$stmt->execute();


// 2. Set selected leader
$stmt = $conn->prepare("UPDATE students SET is_leader = 1 WHERE student_id = ?");
$stmt->bind_param("i", $leader_id);
if ($stmt->execute()) {
    $response['success'] = true;
} else {
    $response['message'] = $stmt->error;
}
$stmt->close();

echo json_encode($response);
