<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get user data
$user = $faithFit->getUserProfile($user_id);
$dashboard_data = $faithFit->getDashboardStats($user_id);

// Get historical data for analytics
$weight_history = $faithFit->getWeightHistory($user_id, 365);
$steps_history = $faithFit->getStepsHistory($user_id, 365);
$devotion_streaks = $faithFit->getUserStreaks($user_id);

// Determine theme
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/analytics.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                                <a href="index.php">
                                    <i class="fas fa-home"></i>
                                    Dashboard
                                </a>
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
            <div class="page active" id="analytics-page">
                <div class="page-header">
                    <h1 class="page-title">Progress Analytics</h1>
                    <p class="page-subtitle">Track your fitness and spiritual journey</p>
                </div>

                <div class="analytics-content">
                    <!-- Time Range Selector -->
                    <div class="time-range-selector">
                        <button class="time-range-btn active" data-range="7">7 Days</button>
                        <button class="time-range-btn" data-range="30">30 Days</button>
                        <button class="time-range-btn" data-range="90">90 Days</button>
                        <button class="time-range-btn" data-range="365">Yearly</button>
                    </div>

                    <!-- Analytics Overview Cards -->
                    <div class="overview-cards">
                        <div class="overview-card">
                            <div class="overview-icon weight">
                                <i class="fas fa-weight-scale"></i>
                            </div>
                            <div class="overview-info">
                                <h3>Weight Progress</h3>
                                <div class="overview-value" id="overview-weight">--</div>
                                <div class="overview-change" id="overview-weight-change">--</div>
                            </div>
                        </div>

                        <div class="overview-card">
                            <div class="overview-icon steps">
                                <i class="fas fa-shoe-prints"></i>
                            </div>
                            <div class="overview-info">
                                <h3>Steps</h3>
                                <div class="overview-value" id="overview-steps">--</div>
                                <div class="overview-change" id="overview-steps-change">--</div>
                            </div>
                        </div>

                        <div class="overview-card">
                            <div class="overview-icon devotion">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="overview-info">
                                <h3>Devotion Streak</h3>
                                <div class="overview-value" id="overview-streak"><?php echo $dashboard_data['devotion_streak'] ?? 0; ?> days</div>
                                <div class="overview-change" id="overview-streak-change">Current streak</div>
                            </div>
                        </div>

                        <div class="overview-card">
                            <div class="overview-icon completion">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="overview-info">
                                <h3>Completion Rate</h3>
                                <div class="overview-value" id="overview-completion">--%</div>
                                <div class="overview-change" id="overview-completion-change">Daily goals</div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Charts Grid -->
                    <div class="analytics-grid">
                        <!-- Weight Progress Card -->
                        <div class="analytics-card large">
                            <div class="card-header">
                                <h3>Weight Progress</h3>
                                <div class="card-actions">
                                    <span class="unit-toggle" id="weight-unit-toggle">kg</span>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="weightChart" width="400" height="200"></canvas>
                            </div>
                            <div class="card-stats">
                                <div class="stat">
                                    <span class="stat-label">Starting</span>
                                    <span class="stat-value" id="start-weight">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Current</span>
                                    <span class="stat-value" id="current-weight">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Change</span>
                                    <span class="stat-value" id="total-weight-change">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Goal</span>
                                    <span class="stat-value" id="goal-weight">--</span>
                                </div>
                            </div>
                        </div>

                        <!-- Steps Progress Card -->
                        <div class="analytics-card large">
                            <div class="card-header">
                                <h3>Steps Progress</h3>
                                <div class="card-actions">
                                    <span class="goal-display">Goal: 8,000</span>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="stepsChart" width="400" height="200"></canvas>
                            </div>
                            <div class="card-stats">
                                <div class="stat">
                                    <span class="stat-label">Today</span>
                                    <span class="stat-value" id="today-steps">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Average</span>
                                    <span class="stat-value" id="avg-steps">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Goal Days</span>
                                    <span class="stat-value" id="goal-days">--</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Total</span>
                                    <span class="stat-value" id="total-steps">--</span>
                                </div>
                            </div>
                        </div>

                        <!-- Devotion Analytics Card -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <h3>Devotion Analytics</h3>
                                <i class="fas fa-bible"></i>
                            </div>
                            <div class="streak-display">
                                <div class="streak-number"><?php echo $dashboard_data['devotion_streak'] ?? 0; ?></div>
                                <div class="streak-label">Day Streak</div>
                            </div>
                            <div class="devotion-calendar" id="devotion-calendar">
                                <!-- Calendar will be populated by JavaScript -->
                            </div>
                            <div class="devotion-stats">
                                <div class="stat">
                                    <span class="stat-value" id="devotion-completion">--%</span>
                                    <span class="stat-label">Completion</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value" id="devotion-total">--</span>
                                    <span class="stat-label">This Period</span>
                                </div>
                            </div>
                        </div>

                        <!-- Completion Analytics Card -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <h3>Daily Completion</h3>
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="completion-chart">
                                <canvas id="completionChart" width="200" height="200"></canvas>
                            </div>
                            <div class="completion-stats">
                                <div class="stat">
                                    <span class="stat-value" id="perfect-days">--</span>
                                    <span class="stat-label">Perfect Days</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value" id="completion-rate">--%</span>
                                    <span class="stat-label">Avg. Rate</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Summary Card -->
                        <div class="analytics-card full-width">
                            <div class="card-header">
                                <h3>Progress Summary</h3>
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="progress-summary">
                                <div class="summary-item">
                                    <div class="summary-label">Fitness Progress</div>
                                    <div class="summary-bar">
                                        <div class="summary-fill" id="fitness-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="summary-value" id="fitness-value">0%</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Spiritual Growth</div>
                                    <div class="summary-bar">
                                        <div class="summary-fill" id="spiritual-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="summary-value" id="spiritual-value">0%</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-label">Consistency</div>
                                    <div class="summary-bar">
                                        <div class="summary-fill" id="consistency-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="summary-value" id="consistency-value">0%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php" class="nav-item active">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="community.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Community</span>
            </a>
        </nav>
    </div>

    <script src="js/analytics.js"></script>
</body>
</html>