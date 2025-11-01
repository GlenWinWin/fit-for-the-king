<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get today's workout
$todays_workout = $faithFit->getTodaysWorkout($user_id);

if (!$todays_workout) {
    header('Location: index.php');
    exit;
}

// Handle workout logging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['log_set'])) {
        $workout_day_exercise_id = intval($_POST['workout_day_exercise_id']);
        $set_number = intval($_POST['set_number']);
        $weight = floatval($_POST['weight']);
        $reps = intval($_POST['reps']);
        $unit = $_POST['unit'] ?? 'kg';
        
        if ($workout_day_exercise_id > 0 && $set_number > 0 && $reps > 0) {
            $faithFit->logWorkoutSet($user_id, $workout_day_exercise_id, $set_number, $weight, $reps, $unit);
        }
    } elseif (isset($_POST['complete_workout'])) {
        $faithFit->completeWorkoutDay($user_id, $todays_workout['id']);
        $_SESSION['message'] = 'Workout completed! Great job!';
        $_SESSION['message_type'] = 'success';
        header('Location: index.php');
        exit;
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
    <title>Today's Workout - FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .main-content {
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .workout-header {
            background: linear-gradient(135deg, var(--primary-color), #E5A020);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(253, 176, 34, 0.3);
        }

        .workout-header h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }

        .workout-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0.9;
        }

        .exercise-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .exercise-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .exercise-title {
            flex: 1;
        }

        .exercise-title h3 {
            margin: 0 0 8px 0;
            color: var(--text-color);
            font-size: 1.4rem;
        }

        .exercise-details {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .exercise-tag {
            background: var(--hover-color);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .video-section {
            margin-top: 20px;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            margin-top: 15px;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        .sets-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
        }

        .sets-table th,
        .sets-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .sets-table th {
            background: var(--hover-color);
            font-weight: 600;
            color: var(--text-color);
        }

        .set-input {
            width: 80px;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--input-bg);
            color: var(--text-color);
        }

        .unit-select {
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--input-bg);
            color: var(--text-color);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
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
        }

        .btn-success {
            background: #4CAF50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .previous-logs {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .previous-logs h4 {
            margin: 0 0 15px 0;
            color: var(--text-color);
        }

        .log-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color-light);
        }

        .log-date {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .log-weights {
            display: flex;
            gap: 15px;
        }

        .weight-set {
            background: var(--hover-color);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .workout-actions {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .complete-btn {
            padding: 15px 30px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .exercise-header {
                flex-direction: column;
            }

            .sets-table {
                font-size: 0.9rem;
            }

            .set-input {
                width: 60px;
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
                            <a href="workout-history.php">
                                <i class="fas fa-chart-line"></i>
                                Progress
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
        <div class="workout-header">
            <h1><?php echo htmlspecialchars($todays_workout['day_name']); ?></h1>
            <p><?php echo htmlspecialchars($todays_workout['plan_name']); ?></p>
            <div class="workout-meta">
                <div class="meta-item">
                    <i class="fas fa-dumbbell"></i>
                    <span><?php echo count($todays_workout['exercises']); ?> Exercises</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <?php foreach ($todays_workout['exercises'] as $exercise): ?>
        <div class="exercise-card" id="exercise-<?php echo $exercise['id']; ?>">
            <div class="exercise-header">
                <div class="exercise-title">
                    <h3><?php echo htmlspecialchars($exercise['name']); ?></h3>
                    <div class="exercise-details">
                        <span class="exercise-tag"><?php echo $exercise['sets']; ?> sets × <?php echo $exercise['reps']; ?> reps</span>
                        <span class="exercise-tag"><?php echo ucfirst($exercise['muscle_group']); ?></span>
                        <span class="exercise-tag"><?php echo ucfirst($exercise['equipment']); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($exercise['demonstration_video_url'])): ?>
            <div class="video-section">
                <h4>Exercise Demonstration</h4>
                <div class="video-container">
                    <?php
                    // Convert YouTube URL to embed URL
                    $video_url = $exercise['demonstration_video_url'];
                    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                        // Extract video ID
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches);
                        $video_id = $matches[1] ?? '';
                        if ($video_id) {
                            $embed_url = "https://www.youtube.com/embed/{$video_id}";
                            echo '<iframe src="' . $embed_url . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="sets-form">
                <input type="hidden" name="workout_day_exercise_id" value="<?php echo $exercise['id']; ?>">
                <table class="sets-table">
                    <thead>
                        <tr>
                            <th>Set</th>
                            <th>Weight</th>
                            <th>Unit</th>
                            <th>Reps</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($set = 1; $set <= $exercise['sets']; $set++): ?>
                        <tr>
                            <td><?php echo $set; ?></td>
                            <td>
                                <input type="number" name="weight" class="set-input" step="0.5" min="0" placeholder="0.0" required>
                            </td>
                            <td>
                                <select name="unit" class="unit-select">
                                    <option value="kg">kg</option>
                                    <option value="lbs">lbs</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="reps" class="set-input" min="1" max="50" placeholder="0" required>
                            </td>
                            <td>
                                <input type="hidden" name="set_number" value="<?php echo $set; ?>">
                                <button type="submit" name="log_set" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save
                                </button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </form>

            <?php if (!empty($exercise['previous_logs'])): ?>
            <div class="previous-logs">
                <h4>Previous Workouts</h4>
                <?php 
                $grouped_logs = [];
                foreach ($exercise['previous_logs'] as $log) {
                    $date = $log['log_date'];
                    if (!isset($grouped_logs[$date])) {
                        $grouped_logs[$date] = [];
                    }
                    $grouped_logs[$date][] = $log;
                }
                
                foreach ($grouped_logs as $date => $logs):
                ?>
                    <div class="log-item">
                        <span class="log-date"><?php echo date('M j', strtotime($date)); ?></span>
                        <div class="log-weights">
                            <?php foreach ($logs as $log): ?>
                                <span class="weight-set">
                                    <?php echo $log['weight']; ?><?php echo $log['weight_unit']; ?> × <?php echo $log['reps_completed']; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="workout-actions">
            <form method="POST">
                <input type="hidden" name="workout_day_id" value="<?php echo $todays_workout['id']; ?>">
                <button type="submit" name="complete_workout" class="btn btn-success complete-btn">
                    <i class="fas fa-check-circle"></i>
                    Complete Workout
                </button>
            </form>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-save sets when inputs change
        const setForms = document.querySelectorAll('.sets-form');
        setForms.forEach(form => {
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Add a small delay to avoid too many requests
                    clearTimeout(window.saveTimeout);
                    window.saveTimeout = setTimeout(() => {
                        form.requestSubmit();
                    }, 500);
                });
            });
        });

        // Handle set form submission
        setForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('log_set', '1');
                
                fetch('workout-tracker.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    // Show success feedback
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    submitBtn.style.background = '#4CAF50';
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalHtml;
                        submitBtn.style.background = '';
                    }, 1500);
                })
                .catch(error => console.error('Error:', error));
            });
        });

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