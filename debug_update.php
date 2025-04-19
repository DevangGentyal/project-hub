<?php
session_start();
include 'includes/db_connect.php';

// Set up debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to dump variable with formatting
function debug_dump($var, $label = null) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f7f7f7;'>";
    if ($label) {
        echo "<h3>$label</h3>";
    }
    echo "<pre>";
    var_dump($var);
    echo "</pre></div>";
}

// Get the current database structure
$tables = ['teams', 'projects', 'tasks', 'task_comments'];
$db_structure = [];

foreach ($tables as $table) {
    // Get table columns
    $result = $conn->query("DESCRIBE $table");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    $db_structure[$table] = $columns;
    
    // Get sample data
    $data_result = $conn->query("SELECT * FROM $table LIMIT 1");
    if ($data_result && $data_result->num_rows > 0) {
        $sample = $data_result->fetch_assoc();
        $db_structure[$table . '_sample'] = $sample;
    }
}

// Test team leader update
$test_leader_update = false;
if (isset($_POST['test_leader'])) {
    $test_leader_update = true;
    $team_id = intval($_POST['team_id']);
    $leader_id = intval($_POST['leader_id']);
    
    echo "<h2>Testing Team Leader Update</h2>";
    echo "Attempting to update team ID: $team_id with leader ID: $leader_id<br>";
    
    // Get current team data
    $team_query = $conn->prepare("SELECT * FROM teams WHERE team_id = ?");
    $team_query->bind_param('i', $team_id);
    $team_query->execute();
    $current_team = $team_query->get_result()->fetch_assoc();
    
    debug_dump($current_team, "Current Team Data");
    
    // Get member IDs format
    $member_ids = [];
    if (!empty($current_team['team_member_ids'])) {
        $test_decode = json_decode($current_team['team_member_ids'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $member_ids = $test_decode;
            echo "team_member_ids is a valid JSON array<br>";
        } else {
            $member_ids = array_map('trim', explode(',', $current_team['team_member_ids']));
            echo "team_member_ids is a comma-separated string<br>";
        }
    }
    
    debug_dump($member_ids, "Parsed Member IDs");
    
    // Check if leader is in team
    $leader_in_team = false;
    if (!empty($member_ids)) {
        $leader_in_team = in_array($leader_id, $member_ids);
    }
    
    echo "Leader in team: " . ($leader_in_team ? "Yes" : "No") . "<br>";
    
    // Add leader to team if not present
    if (!$leader_in_team && $leader_id > 0) {
        $member_ids[] = $leader_id;
        $member_ids = array_unique($member_ids);
        echo "Leader added to member_ids<br>";
        
        // Determine proper format for update
        $update_value = json_encode($member_ids);
        
        debug_dump($update_value, "New member_ids value");
        
        // Update member_ids
        $update_query = $conn->prepare("UPDATE teams SET team_member_ids = ? WHERE team_id = ?");
        $update_query->bind_param('si', $update_value, $team_id);
        $success = $update_query->execute();
        
        echo "Update member_ids result: " . ($success ? "Success" : "Failed: " . $conn->error) . "<br>";
    }
    
    // Update team leader
    $update_query = $conn->prepare("UPDATE teams SET team_leader = ? WHERE team_id = ?");
    $update_query->bind_param('ii', $leader_id, $team_id);
    $success = $update_query->execute();
    
    echo "Update team_leader result: " . ($success ? "Success" : "Failed: " . $conn->error) . "<br>";
    
    // Get updated team data
    $team_query->execute();
    $updated_team = $team_query->get_result()->fetch_assoc();
    
    debug_dump($updated_team, "Updated Team Data");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Update Functionality</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        
        .test-form {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        
        input, select {
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Update Functionality</h1>
        
        <div class="test-form">
            <h2>Test Team Leader Update</h2>
            <form method="post">
                <div>
                    <label for="team_id">Team ID:</label>
                    <input type="number" id="team_id" name="team_id" required>
                </div>
                <div>
                    <label for="leader_id">Leader ID:</label>
                    <input type="number" id="leader_id" name="leader_id" required>
                </div>
                <button type="submit" name="test_leader">Test Update</button>
            </form>
        </div>
        
        <?php if (!$test_leader_update): ?>
            <h2>Database Structure</h2>
            <?php foreach ($db_structure as $name => $data): ?>
                <h3><?php echo $name; ?></h3>
                <pre><?php print_r($data); ?></pre>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html> 