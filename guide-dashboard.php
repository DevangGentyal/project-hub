<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$guide_id = intval($_SESSION['user_id']);

include 'includes/db_connect.php';

// Fetch subjects created by this guide
$subjects = $conn->query("SELECT * FROM subjects WHERE guide_id = $guide_id");

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>
<link rel="stylesheet" href="assets/css/guide_dashboard.css">

<style>
/* Notification styles */
.notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #fff;
    border-radius: 4px;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
    max-width: 400px;
}

.notification.success {
    border-left: 4px solid #4CAF50;
}

.notification.error {
    border-left: 4px solid #F44336;
}

.notification i {
    margin-right: 10px;
    font-size: 20px;
}

.notification.success i {
    color: #4CAF50;
}

.notification.error i {
    color: #F44336;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<!-- Main Content -->
<main class="main-content">
    <div class="dashboard-header">
        <h1 class="dashboard-title">My Subjects</h1>
        <button class="add-subject-btn" id="add-subject">
            <i class="ri-add-line"></i>
            Add Subject
        </button>
    </div>

    <div class="subject-grid">
        <?php
        if ($subjects->num_rows > 0) {
            while ($sub = $subjects->fetch_assoc()) {
                $code = $conn->real_escape_string($sub['subject_code']);
                $subjectId = $sub["subject_id"];
                $countResult = $conn->query("SELECT COUNT(*) as total FROM teams WHERE subject_id=\"$subjectId\"");

                $count = $countResult->fetch_assoc();
                ?>
                <div class="subject-card"
                    onclick="window.location.href='subject_view.php?subject_id=<?php echo htmlspecialchars($subjectId); ?>'">
                    <div class="subject-card-content">
                        <h3 class="subject-title"><?php echo htmlspecialchars($sub['subject_name']); ?></h3>
                        <p class="subject-code">#<?php echo htmlspecialchars($sub['subject_code']); ?></p>
                    </div>
                    <div class="subject-card-footer">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $count['total']; ?></div>
                            <div class="stat-label">Teams</div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p>No subjects added yet.</p>';
        }
        ?>
    </div>
</main>

<!-- Add Subject Modal -->
<div class="modal" id="add-subject-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Subject</h3>
            <button class="modal-close">&times;</button>
        </div>

        <form id="add-subject-form">
            <div class="form-group">
                <label for="subject-name" class="form-label">Subject Name</label>
                <input type="text" id="subject-name" class="form-control" required>
            </div>

            <div class="generated-code">
                <span class="generated-code-text" id="subject-code"></span>
                <button type="button" class="copy-btn" id="copy-code">
                    <i class="ri-file-copy-line"></i>
                </button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Subject</button>
            </div>
        </form>
    </div>
</div>

<!-- Copy Success Message -->
<div class="copy-success" id="copy-success">
    Code copied to clipboard!
</div>

<script>
    // Generate random subject code
    function generateSubjectCode() {
        const prefix = 'SBJ';
        const timestamp = Date.now().toString().slice(-3);
        const randomNum = Math.floor(Math.random() * 90) + 10;
        return `${prefix}${timestamp}${randomNum}`;
    }

    // Modal handling
    const addSubjectBtn = document.getElementById('add-subject');
    const addSubjectModal = document.getElementById('add-subject-modal');
    const modalClose = document.querySelector('.modal-close');
    const subjectNameInput = document.getElementById('subject-name');
    const subjectCodeElement = document.getElementById('subject-code');
    const copyBtn = document.getElementById('copy-code');
    const copySuccess = document.getElementById('copy-success');

    addSubjectBtn.addEventListener('click', () => {
        addSubjectModal.style.display = 'block';
        subjectNameInput.value = '';
        subjectCodeElement.textContent = '';
    });

    // Generate code after subject name input
    subjectNameInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter' || subjectNameInput.value.length > 0) {
            const generatedCode = generateSubjectCode();
            subjectCodeElement.textContent = generatedCode;
            document.querySelector('.generated-code').style.display = 'flex';
        } else {
            document.querySelector('.generated-code').style.display = 'none';
        }
    });

    // Copy code to clipboard
    copyBtn.addEventListener('click', () => {
        navigator.clipboard.writeText(subjectCodeElement.textContent).then(() => {
            copySuccess.style.display = 'block';
            setTimeout(() => {
                copySuccess.style.display = 'none';
            }, 2000);
        });
    });

    // Form submission
    document.getElementById('add-subject-form').addEventListener('submit', (e) => {
        e.preventDefault();

        const newSubject = {
            name: subjectNameInput.value,
            code: subjectCodeElement.textContent
        };

        // Call PHP to add data to the database
        fetch('add_subject.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(newSubject)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json().catch(error => {
                console.error('Error parsing JSON:', error);
                throw new Error('Invalid JSON response');
            });
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                // Add the new subject card to the grid without reloading
                const subjectGrid = document.querySelector('.subject-grid');
                
                // Create a new subject card element
                const newCard = document.createElement('div');
                newCard.className = 'subject-card';
                newCard.onclick = function() {
                    window.location.href = `subject_view.php?subject_id=${data.subject.id}`;
                };
                
                newCard.innerHTML = `
                    <div class="subject-card-content">
                        <h3 class="subject-title">${data.subject.name}</h3>
                        <p class="subject-code">#${data.subject.code}</p>
                    </div>
                    <div class="subject-card-footer">
                        <div class="stat-item">
                            <div class="stat-value">${data.subject.teams || 0}</div>
                            <div class="stat-label">Teams</div>
                        </div>
                    </div>
                `;
                
                // Add the new card to the grid
                const noSubjectsMsg = subjectGrid.querySelector('p');
                if (noSubjectsMsg && noSubjectsMsg.textContent === 'No subjects added yet.') {
                    subjectGrid.innerHTML = ''; // Clear the "No subjects" message
                }
                
                subjectGrid.appendChild(newCard);
                
                // Close the modal
                addSubjectModal.style.display = 'none';
                
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'notification success';
                notification.innerHTML = `
                    <i class="ri-check-line"></i>
                    <span>Subject "${data.subject.name}" added successfully!</span>
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            } else {
                console.error('Error from server:', data.message);
                alert('Failed to add subject: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred: ' + error.message);
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addSubjectModal) {
            addSubjectModal.style.display = 'none';
        }
    });

    // Close modal when clicking close button
    modalClose.addEventListener('click', () => {
        addSubjectModal.style.display = 'none';
    });
</script>

<?php require_once 'includes/footer.php'; ?>