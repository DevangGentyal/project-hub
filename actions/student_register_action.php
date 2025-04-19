<?php
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $roll_no = $_POST['roll_no'] ?? '';
    $prn_no = $_POST['prn'] ?? '';
    $department = $_POST['department'] ?? '';
    $division = $_POST['division'] ?? '';
    $year = $_POST['year'] ?? '';
    $is_leader =  0;
    $team_ids = [];
    $subject_ids = [];
    $email = $_POST['email'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';

    if (
        empty($name) || empty($roll_no) || empty($prn_no) || empty($department) ||
        empty($division) || empty($year) || empty($email) || empty($passwordRaw)
    ) {

        session_start();
        $_SESSION['error_message'] = "Please Enter all Fields";
        header("Location: ../register.php");
        exit();

    }

    // Convert subjects array to JSON
    $teamidsJson = json_encode($team_ids);
    $subjectidsJson = json_encode($subject_ids);
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

    // Check if email exists
    $checkQuery = $conn->prepare("SELECT student_id FROM students WHERE email = ?");
    $checkQuery->bind_param("s", $email);
    $checkQuery->execute();
    $checkQuery->store_result();

    if ($checkQuery->num_rows > 0) {
        session_start();
        $_SESSION['error_message'] = "Email already registered!";
        header("Location: ../register.php");
        exit();
    }

    $sql = "INSERT INTO students (name, roll_no, prn_no, department, division, year, is_leader, team_ids, subject_ids, email, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("siisssissss", $name, $roll_no, $prn_no, $department, $division, $year, $is_leader, $teamidsJson, $subjectidsJson, $email, $password);
        if ($stmt->execute()) {
            header("Location: ../login.php?success=success");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Query error: " . $conn->error;
    }

    $stmt->close();
    $checkQuery->close();
    $conn->close();
}
?>