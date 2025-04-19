<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guide Dashboard - Project Tracking System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/guide_dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2 class="sidebar-title">Guide Dashboard</h2>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="#" class="nav-link active">
                        <i class="ri-dashboard-line nav-icon"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="ri-user-line nav-icon"></i>
                        Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="ri-settings-line nav-icon"></i>
                        Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="auth.logout()">
                        <i class="ri-logout-box-line nav-icon"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

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
            <!-- Subject cards will be dynamically added here -->
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

    <!-- <script src="auth.js"></script> -->
    <!-- <script src="dashboard.js"></script> -->
    <script>
        // Sample subjects data (replace with actual data storage)
        let subjects = [
            { id: 1, name: 'DBMS', code: 'SBJ55', teams: 10 },
            { id: 2, name: 'OOP', code: 'SBJ56', teams: 10 },
            { id: 3, name: 'JAVA', code: 'SBJ57', teams: 10 },
            { id: 4, name: 'AI', code: 'SBJ58', teams: 10 },
            { id: 5, name: 'MATHS', code: 'SBJ59', teams: 10 }
        ];

        // Load subjects into the grid
        function loadSubjects() {
            const grid = document.querySelector('.subject-grid');
            if (!grid) return;
            grid.innerHTML = subjects.map(subject => createSubjectCard(subject)).join('');
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            loadSubjects(); // Load subjects first
        });

        // Create subject card HTML
        function createSubjectCard(subject) {
            return `
                <div class="subject-card" onclick="window.location.href='subject_view.html?id=${subject.id}'">
                    <div class="subject-card-content">
                        <h3 class="subject-title">${subject.name}</h3>
                        <p class="subject-code">#${subject.code}</p>
                    </div>
                    <div class="subject-card-footer">
                        <div class="stat-item">
                            <div class="stat-value">${subject.teams}</div>
                            <div class="stat-label">Teams</div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Generate random subject code
        function generateSubjectCode() {
            const prefix = 'SBJ';
            const timestamp = Date.now().toString().slice(-3); // Use last 3 digits of timestamp
            const randomNum = Math.floor(Math.random() * 90) + 10; // Generate random 2-digit number
            return `${prefix}${timestamp}${randomNum}`; // Combines timestamp and random number for uniqueness
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
            subjectCodeElement.textContent = ''; // Don't generate code immediately
        });

        // Generate code after subject name input
        subjectNameInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' || subjectNameInput.value.length > 0) {
                const generatedCode = generateSubjectCode();
                subjectCodeElement.textContent = generatedCode;
                
                // Show the generated code section
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
                id: subjects.length + 1,
                name: subjectNameInput.value,
                code: subjectCodeElement.textContent,
                teams: 0
            };
            
            subjects.push(newSubject);
            loadSubjects();
            
            addSubjectModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === addSubjectModal) {
                addSubjectModal.style.display = 'none';
            }
        });
    </script>
</body>
</html> 