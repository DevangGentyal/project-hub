<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject View - Project Tracking System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/subject_view.css">
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
                    <a href="guide_dashboard.html" class="nav-link">
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
        <div class="subject-header">
            <div class="breadcrumb">
                <a href="guide_dashboard.html" class="nav-link">
                    <i class="ri-arrow-left-line"></i>
                    Back to Dashboard
                </a>
                <span>/</span>
                <span id="subject-name">DBMS</span>
            </div>
            <button class="create-team-btn" id="create-team">
                <i class="ri-team-line"></i>
                Create Team
            </button>
        </div>

        <div class="team-grid">
            <!-- Team cards will be dynamically added here -->
        </div>
    </main>

    <!-- Create Team Modal -->
    <div class="modal" id="create-team-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Team</h3>
                <button class="modal-close">&times;</button>
            </div>
            
            <form id="create-team-form">
                <div class="form-group">
                    <label for="team-name" class="form-label">Team Name</label>
                    <input type="text" id="team-name" class="form-control" required>
                </div>
                
                <div class="generated-code">
                    <span class="generated-code-text" id="team-code"></span>
                    <button type="button" class="copy-btn" id="copy-code">
                        <i class="ri-file-copy-line"></i>
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Team</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Copy Success Message -->
    <div class="copy-success" id="copy-success">
        Code copied to clipboard!
    </div>

    <!-- <script src="auth.js"></script> -->
    <script>
        // Get subject ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const subjectId = urlParams.get('id');

        // Sample teams data (replace with actual data storage)
        let teams = [
            { id: 1, name: 'Team 1', code: '5602', progress: 70 },
            { id: 2, name: 'Team 2', code: '5603', progress: 70 },
            { id: 3, name: 'Team 3', code: '5604', progress: 70 },
            { id: 4, name: 'Team 4', code: '5605', progress: 70 },
            { id: 5, name: 'Team 5', code: '5606', progress: 70 },
            { id: 6, name: 'Team 6', code: '5607', progress: 70 },
            { id: 7, name: 'Team 7', code: '5608', progress: 70 },
            { id: 8, name: 'Team 8', code: '5609', progress: 70 },
            { id: 9, name: 'Team 9', code: '5610', progress: 70 },
            { id: 10, name: 'Team 10', code: '5611', progress: 70 }
        ];

        // Load teams into the grid
        function loadTeams() {
            const grid = document.querySelector('.team-grid');
            grid.innerHTML = teams.map(team => createTeamCard(team)).join('');
        }

        // Create team card HTML
        function createTeamCard(team) {
            return `
                <div class="team-card" onclick="window.location.href='team_view.html?id=${team.id}&subject=${subjectId}'">
                    <h3 class="team-name">${team.name}</h3>
                    <p class="team-code">#${team.code}</p>
                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-label">Progress</span>
                            <span class="progress-value">${team.progress}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${team.progress}%"></div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Generate random team code
        function generateTeamCode() {
            const timestamp = Date.now().toString().slice(-3);
            const randomNum = Math.floor(Math.random() * 90) + 10;
            return `${timestamp}${randomNum}`;
        }

        // Modal handling
        const createTeamBtn = document.getElementById('create-team');
        const createTeamModal = document.getElementById('create-team-modal');
        const modalClose = document.querySelector('.modal-close');
        const teamNameInput = document.getElementById('team-name');
        const teamCodeElement = document.getElementById('team-code');
        const copyBtn = document.getElementById('copy-code');
        const copySuccess = document.getElementById('copy-success');
        
        createTeamBtn.addEventListener('click', () => {
            createTeamModal.style.display = 'block';
            teamNameInput.value = '';
            teamCodeElement.textContent = '';
        });
        
        modalClose.addEventListener('click', () => {
            createTeamModal.style.display = 'none';
        });

        // Generate code after team name input
        teamNameInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter' || teamNameInput.value.length > 0) {
                const generatedCode = generateTeamCode();
                teamCodeElement.textContent = generatedCode;
                document.querySelector('.generated-code').style.display = 'flex';
            } else {
                document.querySelector('.generated-code').style.display = 'none';
            }
        });
        
        // Copy code to clipboard
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(teamCodeElement.textContent).then(() => {
                copySuccess.style.display = 'block';
                setTimeout(() => {
                    copySuccess.style.display = 'none';
                }, 2000);
            });
        });
        
        // Form submission
        document.getElementById('create-team-form').addEventListener('submit', (e) => {
            e.preventDefault();
            
            const newTeam = {
                id: teams.length + 1,
                name: teamNameInput.value,
                code: teamCodeElement.textContent,
                progress: 0
            };
            
            teams.push(newTeam);
            loadTeams();
            
            createTeamModal.style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === createTeamModal) {
                createTeamModal.style.display = 'none';
            }
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            loadTeams();
        });
    </script>
</body>
</html> 