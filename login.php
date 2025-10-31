<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $faithFit = new FaithFitFunctions();
        $user = $faithFit->authenticateUser($email, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['theme'] = $user['theme_preference'] ?? 'dark';
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

// Handle signup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $faithFit = new FaithFitFunctions();
        $result = $faithFit->registerUser($first_name, $last_name, $email, $password);
        
        if ($result['success']) {
            $success = 'Account created successfully! Please login.';
            // Switch to login tab after successful registration
            echo '<script>document.addEventListener("DOMContentLoaded", function() { switchToTab("login"); });</script>';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Welcome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #FFC107 0%, #FF8F00 100%);
            --gradient-dark: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            --gradient-card: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-dark);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,193,7,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        .auth-container::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,143,0,0.05) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .auth-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1200px;
            width: 100%;
            background: var(--card-dark);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            z-index: 1;
            min-height: 700px;
        }

        .auth-hero {
            background: var(--gradient-primary);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .auth-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .hero-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .hero-features {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .feature-item i {
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 50%;
            font-size: 0.9rem;
        }

        .auth-content {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--gradient-card);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .auth-logo i {
            font-size: 2.2rem;
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: var(--text-secondary-dark);
            font-size: 1.1rem;
        }

        .auth-tabs {
            display: flex;
            background: var(--background-dark);
            border-radius: 16px;
            padding: 6px;
            margin-bottom: 2.5rem;
            border: 1px solid var(--border-dark);
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 15px 20px;
            border: none;
            background: none;
            color: var(--text-secondary-dark);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .auth-tab.active {
            color: var(--background-dark);
            background: var(--gradient-primary);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }

        .auth-tab.active::before {
            left: 0;
        }

        .auth-form {
            display: none;
            animation: slideIn 0.5s ease;
        }

        .auth-form.active {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: var(--background-dark);
            border: 2px solid var(--border-dark);
            border-radius: 12px;
            color: var(--text-dark);
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            padding-right: 50px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 193, 7, 0.1);
            background: var(--card-dark);
        }

        .form-control::placeholder {
            color: var(--text-secondary-dark);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary-dark);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .password-toggle i {
            display: block;
            line-height: 1;
        }

        .password-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--primary);
        }

        .name-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-auth {
            width: 100%;
            padding: 16px 20px;
            background: var(--gradient-primary);
            border: none;
            border-radius: 12px;
            color: var(--background-dark);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-auth::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
        }

        .btn-auth:hover::before {
            left: 100%;
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-dark);
            color: var(--text-secondary-dark);
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            animation: slideIn 0.5s ease;
            border: 1px solid transparent;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border-color: rgba(244, 67, 54, 0.2);
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            border-color: rgba(76, 175, 80, 0.2);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--border-dark);
            border-radius: 2px;
            margin-top: 0.25rem;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: #f44336; width: 33%; }
        .strength-medium { background: #ff9800; width: 66%; }
        .strength-strong { background: #4caf50; width: 100%; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .auth-hero {
                display: none;
            }
            
            .auth-content {
                padding: 50px 40px;
            }
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 10px;
            }
            
            .auth-content {
                padding: 40px 30px;
            }
            
            .auth-title {
                font-size: 1.8rem;
            }
            
            .name-fields {
                grid-template-columns: 1fr;
            }
            
            .hero-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .auth-content {
                padding: 30px 20px;
            }
            
            .auth-tab {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .form-control {
                padding: 14px 16px;
                padding-right: 45px;
            }
            
            .btn-auth {
                padding: 14px 16px;
            }
            
            .password-toggle {
                width: 25px;
                height: 25px;
                right: 12px;
            }
        }

        /* Demo credentials box */
        .demo-credentials {
            background: rgba(255,193,7,0.1);
            border: 1px solid rgba(255,193,7,0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }

        .demo-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-accounts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .demo-account {
            background: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .demo-email {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }

        .demo-password {
            color: var(--text-secondary-dark);
            font-size: 0.8rem;
            font-family: monospace;
        }

        @media (max-width: 480px) {
            .demo-accounts {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="dark-mode">
    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- Hero Section -->
            <div class="auth-hero">
                <div class="hero-content">
                    <div class="hero-icon">
                        <img src="imgs/white-logo.png" width="100" height="100"/>
                    </div>
                    <h1 class="hero-title">Fit for the King</h1>
                    <p class="hero-subtitle">Increasing faith while building muscles</p>
                    
                    <div class="hero-features">
                        <div class="feature-item">
                            <i class="fas fa-pray"></i>
                            <span>Daily Devotions & Prayer</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Progress Tracking</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Supportive Community</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-heart"></i>
                            <span>Faith-Based Fitness</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auth Forms Section -->
            <div class="auth-content">
                <div class="auth-header">
                    <div class="auth-logo">
                        <img src="imgs/dark-logo.png" width="50" height="50"/>
                        <span>Fit for the King</span>
                    </div>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Join thousands transforming their lives</p>
                </div>

                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="login">Sign In</button>
                    <button class="auth-tab" data-tab="signup">Create Account</button>
                </div>

                <!-- Login Form -->
                <form class="auth-form active" method="POST" action="login.php" data-tab="login">
                    <input type="hidden" name="login" value="1">
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" class="form-control" id="login-email" name="email" required 
                               placeholder="Enter your email"
                               value="<?php echo isset($_POST['email']) && !isset($_POST['signup']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" class="form-control" id="login-password" name="password" required
                               placeholder="Enter your password">
                        <button type="button" class="password-toggle" data-target="login-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn-auth">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In to Your Account
                    </button>
                </form>

                <!-- Signup Form -->
                <form class="auth-form" method="POST" action="login.php" data-tab="signup">
                    <input type="hidden" name="signup" value="1">
                    <div class="form-group name-fields">
                        <div>
                            <label for="signup-first-name">First Name</label>
                            <input type="text" class="form-control" id="signup-first-name" name="first_name" required 
                                   placeholder="John"
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div>
                            <label for="signup-last-name">Last Name</label>
                            <input type="text" class="form-control" id="signup-last-name" name="last_name" required 
                                   placeholder="Doe"
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-email">Email Address</label>
                        <input type="email" class="form-control" id="signup-email" name="email" required 
                               placeholder="john.doe@example.com"
                               value="<?php echo isset($_POST['email']) && isset($_POST['signup']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" class="form-control" id="signup-password" name="password" required 
                               minlength="6" placeholder="At least 6 characters">
                        <button type="button" class="password-toggle" data-target="signup-password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="password-strength-bar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-confirm-password">Confirm Password</label>
                        <input type="password" class="form-control" id="signup-confirm-password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <button type="button" class="password-toggle" data-target="signup-confirm-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn-auth">
                        <i class="fas fa-user-plus"></i>
                        Join the Kingdom
                    </button>
                </form>

                <!-- <div class="auth-footer">
                    <p>By continuing, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>
                </div> -->
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        const authTabs = document.querySelectorAll('.auth-tab');
        const authForms = document.querySelectorAll('.auth-form');
        
        function switchToTab(tabName) {
            authTabs.forEach(tab => {
                tab.classList.toggle('active', tab.getAttribute('data-tab') === tabName);
            });
            authForms.forEach(form => {
                form.classList.toggle('active', form.getAttribute('data-tab') === tabName);
            });
        }
        
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                switchToTab(targetTab);
                
                // Clear messages when switching tabs
                const messages = document.querySelectorAll('.message');
                messages.forEach(message => message.style.display = 'none');
            });
        });

        // Password toggle functionality
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('signup-password');
        const strengthBar = document.getElementById('password-strength-bar');
        
        if (passwordInput && strengthBar) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;
                
                strengthBar.className = 'strength-fill';
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength <= 4) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
            });
        }

        // Password confirmation validation
        const signupForm = document.querySelector('form[data-tab="signup"]');
        const confirmPasswordInput = document.getElementById('signup-confirm-password');
        
        if (signupForm && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.style.borderColor = '#f44336';
                    this.style.boxShadow = '0 0 0 4px rgba(244, 67, 54, 0.1)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
            
            signupForm.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match! Please make sure both passwords are identical.');
                    confirmPasswordInput.focus();
                }
            });
        }

        // Auto-fill demo credentials
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                if (this.getAttribute('data-tab') === 'login') {
                    setTimeout(() => {
                        document.getElementById('login-email').value = 'john@example.com';
                        document.getElementById('login-password').value = 'password123';
                    }, 300);
                }
            });
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>