<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all steps
function log_step($message) {
    echo "<div class='log'>" . htmlspecialchars($message) . "</div>";
    error_log($message);
}

// For testing, if not logged in, set a test user ID
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Set to a guide ID
    log_step("Setting test user_id = 1");
}

include 'includes/db_connect.php';

// Process form submission
$result_message = "";
$team_data = null;
$student_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_leader'])) {
    log_step("Processing form submission");
    
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    $leader_id = isset($_POST['leader_id']) ? intval($_POST['leader_id']) : 0;
    
    if ($team_id > 0) {
        log_step("Team ID: $team_id, Leader ID: $leader_id");
        
        // 1. Get current team data
        $team_query = $conn->prepare("SELECT * FROM teams WHERE team_id = ?");
        $team_query->bind_param('i', $team_id);
        $team_query->execute();
        $team_result = $team_query->get_result();
        
        if ($team_result->num_rows > 0) {
            $team_data = $team_result->fetch_assoc();
            log_step("Found team: " . htmlspecialchars($team_data['team_name']));
            
            // 2. Parse team_member_ids
            $team_member_ids = [];
            if (!empty($team_data['team_member_ids'])) {
                // Try to decode JSON first
                $json_decode_result = json_decode($team_data['team_member_ids'], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_decode_result)) {
                    // Successfully decoded as JSON
                    $team_member_ids = $json_decode_result;
                    log_step("team_member_ids decoded as JSON: " . print_r($team_member_ids, true));
                } else {
                    // Try to treat it as a string containing array representation [1,2,3]
                    if (preg_match('/^\[.*\]$/', $team_data['team_member_ids'])) {
                        // Remove brackets and split by commas
                        $trimmed = trim($team_data['team_member_ids'], '[]');
                        $team_member_ids = array_map('intval', explode(',', $trimmed));
                        log_step("team_member_ids parsed from array notation: " . print_r($team_member_ids, true));
                    } else {
                        // Fall back to comma-separated string
                        $team_member_ids = array_map('intval', explode(',', $team_data['team_member_ids']));
                        log_step("team_member_ids parsed as comma-separated: " . print_r($team_member_ids, true));
                    }
                }
            }
            
            // 3. Check if leader is already in team
            $leader_in_team = in_array($leader_id, $team_member_ids);
            log_step("Leader in team: " . ($leader_in_team ? "Yes" : "No"));
            
            // 4. If not in team, add leader to team_member_ids
            if (!$leader_in_team && $leader_id > 0) {
                $team_member_ids[] = $leader_id;
                log_step("Added leader to team_member_ids: " . print_r($team_member_ids, true));
                
                // Update team_member_ids in database
                $member_ids_json = json_encode($team_member_ids);
                $update_members = $conn->prepare("UPDATE teams SET team_member_ids = ? WHERE team_id = ?");
                $update_members->bind_param('si', $member_ids_json, $team_id);
                
                if ($update_members->execute()) {
                    log_step("Successfully updated team_member_ids");
                } else {
                    log_step("Failed to update team_member_ids: " . $update_members->error);
                }
            }
            
            // 5. Update team leader
            $update_leader = $conn->prepare("UPDATE teams SET team_leader = ? WHERE team_id = ?");
            $update_leader->bind_param('ii', $leader_id, $team_id);
            
            if ($update_leader->execute()) {
                log_step("Successfully updated team leader");
                $result_message = "<div class='success'>Team leader updated successfully!</div>";
                
                // Get updated team data
                $team_query->execute();
                $team_data = $team_query->get_result()->fetch_assoc();
            } else {
                log_step("Failed to update team leader: " . $update_leader->error);
                $result_message = "<div class='error'>Failed to update team leader: " . $update_leader->error . "</div>";
            }
        } else {
            log_step("Team not found");
            $result_message = "<div class='error'>Team not found</div>";
        }
    } else {
        log_step("Invalid team ID");
        $result_message = "<div class='error'>Invalid team ID</div>";
    }
}

// Get teams for dropdown
$teams_query = "SELECT * FROM teams ORDER BY team_id DESC";
$teams_result = $conn->query($teams_query);

// Get students for dropdown
$students_query = "SELECT * FROM students ORDER BY student_id";
$students_result = $conn->query($students_query);

while ($student = $students_result->fetch_assoc()) {
    $student_data[$student['student_id']] = $student;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Leader Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
        }
        .container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        select, input, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .log {
            background-color: #f0f0f0;
            padding: 5px 10px;
            margin: 5px 0;
            border-left: 3px solid #999;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Team Leader Update</h1>
        
        <?php echo $result_message; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="team_id">Select Team:</label>
                <select name="team_id" id="team_id" required>
                    <option value="">-- Select Team --</option>
                    <?php if ($teams_result && $teams_result->num_rows > 0): ?>
                        <?php while ($team = $teams_result->fetch_assoc()): ?>
                            <option value="<?php echo $team['team_id']; ?>">
                                <?php echo htmlspecialchars($team['team_id'] . ': ' . $team['team_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="leader_id">Select New Leader:</label>
                <select name="leader_id" id="leader_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($student_data as $student): ?>
                        <option value="<?php echo $student['student_id']; ?>">
                            <?php echo htmlspecialchars($student['student_id'] . ': ' . $student['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="update_leader">Update Team Leader</button>
        </form>
        
        <?php if ($team_data): ?>
            <h2>Team Details</h2>
            <table>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($team_data as $field => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($field); ?></td>
                        <td>
                            <?php 
                            if ($field === 'team_member_ids') {
                                $member_ids = [];
                                $decoded = json_decode($value, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                    $member_ids = $decoded;
                                } else if (preg_match('/^\[.*\]$/', $value)) {
                                    $trimmed = trim($value, '[]');
                                    $member_ids = array_map('intval', explode(',', $trimmed));
                                } else {
                                    $member_ids = array_map('intval', explode(',', $value));
                                }
                                
                                echo htmlspecialchars($value) . " (";
                                $member_names = [];
                                foreach ($member_ids as $id) {
                                    if (isset($student_data[$id])) {
                                        $member_names[] = $student_data[$id]['name'];
                                    } else {
                                        $member_names[] = "Unknown: " . $id;
                                    }
                                }
                                echo htmlspecialchars(implode(", ", $member_names)) . ")";
                            } else if ($field === 'team_leader') {
                                echo htmlspecialchars($value) . " (" . 
                                     (isset($student_data[$value]) ? htmlspecialchars($student_data[$value]['name']) : "Unknown") . ")";
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html> 