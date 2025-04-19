<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a test user ID if not logged in (for debugging only)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Set to a valid guide_id for testing
}

include 'includes/db_connect.php';

// Get team ID from URL or use default
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Handle actions
$message = '';
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_leader':
            if (isset($_POST['team_id']) && isset($_POST['leader_id'])) {
                $update_team_id = intval($_POST['team_id']);
                $leader_id = intval($_POST['leader_id']);
                
                $update_query = "UPDATE teams SET team_leader = ? WHERE team_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ii", $leader_id, $update_team_id);
                
                if ($stmt->execute()) {
                    $message = "Team leader updated successfully to ID: $leader_id";
                } else {
                    $message = "Error updating team leader: " . $stmt->error;
                }
                $stmt->close();
            }
            break;
            
        case 'add_member':
            if (isset($_POST['team_id']) && isset($_POST['member_id'])) {
                $update_team_id = intval($_POST['team_id']);
                $member_id = intval($_POST['member_id']);
                
                // Get current team_member_ids
                $team_query = "SELECT team_member_ids FROM teams WHERE team_id = ?";
                $stmt = $conn->prepare($team_query);
                $stmt->bind_param("i", $update_team_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $team_data = $result->fetch_assoc();
                    $team_member_ids = !empty($team_data['team_member_ids']) ? 
                                      json_decode($team_data['team_member_ids'], true) : [];
                    
                    if (!is_array($team_member_ids)) {
                        $team_member_ids = [];
                    }
                    
                    // Add member if not already in team
                    if (!in_array($member_id, $team_member_ids)) {
                        $team_member_ids[] = $member_id;
                        $member_ids_json = json_encode($team_member_ids);
                        
                        $update_query = "UPDATE teams SET team_member_ids = ? WHERE team_id = ?";
                        $stmt = $conn->prepare($update_query);
                        $stmt->bind_param("si", $member_ids_json, $update_team_id);
                        
                        if ($stmt->execute()) {
                            $message = "Team member added successfully: ID $member_id";
                        } else {
                            $message = "Error adding team member: " . $stmt->error;
                        }
                    } else {
                        $message = "Member ID $member_id is already in the team";
                    }
                } else {
                    $message = "Team not found";
                }
                $stmt->close();
            }
            break;
    }
}

// Get list of teams
$teams_query = "SELECT * FROM teams ORDER BY team_id DESC";
$teams_result = $conn->query($teams_query);

// Get list of students
$students_query = "SELECT * FROM students ORDER BY student_id";
$students_result = $conn->query($students_query);

// Get specific team details if team_id is provided
$team_details = null;
if ($team_id > 0) {
    $team_query = "SELECT * FROM teams WHERE team_id = ?";
    $stmt = $conn->prepare($team_query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $team_result = $stmt->get_result();
    
    if ($team_result->num_rows > 0) {
        $team_details = $team_result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Team Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h1, h2, h3 {
            color: #333;
        }
        pre {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .action-area {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .action-box {
            flex: 1;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Team Data</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="action-area">
            <div class="action-box">
                <h2>Update Team Leader</h2>
                <form method="post">
                    <input type="hidden" name="action" value="update_leader">
                    <div class="form-group">
                        <label for="team_id">Team ID:</label>
                        <select name="team_id" id="team_id" required>
                            <?php if ($teams_result && $teams_result->num_rows > 0): ?>
                                <?php while ($team = $teams_result->fetch_assoc()): ?>
                                    <option value="<?php echo $team['team_id']; ?>" <?php echo ($team_id == $team['team_id']) ? 'selected' : ''; ?>>
                                        <?php echo $team['team_id'] . ': ' . $team['team_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php $teams_result->data_seek(0); // Reset for reuse ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="leader_id">Leader ID:</label>
                        <select name="leader_id" id="leader_id" required>
                            <?php if ($students_result && $students_result->num_rows > 0): ?>
                                <?php while ($student = $students_result->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo $student['student_id'] . ': ' . ($student['name'] ?? 'Unknown'); ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php $students_result->data_seek(0); // Reset for reuse ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit">Update Leader</button>
                </form>
            </div>
            
            <div class="action-box">
                <h2>Add Team Member</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_member">
                    <div class="form-group">
                        <label for="team_id_add">Team ID:</label>
                        <select name="team_id" id="team_id_add" required>
                            <?php if ($teams_result && $teams_result->num_rows > 0): ?>
                                <?php while ($team = $teams_result->fetch_assoc()): ?>
                                    <option value="<?php echo $team['team_id']; ?>" <?php echo ($team_id == $team['team_id']) ? 'selected' : ''; ?>>
                                        <?php echo $team['team_id'] . ': ' . $team['team_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="member_id">Member ID:</label>
                        <select name="member_id" id="member_id" required>
                            <?php if ($students_result && $students_result->num_rows > 0): ?>
                                <?php while ($student = $students_result->fetch_assoc()): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo $student['student_id'] . ': ' . ($student['name'] ?? 'Unknown'); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit">Add Member</button>
                </form>
            </div>
        </div>
        
        <?php if ($team_details): ?>
        <h2>Team Details</h2>
        <table>
            <tr>
                <th>Field</th>
                <th>Value</th>
                <th>JSON Decoded (if applicable)</th>
            </tr>
            <?php foreach ($team_details as $field => $value): ?>
                <tr>
                    <td><?php echo $field; ?></td>
                    <td><?php echo is_null($value) ? 'NULL' : $value; ?></td>
                    <td>
                        <?php 
                        if (in_array($field, ['team_member_ids']) && !empty($value)) {
                            $decoded = json_decode($value, true);
                            echo '<pre>' . print_r($decoded, true) . '</pre>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <h2>All Teams</h2>
        <table>
            <tr>
                <th>Team ID</th>
                <th>Team Name</th>
                <th>Team Leader</th>
                <th>Team Members</th>
                <th>Guide ID</th>
                <th>Subject ID</th>
                <th>Actions</th>
            </tr>
            <?php if ($teams_result && $teams_result->num_rows > 0): ?>
                <?php while ($team = $teams_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $team['team_id']; ?></td>
                        <td><?php echo $team['team_name']; ?></td>
                        <td><?php echo $team['team_leader']; ?></td>
                        <td>
                            <?php 
                            if (!empty($team['team_member_ids'])) {
                                $members = json_decode($team['team_member_ids'], true);
                                echo is_array($members) ? implode(', ', $members) : 'Invalid JSON';
                            } else {
                                echo 'None';
                            }
                            ?>
                        </td>
                        <td><?php echo $team['guide_id']; ?></td>
                        <td><?php echo $team['subject_id']; ?></td>
                        <td>
                            <a href="?team_id=<?php echo $team['team_id']; ?>">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No teams found</td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</body>
</html> 