<?php
include '../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';
    $subject_ids = []; // from checkbox or multi-select

    if (empty($name) || empty($email) || empty($passwordRaw)) {
        die("All fields are required.");
    }

    // Convert subjects array to JSON
    $subjectsidsJson = json_encode($subject_ids);
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

    // Check if email exists
    $checkQuery = $conn->prepare("SELECT guide_id FROM guides WHERE email = ?");
    $checkQuery->bind_param("s", $email);
    $checkQuery->execute();
    $checkQuery->store_result();

    if ($checkQuery->num_rows > 0) {
        session_start();
        $_SESSION['error_message'] = "Email already registered!";
        header("Location: ../register.php");
        exit();
    }

    $sql = "INSERT INTO guides (name, subject_ids, email, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssss", $name, $subjectsidsJson, $email, $password);
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
