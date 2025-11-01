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
        }
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
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            text-align: center;
        }

        .day-checkbox:checked + .day-label {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .day-label:hover {
            border-color: var(--primary-color);
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
        }

        .current-schedule {
            background: linear-gradient(135deg, var(--primary-color), #E5A020);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .current-schedule h3 {
            margin: 0 0 10px 0;
            font-size: 1.3rem;
        }

        .schedule-days {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .scheduled-day {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
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
        }
    </style>
</head>
<body class="<?php echo $theme_class; ?>">
    <!-- Header (same as index.php) -->
    <header class="main-header">
        <div class="header-content">
            <div class="logo">
                <img src="imgs/dark-logo.png" width="40" height="40" alt="FaithFit Logo"/>
                <span>Fit for the King</span>
            </div>
            <div class="header-actions">
                <!-- Theme toggle and user dropdown -->
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <h1 class="page-title">Set Workout Schedule</h1>
            <p class="page-subtitle">Choose your workout days for the week</p>
        </div>

        <?php if ($current_schedule): ?>
        <div class="current-schedule">
            <h3>Current Schedule</h3>
            <p>You're following <strong><?php echo htmlspecialchars($current_schedule['plan_name']); ?></strong></p>
            <div class="schedule-days">
                <?php
                $day_names = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
                $scheduled_days = explode(',', $current_schedule['scheduled_days']);
                foreach ($scheduled_days as $day): 
                    if (isset($day_names[$day])):
                ?>
                    <span class="scheduled-day"><?php echo $day_names[$day]; ?></span>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="schedule-form">
            <div class="form-group">
                <label for="workout_plan">Select Workout Plan</label>
                <select class="form-control" id="workout_plan" name="workout_plan_id" required>
                    <option value="">Choose a plan...</option>
                    <?php foreach ($workout_plans as $plan): ?>
                        <option value="<?php echo $plan['id']; ?>" 
                            <?php echo ($current_schedule && $current_schedule['workout_plan_id'] == $plan['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['name']); ?> (<?php echo ucfirst($plan['difficulty']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Workout Days</label>
                <div class="days-selection">
                    <?php
                    $days = [
                        '1' => 'Mon', '2' => 'Tue', '3' => 'Wed', '4' => 'Thu',
                        '5' => 'Fri', '6' => 'Sat', '7' => 'Sun'
                    ];
                    $current_days = $current_schedule ? explode(',', $current_schedule['scheduled_days']) : [];
                    foreach ($days as $day_num => $day_name): 
                    ?>
                        <input type="checkbox" name="workout_days[]" value="<?php echo $day_num; ?>" 
                               id="day_<?php echo $day_num; ?>" class="day-checkbox"
                               <?php echo in_array($day_num, $current_days) ? 'checked' : ''; ?>>
                        <label for="day_<?php echo $day_num; ?>" class="day-label">
                            <?php echo $day_name; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="set_schedule" class="btn btn-primary">
                <i class="fas fa-calendar-check"></i>
                Set Schedule
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
</body>
</html>