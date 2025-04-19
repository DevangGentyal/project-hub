<?php
// ==== PHP CODE AREA: SESSION HANDLING ====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$student_id = intval($_SESSION['user_id']);

include 'includes/db_connect.php';

// Fetch student data including subject_ids
$student_query = "SELECT name, subject_ids FROM students WHERE student_id = $student_id";
$student_result = $conn->query($student_query);
$student_data = $student_result->fetch_assoc();

// Initialize subjects array
$subjects = [];

// Check if student has any subjects
if ($student_data && !empty($student_data['subject_ids'])) {
    // Decode subject_ids JSON array
    $subject_codes = json_decode($student_data['subject_ids'], true);
    
    if (is_array($subject_codes) && count($subject_codes) > 0) {
        // Format subject codes for SQL IN query - escape each code properly
        $escaped_codes = array_map(function($code) use ($conn) {
            return "'" . $conn->real_escape_string($code) . "'";
        }, $subject_codes);
        
        $subject_codes_str = implode(',', $escaped_codes);
        
        // Fetch subjects the student is enrolled in
        $subjects_query = "SELECT * FROM subjects WHERE subject_code IN ($subject_codes_str)";
        $subjects = $conn->query($subjects_query);
    }
}
// ==== END PHP CODE AREA ====

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/student_dashboard.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1>Students Dashboard</h1>
                <div class="student-info">
                    <?php
                    if ($student_data) {
                        echo '<p>Welcome, ' . htmlspecialchars($student_data['name']) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Subjects Grid -->
            <div class="subjects-grid">
                <?php
                // ==== PHP CODE AREA: SUBJECTS DATA RETRIEVAL ====
                if ($subjects && $subjects->num_rows > 0) {
                    while ($subject = $subjects->fetch_assoc()) {
                        // Get guide name for this subject
                        $guide_id = $subject['guide_id'];
                        $guide_query = "SELECT name FROM guides WHERE guide_id = $guide_id";
                        $guide_result = $conn->query($guide_query);
                        $guide_name = ($guide_result && $guide_data = $guide_result->fetch_assoc()) 
                                     ? $guide_data['name'] : 'Unknown';
                        
                        ?>
                        <div class="subject-card" data-subject="<?php echo htmlspecialchars($subject['subject_id']); ?>">
                            <div class="subject-content">
                                <h2 class="subject-title"><?php echo htmlspecialchars($subject['subject_name']); ?></h2>
                                <p class="guide-name">Guide: <?php echo htmlspecialchars($guide_name); ?></p>
                                <p class="subject-code"><?php echo htmlspecialchars($subject['subject_code']); ?></p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="no-subjects">You are not enrolled in any subjects yet. Join a subject to get started.</p>';
                }
                // ==== END PHP CODE AREA ====
                ?>
            </div>

            <!-- Join Subject Section -->
            <div class="join-subject-section">
                <button class="join-subject-btn">
                    <i class="ri-add-line"></i>
                    Join Subject
                </button>

                <!-- Join Subject Modal -->
                <div class="join-subject-modal" style="display: none;">
                    <div class="modal-content">
                        <h2>Enter Subject Code</h2>
                        <div class="code-input-wrapper">
                            <input type="text" id="subjectCode" placeholder="" maxlength="10">
                        </div>
                        <div class="modal-actions">
                            <button class="join-btn">Join</button>
                            <button class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const joinSubjectBtn = document.querySelector('.join-subject-btn');
            const modal = document.querySelector('.join-subject-modal');
            const cancelBtn = modal.querySelector('.cancel-btn');
            const joinBtn = modal.querySelector('.join-btn');
            const subjectCodeInput = document.getElementById('subjectCode');
            const subjectCards = document.querySelectorAll('.subject-card');

            // Show modal
            joinSubjectBtn.addEventListener('click', () => {
                modal.style.display = 'flex';
                subjectCodeInput.focus();
            });

            // Hide modal
            cancelBtn.addEventListener('click', () => {
                modal.style.display = 'none';
                subjectCodeInput.value = '';
            });

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    subjectCodeInput.value = '';
                }
            });

            // Join subject
            joinBtn.addEventListener('click', () => {
                const code = subjectCodeInput.value.trim();
                if (code) {
                    // Send AJAX request to join subject
                    fetch('join_subject.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            subject_code: code
                        })
                    })
                    .then(response => {
                        // Check if response is valid JSON
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        }
                        throw new Error('Server returned non-JSON response');
                    })
                    .then(data => {
                        if (data.success) {
                            // Reload page to show new subject
                            window.location.reload();
                        } else {
                            alert(data.message || 'Invalid subject code. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                    
                    modal.style.display = 'none';
                    subjectCodeInput.value = '';
                }
            });

            // Navigate to subject view
            subjectCards.forEach(card => {
                card.addEventListener('click', () => {
                    const subjectId = card.dataset.subject;
                    
                    // First, check if student is already in a team for this subject
                    fetch(`check_team_membership.php?student_id=${<?php echo $student_id; ?>}&subject_id=${subjectId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.is_member && data.team_id) {
                            // Student is already in a team, navigate to team view
                            window.location.href = `team_view.php?team_id=${data.team_id}&subject_id=${subjectId}`;
                        } else {
                            // Student needs to join a team first, navigate to join team page
                            window.location.href = `join_team.php?subject_id=${subjectId}`;
                        }
                    })
                    .catch(error => {
                        console.error('Error checking team membership:', error);
                        // Fallback to join team page
                        window.location.href = `join_team.php?subject_id=${subjectId}`;
                    });
                });
            });

            // Handle input formatting
            subjectCodeInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                e.target.value = value.slice(0, 10);
            });
        });
    </script>

<?php require_once 'includes/footer.php'; ?> 