
<?php
session_start();
if (isset($_SESSION['error_message'])) {
    echo "<script>alert('{$_SESSION['error_message']}');</script>";
    unset($_SESSION['error_message']);
}
require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/auth.css">
<style>
    /* Select field styling */
    .input-wrapper {
        position: relative;
        width: 100%;
    }

    .input-wrapper i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
    }

    .input-wrapper select {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background-color: white;
        font-size: 1rem;
        color: #1a202c;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .input-wrapper select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .input-wrapper select:hover {
        border-color: #cbd5e0;
    }

    .input-wrapper select option {
        padding: 0.75rem 1rem;
        background-color: white;
        color: #1a202c;
    }

    .input-wrapper select option:checked {
        background-color: #3b82f6;
        color: white;
    }

    /* Error message styling */
    .error-message {
        background-color: #fee2e2;
        color: #dc2626;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    /* Hidden class */
    .hidden {
        display: none;
    }

    /* Select wrapper specific styles */
    .select-wrapper {
        position: relative;
    }

    .select-wrapper::after {
        content: '';
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid #6b7280;
        pointer-events: none;
    }
</style>
<!-- Register Section -->
<section class="auth-section" style="margin-top: 5rem;">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="auth-logo">
                    <i class="ri-code-box-line"></i>
                    <span>ProjectHub</span>
                </a>
                <h1>Create an account</h1>
                <p>Please select your role and fill in your details</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="actions/student_register_action.php" id="registerForm">
                <div class="role-selector">
                    <label class="role-option">
                        <input type="radio" name="role" value="guide" <?php echo ($_POST['role'] ?? '') === 'guide' ? 'checked' : ''; ?>>
                        <div class="role-content">
                            <div class="role-icon">
                                <i class="ri-user-star-line"></i>
                            </div>
                            <div class="role-info">
                                <h3>Guide</h3>
                                <p>Teacher/Supervisor</p>
                            </div>
                        </div>
                    </label>

                    <label class="role-option">
                        <input type="radio" name="role" value="student" <?php echo ($_POST['role'] ?? 'student') === 'student' ? 'checked' : ''; ?>>
                        <div class="role-content">
                            <div class="role-icon">
                                <i class="ri-user-line"></i>
                            </div>
                            <div class="role-info">
                                <h3>Student</h3>
                                <p>Team Member</p>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="form-group">
                    <label for="name">Full name</label>
                    <div class="input-wrapper">
                        <i class="ri-user-line"></i>
                        <input type="text" id="name" name="name"
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                            placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <div class="input-wrapper">
                        <i class="ri-mail-line"></i>
                        <input type="email" id="email" name="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="Enter your email" required>
                    </div>
                </div>

                <div id="studentFields"
                    class="<?php echo ($_POST['role'] ?? 'student') !== 'guide' ? '' : 'hidden'; ?>">
                    <div class="form-group">
                        <label for="prn">Roll No</label>
                        <div class="input-wrapper">
                            <i class="ri-user-line"></i>
                            <input type="text" id="roll_no" name="roll_no"
                                value="<?php echo htmlspecialchars($_POST['roll_no'] ?? ''); ?>"
                                placeholder="Enter your Roll number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="prn">PRN No</label>
                        <div class="input-wrapper">
                            <i class="ri-user-line"></i>
                            <input type="text" id="prn" name="prn"
                                value="<?php echo htmlspecialchars($_POST['prn'] ?? ''); ?>"
                                placeholder="Enter your PRN number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <div class="input-wrapper">
                            <i class="ri-building-line"></i>
                            <input type="text" id="department" name="department"
                                value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>"
                                placeholder="Enter your department">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="division">Division</label>
                        <div class="input-wrapper select-wrapper">
                            <i class="ri-group-line"></i>
                            <select id="division" name="division">
                                <option value="">-- Select Division --</option>
                                <option value="A" <?php echo ($_POST['division'] ?? '') === 'A' ? 'selected' : ''; ?>>A
                                </option>
                                <option value="B" <?php echo ($_POST['division'] ?? '') === 'B' ? 'selected' : ''; ?>>B
                                </option>
                                <option value="C" <?php echo ($_POST['division'] ?? '') === 'C' ? 'selected' : ''; ?>>C
                                </option>
                                <option value="D" <?php echo ($_POST['division'] ?? '') === 'D' ? 'selected' : ''; ?>>D
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="year">Year</label>
                        <div class="input-wrapper select-wrapper">
                            <i class="ri-calendar-line"></i>
                            <select id="year" name="year">
                                <option value="">-- Select Year --</option>
                                <option value="FY" <?php echo ($_POST['year'] ?? '') === 'FY' ? 'selected' : ''; ?>>FY
                                </option>
                                <option value="SY" <?php echo ($_POST['year'] ?? '') === 'SY' ? 'selected' : ''; ?>>SY
                                </option>
                                <option value="TY" <?php echo ($_POST['year'] ?? '') === 'TY' ? 'selected' : ''; ?>>TY
                                </option>
                                <option value="BY" <?php echo ($_POST['year'] ?? '') === 'BY' ? 'selected' : ''; ?>>BTech
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="ri-lock-line"></i>
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="toggle-password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm password</label>
                    <div class="input-wrapper">
                        <i class="ri-lock-line"></i>
                        <input type="password" id="confirmPassword" name="confirm_password"
                            placeholder="Confirm your password" required>
                        <button type="button" class="toggle-password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="terms" required>
                        <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></span>
                    </label>
                </div>

                <button type="submit" class="auth-submit">
                    Create account
                    <i class="ri-arrow-right-line"></i>
                </button>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign in</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const roleRadios = document.querySelectorAll('input[name="role"]');
        const studentFields = document.getElementById('studentFields');
        const toggleButtons = document.querySelectorAll('.toggle-password');
        const form = document.getElementById('registerForm');
        const roleRadiosoption = document.getElementsByName('role');

        function updateFormAction() {
            console.log("Role Changed");
            const selectedRole = [...roleRadiosoption].find(r => r.checked)?.value;

            if (selectedRole === 'guide') {
                form.action = 'actions/guide_register_action.php';
            } else if (selectedRole === 'student') {
                form.action = 'actions/student_register_action.php';
            } else {
                form.action = '';
            }
        }
        // Attach change event to each radio input
        roleRadiosoption.forEach(radio => {
            radio.addEventListener('change', updateFormAction);
        });

        // Prevent form submission if no action is set (optional safety)
        form.addEventListener('submit', function (e) {
            if (!form.action) {
                e.preventDefault();
                alert("Please select a role before submitting.");
            }
        });

        // Set initial state of student fields based on selected role
        function updateStudentFields() {
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            studentFields.classList.toggle('hidden', selectedRole === 'guide');

            // Make student fields required or not based on role
            const studentInputs = studentFields.querySelectorAll('input, select');
            studentInputs.forEach(input => {
                input.required = (selectedRole === 'student');
            });
        }

        // Initialize fields
        updateStudentFields();

        // Toggle student fields based on role selection
        roleRadios.forEach(radio => {
            radio.addEventListener('change', updateStudentFields);
        });

        // Toggle password visibility
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const input = button.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                button.innerHTML = type === 'password' ?
                    '<i class="ri-eye-line"></i>' :
                    '<i class="ri-eye-off-line"></i>';
            });
        });

        // Email validation on form submit
        form.addEventListener('submit', function (event) {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                event.preventDefault();
                const errorDiv = document.querySelector('.error-message') || document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Please enter a valid email address';
                if (!document.querySelector('.error-message')) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
            }

            // Password validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password.length < 8) {
                event.preventDefault();
                const errorDiv = document.querySelector('.error-message') || document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Password must be at least 8 characters long';
                if (!document.querySelector('.error-message')) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
            } else if (password !== confirmPassword) {
                event.preventDefault();
                const errorDiv = document.querySelector('.error-message') || document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = 'Passwords do not match';
                if (!document.querySelector('.error-message')) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
            }
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleInputs = document.querySelectorAll('input[name="role"]');
        const studentFields = document.getElementById('studentFields');

        roleInputs.forEach(input => {
            input.addEventListener('change', () => {
                if (input.value === 'student') {
                    studentFields.classList.remove('hidden');
                } else {
                    studentFields.classList.add('hidden');
                }
            });
        });
    });
</script>