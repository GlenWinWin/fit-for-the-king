<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get workout history
$workout_history = $faithFit->getWorkoutHistory($user_id, 30);

$user = $faithFit->getUserProfile($user_id);
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout History - FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .main-content {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .workout-session {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .workout-session:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .session-title h3 {
            margin: 0 0 5px 0;
            color: var(--text-color);
            font-size: 1.3rem;
        }

        .session-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .session-stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .session-stat {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .session-stat i {
            color: var(--primary-color);
        }

        .no-history {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .no-history i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-history h3 {
            margin: 0 0 15px 0;
            font-size: 1.5rem;
        }

        .progress-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .progress-up {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .progress-down {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .session-header {
                flex-direction: column;
            }

            .session-stats {
                justify-content: space-between;
                width: 100%;
            }
        }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
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
                            <a href="index.php">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>
                            <a href="workout-tracker.php">
                                <i class="fas fa-dumbbell"></i>
                                Today's Workout
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

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Workout Progress</h1>
            <p class="page-subtitle">Track your strength gains and consistency</p>
        </div>

        <?php if (empty($workout_history)): ?>
            <div class="no-history">
                <i class="fas fa-chart-line"></i>
                <h3>No Workout History Yet</h3>
                <p>Complete your first workout to start tracking your progress!</p>
                <a href="workout-tracker.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-dumbbell"></i>
                    Start Working Out
                </a>
            </div>
        <?php else: ?>
            <!-- Stats Summary -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($workout_history); ?></div>
                    <div class="stat-label">Workouts Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_reps = array_sum(array_column($workout_history, 'total_reps'));
                        echo number_format($total_reps);
                        ?>
                    </div>
                    <div class="stat-label">Total Reps</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $unique_days = array_unique(array_column($workout_history, 'day_name'));
                        echo count($unique_days);
                        ?>
                    </div>
                    <div class="stat-label">Different Workouts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $current_week = date('W');
                        $week_workouts = array_filter($workout_history, function($workout) use ($current_week) {
                            return date('W', strtotime($workout['completed_at'])) == $current_week;
                        });
                        echo count($week_workouts);
                        ?>
                    </div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>

            <!-- Workout History -->
            <div class="history-list">
                <?php foreach ($workout_history as $session): ?>
                    <div class="workout-session">
                        <div class="session-header">
                            <div class="session-title">
                                <h3><?php echo htmlspecialchars($session['day_name']); ?></h3>
                                <div class="session-date">
                                    <?php echo date('F j, Y g:i A', strtotime($session['completed_at'])); ?>
                                </div>
                            </div>
                            <div class="session-plan">
                                <span style="color: var(--text-muted);"><?php echo htmlspecialchars($session['plan_name']); ?></span>
                            </div>
                        </div>
                        <div class="session-stats">
                            <div class="session-stat">
                                <i class="fas fa-dumbbell"></i>
                                <span><?php echo $session['exercises_completed']; ?> exercises</span>
                            </div>
                            <div class="session-stat">
                                <i class="fas fa-repeat"></i>
                                <span><?php echo $session['total_reps']; ?> total reps</span>
                            </div>
                            <div class="session-stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo time_elapsed_string($session['completed_at']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="workout-tracker.php" class="nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workout</span>
        </a>
        <a href="workout-history.php" class="nav-item active">
            <i class="fas fa-chart-line"></i>
            <span>Progress</span>
        </a>
        <a href="community.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Community</span>
        </a>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.body.classList.contains('light-mode') ? 'light' : 'dark';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                const formData = new FormData();
                formData.append('action', 'update_theme');
                formData.append('theme', newTheme);
                
                fetch('api.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    window.location.reload();
                });
            });
        }
    });
    </script>
</body>
</html>