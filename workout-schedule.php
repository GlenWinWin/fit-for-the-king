<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get workout plans
$workout_plans = $faithFit->getWorkoutPlans();
$current_schedule = $faithFit->getUserWorkoutSchedule($user_id);

// Handle schedule submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_schedule'])) {
    $workout_plan_id = intval($_POST['workout_plan_id']);
    $scheduled_days = $_POST['workout_days'] ?? [];
    
    if ($workout_plan_id > 0 && !empty($scheduled_days)) {
        $days_string = implode(',', $scheduled_days);
        $result = $faithFit->setWorkoutSchedule($user_id, $workout_plan_id, $days_string);
        if ($result) {
            $_SESSION['message'] = 'Workout schedule set successfully!';
            $_SESSION['message_type'] = 'success';
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['message'] = 'Error setting workout schedule';
            $_SESSION['message_type'] = 'error';
        }
    } else {
        $_SESSION['message'] = 'Please select a workout plan and at least one day';
        $_SESSION['message_type'] = 'error';
    }
}

$user = $faithFit->getUserProfile($user_id);
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Workout Schedule - FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .main-content {
            padding: 20px;
            max-width: 600px;
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

        .schedule-form {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(253, 176, 34, 0.1);
        }

        .days-selection {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .day-checkbox {
            display: none;
        }

        .day-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 10px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
            background: var(--card-bg);
            color: var(--text-color);
        }

        .day-checkbox:checked + .day-label {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(253, 176, 34, 0.3);
        }

        .day-label:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .day-label i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            width: 100%;
            margin-top: 10px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(253, 176, 34, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .current-schedule {
            background: linear-gradient(135deg, var(--primary-color), #E5A020);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(253, 176, 34, 0.3);
        }

        .current-schedule h3 {
            margin: 0 0 15px 0;
            font-size: 1.4rem;
        }

        .schedule-days {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .scheduled-day {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .plan-info {
            background: var(--hover-color);
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .plan-info p {
            margin: 5px 0;
            color: var(--text-muted);
        }

        .required {
            color: #ff4444;
        }

        .form-hint {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .days-selection {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .main-content {
                padding: 15px;
            }
            
            .page-title {
                font-size: 2rem;
            }

            .day-label {
                padding: 12px 8px;
                font-size: 0.9rem;
            }

            .day-label i {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .days-selection {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .schedule-form {
                padding: 20px 15px;
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
            <h1 class="page-title">Set Workout Schedule</h1>
            <p class="page-subtitle">Choose your workout days for the week</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px; background: <?php echo $_SESSION['message_type'] === 'success' ? 'var(--success-color)' : 'var(--error-color)'; ?>; color: white;">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <?php if ($current_schedule): ?>
        <div class="current-schedule">
            <h3><i class="fas fa-calendar-check"></i> Current Schedule</h3>
            <p>You're following <strong><?php echo htmlspecialchars($current_schedule['plan_name']); ?></strong></p>
            <div class="schedule-days">
                <?php
                $day_names = [
                    '1' => ['name' => 'Monday', 'icon' => 'fa-calendar-day'],
                    '2' => ['name' => 'Tuesday', 'icon' => 'fa-calendar-day'],
                    '3' => ['name' => 'Wednesday', 'icon' => 'fa-calendar-day'],
                    '4' => ['name' => 'Thursday', 'icon' => 'fa-calendar-day'],
                    '5' => ['name' => 'Friday', 'icon' => 'fa-calendar-day'],
                    '6' => ['name' => 'Saturday', 'icon' => 'fa-calendar-week'],
                    '7' => ['name' => 'Sunday', 'icon' => 'fa-calendar-week']
                ];
                $scheduled_days = explode(',', $current_schedule['scheduled_days']);
                foreach ($scheduled_days as $day): 
                    if (isset($day_names[$day])):
                ?>
                    <span class="scheduled-day">
                        <i class="fas <?php echo $day_names[$day]['icon']; ?>"></i>
                        <?php echo $day_names[$day]['name']; ?>
                    </span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="schedule-form">
            <div class="form-group">
                <label for="workout_plan">
                    Select Workout Plan <span class="required">*</span>
                </label>
                <select class="form-control" id="workout_plan" name="workout_plan_id" required>
                    <option value="">Choose a plan...</option>
                    <?php foreach ($workout_plans as $plan): ?>
                        <option value="<?php echo $plan['id']; ?>" 
                            <?php echo ($current_schedule && $current_schedule['workout_plan_id'] == $plan['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['name']); ?> 
                            (<?php echo ucfirst($plan['difficulty']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Choose the workout plan that matches your fitness level</div>
            </div>

            <div class="form-group">
                <label>
                    Select Workout Days <span class="required">*</span>
                </label>
                <div class="days-selection">
                    <?php
                    $days = [
                        '1' => ['name' => 'Monday', 'icon' => 'fa-sun'],
                        '2' => ['name' => 'Tuesday', 'icon' => 'fa-cloud-sun'],
                        '3' => ['name' => 'Wednesday', 'icon' => 'fa-cloud'],
                        '4' => ['name' => 'Thursday', 'icon' => 'fa-cloud-rain'],
                        '5' => ['name' => 'Friday', 'icon' => 'fa-umbrella'],
                        '6' => ['name' => 'Saturday', 'icon' => 'fa-star'],
                        '7' => ['name' => 'Sunday', 'icon' => 'fa-moon']
                    ];
                    $current_days = $current_schedule ? explode(',', $current_schedule['scheduled_days']) : [];
                    foreach ($days as $day_num => $day_info): 
                    ?>
                        <input type="checkbox" name="workout_days[]" value="<?php echo $day_num; ?>" 
                               id="day_<?php echo $day_num; ?>" class="day-checkbox"
                               <?php echo in_array($day_num, $current_days) ? 'checked' : ''; ?>>
                        <label for="day_<?php echo $day_num; ?>" class="day-label">
                            <i class="fas <?php echo $day_info['icon']; ?>"></i>
                            <?php echo $day_info['name']; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-hint">Select the days you want to workout each week</div>
            </div>

            <?php if ($current_schedule): ?>
            <div class="plan-info">
                <p><strong>Current Plan:</strong> <?php echo htmlspecialchars($current_schedule['plan_name']); ?></p>
                <p><strong>Difficulty:</strong> <?php echo ucfirst($current_schedule['difficulty']); ?></p>
                <p><strong>Duration:</strong> <?php echo $current_schedule['duration_weeks']; ?> weeks</p>
                <p><strong>Started:</strong> <?php echo date('F j, Y', strtotime($current_schedule['start_date'])); ?></p>
            </div>
            <?php endif; ?>

            <button type="submit" name="set_schedule" class="btn btn-primary">
                <i class="fas fa-calendar-check"></i>
                <?php echo $current_schedule ? 'Update Schedule' : 'Set Schedule'; ?>
            </button>
        </form>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="workouts.php" class="nav-item">
            <i class="fas fa-dumbbell"></i>
            <span>Workouts</span>
        </a>
        <a href="workout-schedule.php" class="nav-item active">
            <i class="fas fa-calendar"></i>
            <span>Schedule</span>
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

        // Form validation
        const scheduleForm = document.querySelector('.schedule-form');
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function(e) {
                const workoutPlan = document.getElementById('workout_plan');
                const checkedDays = document.querySelectorAll('.day-checkbox:checked');
                
                if (!workoutPlan.value) {
                    e.preventDefault();
                    alert('Please select a workout plan');
                    workoutPlan.focus();
                    return;
                }
                
                if (checkedDays.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one workout day');
                    return;
                }
            });
        }

        // Add visual feedback for day selection
        const dayCheckboxes = document.querySelectorAll('.day-checkbox');
        dayCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.day-checkbox:checked').length;
                // You can add additional logic here if needed
            });
        });
    });
    </script>
</body>
</html>