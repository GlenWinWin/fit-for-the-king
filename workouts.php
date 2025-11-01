<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get all workout plans
$workout_plans = $faithFit->getWorkoutPlans();

// Get user's active workout
$active_workout = $faithFit->getUserActiveWorkout($user_id);

// Handle workout assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_workout'])) {
    $workout_plan_id = intval($_POST['workout_plan_id']);
    if ($workout_plan_id > 0) {
        $result = $faithFit->assignWorkoutPlan($user_id, $workout_plan_id);
        if ($result) {
            $_SESSION['message'] = 'Workout plan assigned successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['message'] = 'Error assigning workout plan';
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Get current theme
$user = $faithFit->getUserProfile($user_id);
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
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
            margin-bottom: 30px;
        }

        /* Active Workout Banner */
        .active-workout-banner {
            background: linear-gradient(135deg, var(--primary-color), #E5A020);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(253, 176, 34, 0.3);
        }

        .active-workout-banner h3 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }

        .active-workout-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .workout-details {
            flex: 1;
        }

        .workout-details p {
            margin: 5px 0;
            opacity: 0.9;
        }

        .workout-actions {
            display: flex;
            gap: 10px;
        }

        /* Workout Plans Grid */
        .workout-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .workout-plan-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .workout-plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .plan-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--text-color);
            flex: 1;
        }

        .difficulty-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .difficulty-badge.beginner {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .difficulty-badge.intermediate {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .difficulty-badge.advanced {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .plan-details {
            flex: 1;
            margin-bottom: 25px;
        }

        .plan-details p {
            margin: 8px 0;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .plan-details i {
            color: var(--primary-color);
            width: 16px;
        }

        .plan-description {
            margin-top: 15px !important;
            line-height: 1.6;
            font-size: 0.95rem;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .assign-form {
            margin-top: auto;
        }

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
            width: 100%;
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

        /* No Workouts Message */
        .no-workouts {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .no-workouts i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-workouts h3 {
            margin: 0 0 15px 0;
            font-size: 1.5rem;
        }

        .no-workouts p {
            margin: 0 0 25px 0;
            font-size: 1.1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .page-title {
                font-size: 2rem;
            }

            .workout-plans-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .workout-plan-card {
                padding: 25px 20px;
            }

            .active-workout-info {
                flex-direction: column;
                align-items: flex-start;
            }

            .workout-actions {
                width: 100%;
                justify-content: stretch;
            }

            .workout-actions .btn {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.8rem;
            }

            .workout-plan-card {
                padding: 20px 15px;
            }

            .plan-header {
                flex-direction: column;
                gap: 10px;
            }

            .plan-header h3 {
                font-size: 1.3rem;
            }
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
                                <a href="index.php">
                                    <i class="fas fa-home"></i>
                                    Dashboard
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
            <div class="page-header">
                <h1 class="page-title">Workout Plans</h1>
                <p class="page-subtitle">Choose a plan that fits your fitness goals and faith journey</p>
            </div>

            <!-- Active Workout Banner -->
            <?php if ($active_workout): ?>
            <div class="active-workout-banner">
                <h3>🎯 Active Workout Plan</h3>
                <div class="active-workout-info">
                    <div class="workout-details">
                        <p><strong><?php echo htmlspecialchars($active_workout['plan_name']); ?></strong></p>
                        <p>Day <?php echo $active_workout['assigned_day']; ?>: <?php echo htmlspecialchars($active_workout['day_name']); ?></p>
                        <p>Difficulty: <?php echo ucfirst($active_workout['difficulty']); ?> • <?php echo $active_workout['duration_weeks']; ?> weeks</p>
                    </div>
                    <div class="workout-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-dumbbell"></i>
                            View Today's Workout
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Workout Plans Grid -->
            <div class="workout-plans-grid">
                <?php if (empty($workout_plans)): ?>
                    <div class="no-workouts">
                        <i class="fas fa-dumbbell"></i>
                        <h3>No Workout Plans Available</h3>
                        <p>Check back later for new workout plans!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($workout_plans as $plan): ?>
                        <div class="workout-plan-card">
                            <div class="plan-header">
                                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <span class="difficulty-badge <?php echo $plan['difficulty']; ?>">
                                    <?php echo ucfirst($plan['difficulty']); ?>
                                </span>
                            </div>
                            
                            <div class="plan-details">
                                <p><i class="fas fa-calendar"></i> <?php echo $plan['duration_weeks']; ?> week program</p>
                                <p><i class="fas fa-list"></i> 
                                    <?php 
                                    $days = $faithFit->getWorkoutDays($plan['id']);
                                    echo count($days) . ' days per week';
                                    ?>
                                </p>
                                <?php if (!empty($plan['description'])): ?>
                                    <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($plan['created_by_name'])): ?>
                                    <p><i class="fas fa-user-tie"></i> Created by <?php echo htmlspecialchars($plan['created_by_name']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" class="assign-form">
                                <input type="hidden" name="workout_plan_id" value="<?php echo $plan['id']; ?>">
                                <button type="submit" name="assign_workout" class="btn btn-primary" 
                                    <?php echo ($active_workout && $active_workout['workout_plan_id'] == $plan['id']) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-dumbbell"></i>
                                    <?php echo ($active_workout && $active_workout['workout_plan_id'] == $plan['id']) ? 'Current Plan' : 'Start This Plan'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            <a href="workouts.php" class="nav-item active">
                <i class="fas fa-dumbbell"></i>
                <span>Workouts</span>
            </a>
            <a href="community.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Community</span>
            </a>
        </nav>
    </div>

    <script src="js/script.js"></script>
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

        // Form submission handling
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Don't prevent default for form submission
                // Let it submit normally since we're not using AJAX for workout assignment
            });
        });
    });
    </script>
</body>
</html>