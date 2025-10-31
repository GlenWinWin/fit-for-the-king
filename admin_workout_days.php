<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

if (!isset($_GET['plan_id'])) {
    header('Location: admin_workouts.php');
    exit;
}

$plan_id = $_GET['plan_id'];
$faithFit = new FaithFitFunctions();

// Get plan details
$plan = $faithFit->getWorkoutPlanDetails($plan_id);
if (!$plan) {
    header('Location: admin_workouts.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_workout_day'])) {
        $result = $faithFit->addWorkoutDay(
            $plan_id,
            $_POST['day_number'],
            $_POST['day_name'],
            $_POST['day_description']
        );
        
        if ($result['success']) {
            $success = "Workout day added successfully!";
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['add_exercise_to_day'])) {
        $result = $faithFit->addExerciseToDay(
            $_POST['workout_day_id'],
            $_POST['exercise_id'],
            $_POST['exercise_order'],
            $_POST['sets'],
            $_POST['reps'],
            $_POST['rest_seconds'],
            $_POST['notes']
        );
        
        if ($result) {
            $success = "Exercise added to workout day!";
        } else {
            $error = "Error adding exercise to workout day";
        }
    }
}

// Get existing data
$workoutDays = $faithFit->getWorkoutDays($plan_id);
$exercises = $faithFit->getExercises();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Manage Workout Days</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Include all the same CSS styles from admin_workouts.php */
        :root {
            --gradient-admin: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            --gradient-info: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
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
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
        }

        .admin-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-brand i {
            font-size: 2rem;
            background: var(--gradient-admin);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-brand h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-admin {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--gradient-admin);
            color: white;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .workout-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .welcome-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-admin);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .plan-title h2 {
            color: #fff;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .plan-meta {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .plan-difficulty {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .beginner { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .intermediate { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .advanced { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        .workout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 1024px) {
            .workout-grid {
                grid-template-columns: 1fr;
            }
        }

        .workout-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .workout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-admin);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .workout-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .workout-card:hover::before {
            opacity: 1;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: #8B5CF6;
            font-size: 1.1rem;
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
            padding: 14px 16px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Updated Select Styles */
        .select-wrapper {
            position: relative;
        }

        .select-wrapper::after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%238B5CF6"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat center;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        .select-wrapper:focus-within::after {
            transform: translateY(-50%) rotate(180deg);
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            padding-right: 45px;
            background: rgba(255,255,255,0.05);
            background-image: none;
            position: relative;
            z-index: 1;
        }

        select.form-control option {
            background: #1a1a1a;
            color: #fff;
            padding: 12px;
            border: none;
        }

        select.form-control:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
            background: rgba(255,255,255,0.08);
        }

        select.form-control:hover {
            border-color: rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.08);
        }

        /* Custom scrollbar for select dropdowns */
        select.form-control::-webkit-scrollbar {
            width: 8px;
        }

        select.form-control::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
        }

        select.form-control::-webkit-scrollbar-thumb {
            background: #8B5CF6;
            border-radius: 4px;
        }

        select.form-control::-webkit-scrollbar-thumb:hover {
            background: #7C3AED;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--gradient-admin);
            color: white;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }

        /* Workout Days Display */
        .days-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .day-item {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
        }

        .day-header {
            background: rgba(139, 92, 246, 0.1);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .day-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }

        .day-meta {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .exercises-list {
            padding: 1.5rem;
        }

        .exercise-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255,255,255,0.02);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .exercise-info h4 {
            color: #fff;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .exercise-meta {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .exercise-sets {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="dark-mode">
    <!-- Admin Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-brand">
                <i class="fas fa-dumbbell"></i>
                <h1>Manage Workout Plan</h1>
            </div>
            <div class="admin-actions">
                <a href="admin_workouts.php" class="btn-admin btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Workouts
                </a>
                <a href="admin_dashboard.php" class="btn-admin btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="workout-container">
        <!-- Plan Header -->
        <section class="welcome-section">
            <div class="plan-header">
                <div class="plan-title">
                    <h2><?php echo htmlspecialchars($plan['name']); ?></h2>
                    <div class="plan-meta">
                        <?php echo $plan['duration_weeks']; ?> Week Plan • 
                        <?php echo count($workoutDays); ?> Days Created
                    </div>
                    <?php if ($plan['description']): ?>
                        <p style="color: #94a3b8; margin-top: 0.5rem;"><?php echo htmlspecialchars($plan['description']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="plan-difficulty <?php echo $plan['difficulty']; ?>">
                    <?php echo ucfirst($plan['difficulty']); ?>
                </div>
            </div>
        </section>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Forms Grid -->
        <div class="workout-grid">
            <!-- Add Workout Day -->
            <div class="workout-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Add Workout Day
                    </h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_workout_day" value="1">
                    
                    <div class="form-group">
                        <label for="day_number">Day Number</label>
                        <input type="number" class="form-control" id="day_number" name="day_number" required 
                               min="1" max="7" placeholder="e.g., 1">
                    </div>
                    
                    <div class="form-group">
                        <label for="day_name">Day Name</label>
                        <input type="text" class="form-control" id="day_name" name="day_name" required 
                               placeholder="e.g., Push Strength, Pull Hypertrophy">
                    </div>
                    
                    <div class="form-group">
                        <label for="day_description">Description (Optional)</label>
                        <textarea class="form-control" id="day_description" name="day_description" 
                                  placeholder="Describe this workout day..." rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i>
                        Add Workout Day
                    </button>
                </form>
            </div>

            <!-- Add Exercise to Day -->
            <div class="workout-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-dumbbell"></i>
                        Add Exercise to Day
                    </h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="add_exercise_to_day" value="1">
                    
                    <div class="form-group">
                        <label for="workout_day_id">Workout Day</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="workout_day_id" name="workout_day_id" required>
                                <option value="">Select a workout day</option>
                                <?php foreach ($workoutDays as $day): ?>
                                    <option value="<?php echo $day['id']; ?>">
                                        Day <?php echo $day['day_number']; ?>: <?php echo htmlspecialchars($day['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="exercise_id">Exercise</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="exercise_id" name="exercise_id" required>
                                <option value="">Select an exercise</option>
                                <?php foreach ($exercises as $exercise): ?>
                                    <option value="<?php echo $exercise['id']; ?>">
                                        <?php echo htmlspecialchars($exercise['name']); ?> 
                                        (<?php echo ucfirst($exercise['muscle_group']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exercise_order">Exercise Order</label>
                            <input type="number" class="form-control" id="exercise_order" name="exercise_order" 
                                   min="1" max="20" value="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="sets">Sets</label>
                            <input type="number" class="form-control" id="sets" name="sets" 
                                   min="1" max="10" value="3" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reps">Reps</label>
                            <input type="text" class="form-control" id="reps" name="reps" 
                                   placeholder="e.g., 8-12" value="8-12" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="rest_seconds">Rest (seconds)</label>
                            <input type="number" class="form-control" id="rest_seconds" name="rest_seconds" 
                                   min="0" max="300" value="60" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" 
                                  placeholder="Any special instructions..." rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Exercise to Day
                    </button>
                </form>
            </div>
        </div>

        <!-- Workout Days Display -->
        <div class="workout-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Workout Plan Structure
                </h3>
                <span class="plan-difficulty beginner">
                    <?php echo count($workoutDays); ?> Days
                </span>
            </div>
            
            <div class="days-grid">
                <?php if (!empty($workoutDays)): ?>
                    <?php foreach ($workoutDays as $day): ?>
                        <?php $dayExercises = $faithFit->getWorkoutDayWithExercises($day['id']); ?>
                        <div class="day-item">
                            <div class="day-header">
                                <div class="day-title">
                                    Day <?php echo $day['day_number']; ?>: <?php echo htmlspecialchars($day['name']); ?>
                                </div>
                                <?php if ($day['description']): ?>
                                    <div class="day-meta"><?php echo htmlspecialchars($day['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="exercises-list">
                                <?php if (!empty($dayExercises)): ?>
                                    <?php foreach ($dayExercises as $exercise): ?>
                                        <div class="exercise-item">
                                            <div class="exercise-info">
                                                <h4><?php echo htmlspecialchars($exercise['exercise_name']); ?></h4>
                                                <div class="exercise-meta">
                                                    <?php echo ucfirst($exercise['muscle_group']); ?> • 
                                                    <?php echo $exercise['sets']; ?> sets × <?php echo $exercise['reps']; ?> reps •
                                                    <?php echo $exercise['rest_seconds']; ?>s rest
                                                </div>
                                                <?php if ($exercise['notes']): ?>
                                                    <div class="exercise-meta" style="margin-top: 0.25rem;">
                                                        Note: <?php echo htmlspecialchars($exercise['notes']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="exercise-sets">
                                                <?php echo $exercise['exercise_order']; ?>.
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state" style="padding: 2rem;">
                                        <i class="fas fa-dumbbell"></i>
                                        <p>No exercises added to this day yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <p>No workout days created yet.</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #64748b;">
                            Add workout days using the form above to build your plan.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-increment day number
            const dayNumberInput = document.getElementById('day_number');
            if (dayNumberInput) {
                // Set to next available day number
                const currentDays = <?php echo count($workoutDays); ?>;
                dayNumberInput.value = currentDays + 1;
            }

            // Form validation enhancement
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                });
            });
        });
    </script>
</body>
</html>