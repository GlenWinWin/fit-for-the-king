<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email) && !empty($password)) {
        $faithFit = new FaithFitFunctions();
        $admin = $faithFit->authenticateAdmin($email, $password);
        
        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['is_admin'] = true;
            
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin credentials';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

// Handle admin registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_register'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_key = trim($_POST['admin_key']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($admin_key)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($admin_key !== 'FAITHFIT_ADMIN_2024') {
        $error = 'Invalid admin registration key';
    } else {
        $faithFit = new FaithFitFunctions();
        $result = $faithFit->registerAdmin($first_name, $last_name, $email, $password);
        
        if ($result['success']) {
            $success = 'Admin account created successfully! Please login.';
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
    <title>Fit for the King | Admin Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-dark: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            --gradient-card: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
            --gradient-admin: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            --gradient-admin-hover: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gradient-dark);
            color: #fff;
            min-height: 100vh;
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background: 
                radial-gradient(circle at 20% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(124, 58, 237, 0.05) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }

        .auth-wrapper {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            max-width: 1200px;
            width: 100%;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid var(--glass-border);
            position: relative;
            z-index: 1;
            min-height: 700px;
        }

        .auth-hero {
            background: var(--gradient-admin);
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
            background: 
                radial-gradient(circle at 30% 70%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255,255,255,0.05) 0%, transparent 50%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }

        .hero-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.3));
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.1);
            padding: 12px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .feature-item i {
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 10px;
            font-size: 1rem;
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
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .auth-logo i {
            font-size: 2.2rem;
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .auth-tabs {
            display: flex;
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 6px;
            margin-bottom: 2.5rem;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
        }

        .auth-tab {
            flex: 1;
            text-align: center;
            padding: 15px 20px;
            border: none;
            background: none;
            color: #94a3b8;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .auth-tab.active {
            color: white;
            background: var(--gradient-admin);
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
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
            color: #e2e8f0;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
            background: rgba(255,255,255,0.08);
        }

        .form-control::placeholder {
            color: #64748b;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #8B5CF6;
        }

        .name-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-auth {
            width: 100%;
            padding: 16px 20px;
            background: var(--gradient-admin);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-auth:hover {
            background: var(--gradient-admin-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        .btn-auth:active {
            transform: translateY(0);
        }

        .auth-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .auth-footer a {
            color: #8B5CF6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-footer a:hover {
            color: #7C3AED;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            animation: slideIn 0.5s ease;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
        }

        .message.error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }

        .message.success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border-color: rgba(34, 197, 94, 0.2);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: rgba(255,255,255,0.1);
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

        .strength-weak { background: #ef4444; width: 33%; }
        .strength-medium { background: #f59e0b; width: 66%; }
        .strength-strong { background: #22c55e; width: 100%; }

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
            }
            
            .btn-auth {
                padding: 14px 16px;
            }
        }

        /* Modern scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: #8B5CF6;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #7C3AED;
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
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1 class="hero-title">Admin Portal</h1>
                    <p class="hero-subtitle">Fit for the King Administration System</p>
                    
                    <div class="hero-features">
                        <div class="feature-item">
                            <i class="fas fa-users-cog"></i>
                            <span>User Management</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics & Reports</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-cog"></i>
                            <span>System Configuration</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-database"></i>
                            <span>Content Management</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auth Forms Section -->
            <div class="auth-content">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-shield-alt"></i>
                        <span>Fit for the King - Admin</span>
                    </div>
                    <h1 class="auth-title">Administrator Access</h1>
                    <p class="auth-subtitle">Secure admin portal for Fit for the King management</p>
                </div>

                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="auth-tabs">
                    <button class="auth-tab active" data-tab="login">Admin Sign In</button>
                    <button class="auth-tab" data-tab="register">Register Admin</button>
                </div>

                <!-- Admin Login Form -->
                <form class="auth-form active" method="POST" action="admin.php" data-tab="login">
                    <input type="hidden" name="admin_login" value="1">
                    <div class="form-group">
                        <label for="login-email">Admin Email</label>
                        <input type="email" class="form-control" id="login-email" name="email" required 
                               placeholder="Enter admin email"
                               value="<?php echo isset($_POST['email']) && !isset($_POST['admin_register']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" class="form-control" id="login-password" name="password" required
                               placeholder="Enter admin password">
                        <button type="button" class="password-toggle" data-target="login-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" class="btn-auth">
                        <i class="fas fa-sign-in-alt"></i>
                        Access Admin Dashboard
                    </button>
                </form>

                <!-- Admin Registration Form -->
                <form class="auth-form" method="POST" action="admin.php" data-tab="register">
                    <input type="hidden" name="admin_register" value="1">
                    <div class="form-group name-fields">
                        <div>
                            <label for="register-first-name">First Name</label>
                            <input type="text" class="form-control" id="register-first-name" name="first_name" required 
                                   placeholder="John"
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div>
                            <label for="register-last-name">Last Name</label>
                            <input type="text" class="form-control" id="register-last-name" name="last_name" required 
                                   placeholder="Doe"
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Admin Email</label>
                        <input type="email" class="form-control" id="register-email" name="email" required 
                               placeholder="admin@faithfit.com"
                               value="<?php echo isset($_POST['email']) && isset($_POST['admin_register']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" class="form-control" id="register-password" name="password" required 
                               minlength="6" placeholder="At least 6 characters">
                        <button type="button" class="password-toggle" data-target="register-password">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="password-strength-bar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-confirm-password">Confirm Password</label>
                        <input type="password" class="form-control" id="register-confirm-password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <button type="button" class="password-toggle" data-target="register-confirm-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="admin-key">Admin Registration Key</label>
                        <input type="password" class="form-control" id="admin-key" name="admin_key" required
                               placeholder="Enter admin registration key">
                        <small style="color: #64748b; margin-top: 0.5rem; display: block;">
                            Contact system administrator for the registration key
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-auth">
                        <i class="fas fa-user-shield"></i>
                        Register Admin Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p><a href="login.php">← Back to User Login</a></p>
                </div>
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
        const passwordInput = document.getElementById('register-password');
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
        const registerForm = document.querySelector('form[data-tab="register"]');
        const confirmPasswordInput = document.getElementById('register-confirm-password');
        
        if (registerForm && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
            
            registerForm.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match! Please make sure both passwords are identical.');
                    confirmPasswordInput.focus();
                }
            });
        }

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