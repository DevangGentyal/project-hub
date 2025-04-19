<?php
include 'includes/db_connect.php';

// Check if table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'teams'")->num_rows > 0;

if (!$table_exists) {
    echo "Creating teams table...<br>";
    // Create teams table
    $create_table_query = "CREATE TABLE `teams` (
        `team_id` int(11) NOT NULL AUTO_INCREMENT,
        `team_name` varchar(100) NOT NULL,
        `team_code` varchar(10) NOT NULL,
        `team_leader` int(11) DEFAULT NULL,
        `team_member_ids` text DEFAULT NULL,
        `guide_id` int(11) NOT NULL,
        `subject_id` int(11) NOT NULL,
        `progress` int(11) DEFAULT 0,
        `creation_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`team_id`),
        UNIQUE KEY `team_code` (`team_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_table_query)) {
        echo "Teams table created successfully.<br>";
    } else {
        echo "Error creating teams table: " . $conn->error . "<br>";
    }
} else {
    echo "Teams table exists.<br>";
    
    // Check for necessary columns
    $required_columns = [
        'team_id' => 'INT(11) NOT NULL AUTO_INCREMENT',
        'team_name' => 'VARCHAR(100) NOT NULL',
        'team_code' => 'VARCHAR(10) NOT NULL',
        'team_leader' => 'INT(11) DEFAULT NULL',
        'team_member_ids' => 'TEXT DEFAULT NULL',
        'guide_id' => 'INT(11) NOT NULL',
        'subject_id' => 'INT(11) NOT NULL',
        'progress' => 'INT(11) DEFAULT 0',
        'creation_datetime' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
    
    $columns_result = $conn->query("SHOW COLUMNS FROM teams");
    $existing_columns = [];
    
    while ($col = $columns_result->fetch_assoc()) {
        $existing_columns[$col['Field']] = true;
    }
    
    foreach ($required_columns as $column => $definition) {
        if (!isset($existing_columns[$column])) {
            echo "Adding missing column: $column<br>";
            $conn->query("ALTER TABLE teams ADD $column $definition");
        }
    }
}

// Check if we have any teams in the database
$teams_count = $conn->query("SELECT COUNT(*) as count FROM teams")->fetch_assoc()['count'];
echo "Total teams in database: $teams_count<br>";

// Show example of a team structure if at least one team exists
if ($teams_count > 0) {
    $team_example = $conn->query("SELECT * FROM teams LIMIT 1")->fetch_assoc();
    echo "<h3>Example team structure:</h3><pre>";
    print_r($team_example);
    echo "</pre>";
}

// Check projects table
$projects_table_exists = $conn->query("SHOW TABLES LIKE 'projects'")->num_rows > 0;

if (!$projects_table_exists) {
    echo "Creating projects table...<br>";
    // Create projects table
    $create_projects_query = "CREATE TABLE `projects` (
        `project_id` int(11) NOT NULL AUTO_INCREMENT,
        `project_name` varchar(255) NOT NULL,
        `abstract` text DEFAULT NULL,
        `team_id` int(11) NOT NULL,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `progress` text DEFAULT NULL,
        `creation_datetime` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`project_id`),
        KEY `team_id` (`team_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_projects_query)) {
        echo "Projects table created successfully.<br>";
    } else {
        echo "Error creating projects table: " . $conn->error . "<br>";
    }
} else {
    echo "Projects table exists.<br>";
}

// Check tasks table
$tasks_table_exists = $conn->query("SHOW TABLES LIKE 'tasks'")->num_rows > 0;

if (!$tasks_table_exists) {
    echo "Creating tasks table...<br>";
    // Create tasks table
    $create_tasks_query = "CREATE TABLE `tasks` (
        `task_id` int(11) NOT NULL AUTO_INCREMENT,
        `team_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `assigned_to` int(11) NOT NULL,
        `assigned_to_name` varchar(100) DEFAULT NULL,
        `assigned_by` int(11) NOT NULL,
        `due_date` date DEFAULT NULL,
        `status` enum('not-started','in-progress','completed') DEFAULT 'not-started',
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`task_id`),
        KEY `team_id` (`team_id`),
        KEY `assigned_to` (`assigned_to`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_tasks_query)) {
        echo "Tasks table created successfully.<br>";
    } else {
        echo "Error creating tasks table: " . $conn->error . "<br>";
    }
} else {
    echo "Tasks table exists.<br>";
}

// Check files table
$files_table_exists = $conn->query("SHOW TABLES LIKE 'files'")->num_rows > 0;

if (!$files_table_exists) {
    echo "Creating files table...<br>";
    // Create files table
    $create_files_query = "CREATE TABLE `files` (
        `file_id` int(11) NOT NULL AUTO_INCREMENT,
        `team_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `path` varchar(255) NOT NULL,
        `type` varchar(100) DEFAULT NULL,
        `size` int(11) DEFAULT NULL,
        `uploaded_by` int(11) NOT NULL,
        `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`file_id`),
        KEY `team_id` (`team_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_files_query)) {
        echo "Files table created successfully.<br>";
    } else {
        echo "Error creating files table: " . $conn->error . "<br>";
    }
} else {
    echo "Files table exists.<br>";
}

// Check students table
$students_table_exists = $conn->query("SHOW TABLES LIKE 'students'")->num_rows > 0;

if (!$students_table_exists) {
    echo "Creating students table...<br>";
    // Create students table
    $create_students_query = "CREATE TABLE `students` (
        `student_id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `subject_ids` text DEFAULT NULL,
        PRIMARY KEY (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($create_students_query)) {
        echo "Students table created successfully.<br>";
        
        // Insert some sample students for testing
        $sample_students = [
            ['name' => 'Elon Musk', 'email' => 'elon@example.com'],
            ['name' => 'Steve Jobs', 'email' => 'steve@example.com'],
            ['name' => 'Bill Gates', 'email' => 'bill@example.com'],
            ['name' => 'Mark Zuckerberg', 'email' => 'mark@example.com']
        ];
        
        foreach ($sample_students as $student) {
            $insert_query = "INSERT INTO students (name, email) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ss", $student['name'], $student['email']);
            $stmt->execute();
            $stmt->close();
        }
        
        echo "Sample students added.<br>";
    } else {
        echo "Error creating students table: " . $conn->error . "<br>";
    }
} else {
    echo "Students table exists.<br>";
}

echo "Database check completed.";
?> 