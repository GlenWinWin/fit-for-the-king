<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get user data
$user = $faithFit->getUserProfile($user_id);
$dashboard_data = $faithFit->getDashboardStats($user_id);

// Get daily todos
$daily_todos = $faithFit->getDailyTodos($user_id);

// Calculate completed tasks
$completed_tasks = 0;
$total_tasks = 3;

// Check if todos were retrieved successfully
if ($daily_todos) {
    if (isset($daily_todos['devotion_completed']) && $daily_todos['devotion_completed']) $completed_tasks++;
    if (isset($daily_todos['weight_logged']) && $daily_todos['weight_logged']) $completed_tasks++;
    if (isset($daily_todos['steps_logged']) && $daily_todos['steps_logged']) $completed_tasks++;
} else {
    // If todos couldn't be retrieved, create a default structure
    $daily_todos = [
        'devotion_completed' => false,
        'weight_logged' => false,
        'steps_logged' => false
    ];
}

$progress_percentage = ($completed_tasks / $total_tasks) * 100;

// Determine theme
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';

// Get current time and date
$current_time = date('H:i');
$current_date = date('l, F j');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Faith • Fitness • Progress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Original Theme Styles */
        .main-content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .time-display {
            margin-bottom: 20px;
        }

        .time {
            font-size: 3rem;
            font-weight: 300;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .date {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .main-card {
            text-align: center;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-title-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-title-section h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-color);
        }

        .streak-badge {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-count {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            background: var(--hover-color);
            padding: 8px 16px;
            border-radius: 20px;
        }

        /* Progress */
        .progress-container {
            margin: 30px 0;
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .progress-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-description {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .divider {
            height: 1px;
            background: var(--border-color);
            margin: 25px 0;
        }

        /* Todo List */
        .todo-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .todo-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: var(--hover-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .todo-item:hover {
            transform: translateY(-2px);
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .todo-item.completed {
            background: rgba(76, 175, 80, 0.1);
            border-color: rgba(76, 175, 80, 0.3);
        }

        .todo-checkbox {
            width: 24px;
            height: 24px;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .todo-item.completed .todo-checkbox {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .todo-checkbox i {
            color: white;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .todo-item.completed .todo-checkbox i {
            opacity: 1;
        }

        .todo-content {
            flex: 1;
            text-align: left;
        }

        .todo-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: var(--text-color);
        }

        .todo-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        .todo-status {
            font-size: 1.2rem;
        }

        .completed-icon {
            color: #4CAF50;
        }

        .not-completed-icon {
            color: var(--border-color);
        }

        /* Devotion Card */
        .devotion-card .card-header {
            justify-content: center;
            text-align: center;
        }

        .devotion-card .card-header h2 {
            font-size: 1.8rem;
            margin: 0;
        }

        .devotion-content {
            text-align: center;
        }

        .bible-verse {
            margin-bottom: 25px;
            padding: 25px;
            background: var(--hover-color);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .verse-text {
            font-size: 1.3rem;
            font-style: italic;
            color: var(--text-color);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .verse-reference {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .devotion-text {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-color);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: var(--hover-color);
            color: var(--text-color);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Floating Action Buttons */
        .floating-action-buttons {
            position: fixed;
            bottom: 90px;
            right: 25px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 1000;
        }

        .floating-action-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            background: #FDB022;
            color: #000;
        }

        .floating-action-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 25px rgba(253, 176, 34, 0.5);
        }

        /* Modals - Updated Design */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background: #2A2A2A;
            border-radius: 15px;
            padding: 40px 35px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            border: 1px solid #3A3A3A;
            animation: modalSlideIn 0.3s ease;
        }

        .light-mode .modal-content {
            background: #ffffff;
            border: 1px solid #e0e0e0;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 0;
            border-bottom: none;
        }

        .modal-title {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: #FDB022;
        }

        .light-mode .modal-title {
            color: #FDB022;
        }

        .close {
            font-size: 1.8rem;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .close:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }

        .light-mode .close:hover {
            color: #000;
            background: rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #fff;
            font-size: 1rem;
        }

        .light-mode .form-group label {
            color: #000;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #3A3A3A;
            border-radius: 8px;
            background: #1A1A1A;
            color: #ccc;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .light-mode .form-control {
            background: #f5f5f5;
            border: 1px solid #ddd;
            color: #333;
        }

        .form-control::placeholder {
            color: #666;
        }

        .light-mode .form-control::placeholder {
            color: #999;
        }

        .form-control:focus {
            outline: none;
            border-color: #FDB022;
            background: #1A1A1A;
            box-shadow: 0 0 0 3px rgba(253, 176, 34, 0.1);
        }

        .light-mode .form-control:focus {
            background: #fff;
            border-color: #FDB022;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 140px;
            line-height: 1.6;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23ccc' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 12px;
            padding-right: 40px;
        }

        .light-mode select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: normal;
            gap: 10px;
            font-size: 0.95rem;
            color: #ccc;
        }

        .light-mode .checkbox-label {
            color: #333;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #FDB022;
        }

        /* Modal Submit Button */
        .modal-content .btn-primary {
            width: 100%;
            padding: 14px 24px;
            font-size: 1.05rem;
            font-weight: 700;
            background: #FDB022;
            color: #000;
            border-radius: 8px;
            margin-top: 10px;
        }

        .modal-content .btn-primary:hover {
            background: #E5A020;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(253, 176, 34, 0.4);
        }

        .modal-content .btn-primary i {
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-header {
                margin-bottom: 30px;
            }

            .time {
                font-size: 2.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .card {
                padding: 25px 20px;
            }

            .card-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .floating-action-buttons {
                bottom: 80px;
                right: 20px;
            }

            .floating-action-btn {
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
            }

            .modal-content {
                padding: 30px 25px;
                margin: 10px;
            }

            .modal-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .time {
                font-size: 2rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .card {
                padding: 20px 15px;
            }

            .todo-item {
                padding: 15px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }

            .modal-content {
                padding: 25px 20px;
            }
        }
        /* Light mode form controls */
        .light-mode .form-control {
            background: #ffffff;
            border: 1px solid #ddd;
            color: #333;
        }

        .light-mode .form-control::placeholder {
            color: #999;
        }

        .light-mode .form-control:focus {
            background: #ffffff;
            border-color: #FDB022;
        }

        /* Fix the select dropdown arrow for light mode */
        .light-mode select.form-control {
            background-color: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        }
        
        select.form-control {
            appearance: none;
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 12px;
            padding-right: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23ccc' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        }

        .light-mode select.form-control {
            background-color: #ffffff;
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
    <div class="app-container">
        <!-- Header -->
        <header class="main-header">
            <div class="header-content">
                <div class="logo">
                    <img src="imgs/dark-logo.png" width="40" height="40" alt="FaithFit Logo"/>
                    <span>Fit for the King</span>
                </div>
                <div class="header-actions">
                    <button class="theme-toggle" id="theme-toggle" title="Switch to <?php echo $theme === 'dark' ? 'light' : 'dark'; ?> mode">
                        <i class="fas fa-<?php echo $theme === 'dark' ? 'moon' : 'sun'; ?>"></i>
                    </button>
                    <div class="user-info">
                        <div class="user-dropdown">
                            <div class="user-avatar">
                                <?php 
                                $initials = substr($user['first_name'] ?? 'J', 0, 1) . substr($user['last_name'] ?? 'D', 0, 1);
                                echo $initials;
                                ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars(($user['first_name'] ?? 'John') . ' ' . ($user['last_name'] ?? 'Doe')); ?></span>
                            <div class="dropdown-content">
                                <a href="profile.php">
                                    <i class="fas fa-user"></i>
                                    Profile
                                </a>
                                <a href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Page -->
            <div class="page active" id="dashboard-page">
                <div class="page-header">
                    <div class="time-display">
                        <div class="time" id="current-time"><?php echo $current_time; ?></div>
                        <div class="date" id="current-date"><?php echo $current_date; ?></div>
                    </div>
                    <h1 class="page-title">Today's Journey</h1>
                    <p class="page-subtitle">Faith • Fitness • Progress</p>
                </div>

                <div class="content-grid">
                    <!-- Daily Goals Card -->
                    <div class="card main-card">
                        <div class="card-header">
                            <div class="card-title-section">
                                <h2>Daily Goals</h2>
                                <div class="streak-badge">
                                    <i class="fas fa-fire"></i>
                                    <span><?php echo $dashboard_data['devotion_streak'] ?? 0; ?> Day Streak</span>
                                </div>
                            </div>
                            <span class="progress-count"><?php echo $completed_tasks; ?>/3 completed</span>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                            </div>
                            <div class="progress-text"><?php echo round($progress_percentage); ?>% Complete</div>
                        </div>

                        <p class="card-description">Complete your daily habits to build a stronger faith and body.</p>

                        <div class="divider"></div>

                        <!-- Daily Tasks -->
                        <div class="todo-list">
                            <!-- Devotion Task -->
                            <div class="todo-item <?php echo $daily_todos['devotion_completed'] ? 'completed' : ''; ?>" data-task="devotion">
                                <div class="todo-checkbox">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="todo-content">
                                    <h3 class="todo-title">Daily Devotion</h3>
                                    <p class="todo-subtitle">
                                        <?php echo $daily_todos['devotion_completed'] ? 'Completed today' : 'Read today\'s devotion'; ?>
                                    </p>
                                </div>
                                <div class="todo-status">
                                    <?php if ($daily_todos['devotion_completed']): ?>
                                        <i class="fas fa-check-circle completed-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-circle not-completed-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Weight Logging Task -->
                            <div class="todo-item <?php echo $daily_todos['weight_logged'] ? 'completed' : ''; ?>" data-task="weight">
                                <div class="todo-checkbox">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="todo-content">
                                    <h3 class="todo-title">Log Your Weight</h3>
                                    <p class="todo-subtitle">
                                        <?php if ($daily_todos['weight_logged'] && isset($dashboard_data['current_weight'])): ?>
                                            Today: <?php echo $dashboard_data['current_weight']['weight_value']; ?> <?php echo strtoupper($dashboard_data['current_weight']['weight_unit']); ?>
                                        <?php else: ?>
                                            Track your progress
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="todo-status">
                                    <?php if ($daily_todos['weight_logged']): ?>
                                        <i class="fas fa-check-circle completed-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-circle not-completed-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Steps Logging Task -->
                            <div class="todo-item <?php echo $daily_todos['steps_logged'] ? 'completed' : ''; ?>" data-task="steps">
                                <div class="todo-checkbox">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="todo-content">
                                    <h3 class="todo-title">Log Your Steps</h3>
                                    <p class="todo-subtitle">
                                        <?php if ($daily_todos['steps_logged']): ?>
                                            Today: <?php echo number_format($dashboard_data['today_steps'] ?? 0); ?> steps
                                        <?php else: ?>
                                            Stay active daily
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="todo-status">
                                    <?php if ($daily_todos['steps_logged']): ?>
                                        <i class="fas fa-check-circle completed-icon"></i>
                                    <?php else: ?>
                                        <i class="fas fa-circle not-completed-icon"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Devotion Card -->
                    <div class="card devotion-card">
                        <div class="card-header">
                            <h2>Today's Devotion</h2>
                            <button class="btn btn-secondary" id="complete-devotion" <?php echo $daily_todos['devotion_completed'] ? 'disabled' : ''; ?>>
                                <i class="fas fa-check"></i>
                                Mark Complete
                            </button>
                        </div>
                        <div class="devotion-content">
                            <div class="bible-verse">
                                <p class="verse-text">"<?php echo htmlspecialchars($devotion['verse_text'] ?? 'I can do all things through Christ who strengthens me.'); ?>"</p>
                                <p class="verse-reference">— <?php echo htmlspecialchars($devotion['verse_reference'] ?? 'Philippians 4:13'); ?></p>
                            </div>
                            <div class="devotion-text">
                                <p><?php echo htmlspecialchars($devotion['devotion_text'] ?? 'In our fitness journey, we often focus on building physical strength through our own efforts. But true strength comes from surrendering to God\'s plan and allowing His power to work through us.'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Floating Action Buttons -->
        <div class="floating-action-buttons">
            <button class="floating-action-btn" id="share-prayer-btn" title="Share Prayer Request">
                <i class="fas fa-hands-praying"></i>
            </button>
        </div>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="community.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Community</span>
            </a>
        </nav>
    </div>

    <!-- Prayer Request Modal -->
    <div class="modal" id="prayer-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Share Prayer Request</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="create_prayer_request">
                <div class="form-group">
                    <label for="testimonial-title">Title (Optional)</label>
                    <input type="text" class="form-control" id="testimonial-title" name="title" placeholder="Brief title for your request">
                </div>
                <div class="form-group">
                    <label for="testimonial-category">Category</label>
                    <select class="form-control" id="testimonial-category" name="category">
                        <option value="fitness">Fitness Goals</option>
                        <option value="healing">Healing</option>
                        <option value="strength">Strength</option>
                        <option value="discipline">Discipline</option>
                        <option value="spiritual">Spiritual Growth</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="testimonial-content">Your Request</label>
                    <textarea class="form-control" id="testimonial-content" name="content" placeholder="Share your prayer request..." required rows="5"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Share Request
                </button>
            </form>
        </div>
    </div>

    <!-- Weight Modal -->
    <div class="modal" id="weight-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <?php echo ($daily_todos['weight_logged'] && isset($dashboard_data['current_weight'])) ? 'Update Your Weight' : 'Log Your Weight'; ?>
                </h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="log_weight">
                <div class="form-group">
                    <label for="weight-value">Weight</label>
                    <input type="number" class="form-control" id="weight-value" name="weight" 
                        step="0.01" min="0"  placeholder="Enter your weight" 
                        value="<?php echo $dashboard_data['current_weight']['weight_value'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="weight-unit">Unit</label>
                    <select class="form-control" id="weight-unit" name="unit">
                        <option value="kg" <?php echo (isset($dashboard_data['current_weight']) && $dashboard_data['current_weight']['weight_unit'] == 'kg') ? 'selected' : ''; ?>>Kilograms (kg)</option>
                        <option value="lbs" <?php echo (isset($dashboard_data['current_weight']) && $dashboard_data['current_weight']['weight_unit'] == 'lbs') ? 'selected' : ''; ?>>Pounds (lbs)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo ($daily_todos['weight_logged'] && isset($dashboard_data['current_weight'])) ? 'Update Weight' : 'Save Weight'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Steps Modal -->
    <div class="modal" id="steps-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <?php echo $daily_todos['steps_logged'] ? 'Update Your Steps' : 'Log Your Steps'; ?>
                </h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="log_steps">
                <div class="form-group">
                    <label for="steps-value">Steps Today</label>
                    <input type="number" class="form-control" id="steps-value" name="steps" 
                        placeholder="Enter steps count" 
                        value="<?php echo $dashboard_data['today_steps'] ?? ''; ?>" required>
                </div>
                <div class="steps-info">
                    <div class="step-goal-display">
                        Daily Goal: <?php echo number_format($dashboard_data['step_goal'] ?? 8000); ?> steps
                    </div>
                    <?php if ($daily_todos['steps_logged']): ?>
                        <div class="step-progress">
                            Progress: <?php echo number_format($dashboard_data['today_steps'] ?? 0); ?> / <?php echo number_format($dashboard_data['step_goal'] ?? 8000); ?> 
                            (<?php echo round((($dashboard_data['today_steps'] ?? 0) / ($dashboard_data['step_goal'] ?? 8000)) * 100); ?>%)
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $daily_todos['steps_logged'] ? 'Update Steps' : 'Save Steps'; ?>
                </button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    // Dashboard-specific JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Update time display
        function updateTime() {
            const now = new Date();
            const timeElement = document.getElementById('current-time');
            const dateElement = document.getElementById('current-date');
            
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });
            }
            
            if (dateElement) {
                dateElement.textContent = now.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
        }
        
        // Update time immediately and every minute
        updateTime();
        setInterval(updateTime, 60000);

        // Floating action buttons
        const sharePrayerBtn = document.getElementById('share-prayer-btn');
        const shareTestimonialBtn = document.getElementById('share-testimonial-btn');
        const prayerModal = document.getElementById('prayer-modal');
        const testimonialModal = document.getElementById('testimonial-modal');

        // Open prayer modal
        if (sharePrayerBtn) {
            sharePrayerBtn.addEventListener('click', function() {
                prayerModal.style.display = 'flex';
            });
        }

        // Open testimonial modal
        if (shareTestimonialBtn) {
            shareTestimonialBtn.addEventListener('click', function() {
                testimonialModal.style.display = 'flex';
            });
        }

        // Task click handlers
        const todoItems = document.querySelectorAll('.todo-item');
        todoItems.forEach(item => {
            item.addEventListener('click', function() {
                const taskType = this.getAttribute('data-task');
                
                switch(taskType) {
                    case 'devotion':
                        // Scroll to devotion section
                        document.querySelector('.devotion-card').scrollIntoView({ 
                            behavior: 'smooth' 
                        });
                        break;
                    case 'weight':
                        document.getElementById('weight-modal').style.display = 'flex';
                        break;
                    case 'steps':
                        document.getElementById('steps-modal').style.display = 'flex';
                        break;
                }
            });
        });

        // Complete devotion button
        const completeDevotionBtn = document.getElementById('complete-devotion');
        if (completeDevotionBtn) {
            completeDevotionBtn.addEventListener('click', function() {
                if (!this.disabled) {
                    // Submit form to mark devotion complete
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'api.php';
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'complete_devotion';
                    
                    form.appendChild(actionInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Modal functionality
        const modals = document.querySelectorAll('.modal');
        const closeButtons = document.querySelectorAll('.close');

        // Close modals
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Form submission handling
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const action = this.getAttribute('action');
                
                fetch(action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(data => {
                    // Handle success - you might want to show a success message
                    // and refresh the content instead of redirecting
                    window.location.reload();
                })
                .catch(error => console.error('Error:', error));
            });
        });
    });
    </script>
</body>
</html>