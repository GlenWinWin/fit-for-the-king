<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

$faithFit = new FaithFitFunctions();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_workout_plan'])) {
        $result = $faithFit->createWorkoutPlan(
            $_POST['plan_name'],
            $_POST['plan_description'],
            $_POST['duration_weeks'],
            $_POST['difficulty'],
            $_SESSION['admin_id']
        );
        
        if ($result['success']) {
            $_SESSION['success'] = "Workout plan created successfully!";
            // Redirect to add workout days
            header("Location: admin_workout_days.php?plan_id=" . $result['plan_id']);
            exit;
        } else {
            $error = $result['message'];
        }
    }
    
    if (isset($_POST['create_exercise'])) {
        $result = $faithFit->createExercise(
            $_POST['exercise_name'],
            $_POST['exercise_description'],
            $_POST['muscle_group'],
            $_POST['equipment'],
            $_POST['video_url']
        );
        
        if ($result) {
            $success = "Exercise created successfully!";
        } else {
            $error = "Error creating exercise";
        }
    }
}

// Get existing data
$workoutPlans = $faithFit->getWorkoutPlans();
$exercises = $faithFit->getExercises();

// Check for success message from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Manage Workouts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
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

        .welcome-section h2 {
            color: #fff;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .welcome-section p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

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

        .form-control::placeholder {
            color: #64748b;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* Custom Select Styling */
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%2394a3b8"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat center;
            pointer-events: none;
            transition: transform 0.3s ease;
        }

        .select-wrapper:focus-within::after {
            transform: translateY(-50%) rotate(180deg);
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
            padding-right: 45px;
            background: rgba(255,255,255,0.05);
            background-image: none;
        }

        select.form-control option {
            background: #1a1a1a;
            color: #fff;
            padding: 12px;
            border: none;
        }

        select.form-control:focus {
            background: rgba(255,255,255,0.08);
        }

        /* Custom select hover states */
        select.form-control:hover {
            border-color: rgba(255,255,255,0.2);
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

        .btn-primary:active {
            transform: translateY(0);
        }

        .plans-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .plan-item {
            background: rgba(255,255,255,0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .plan-item:hover {
            background: rgba(255,255,255,0.05);
            transform: translateX(5px);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .plan-name {
            font-weight: 700;
            color: #fff;
            font-size: 1.1rem;
        }

        .plan-difficulty {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .beginner { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .intermediate { background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .advanced { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        .plan-meta {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .plan-description {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .exercises-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .exercise-item {
            background: rgba(255,255,255,0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .exercise-item:hover {
            background: rgba(255,255,255,0.05);
            transform: translateY(-3px);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .exercise-name {
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }

        .exercise-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .exercise-tag {
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
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

        .empty-state p {
            font-size: 1rem;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .workout-container {
                padding: 1rem;
            }

            .admin-header {
                padding: 1rem;
            }

            .admin-nav {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .admin-actions {
                width: 100%;
                justify-content: space-between;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .workout-card {
                padding: 1.5rem;
            }

            .exercises-grid {
                grid-template-columns: 1fr;
            }

            .exercise-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .workout-container {
                padding: 0.5rem;
            }

            .welcome-section {
                padding: 1rem;
            }

            .workout-card {
                padding: 1rem;
            }

            .btn {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
        }

        /* Animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .workout-card {
            animation: fadeInUp 0.6s ease;
        }

        .workout-card:nth-child(1) { animation-delay: 0.1s; }
        .workout-card:nth-child(2) { animation-delay: 0.2s; }
    </style>
</head>
<body class="dark-mode">
    <!-- Admin Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-brand">
                <i class="fas fa-dumbbell"></i>
                <h1>FaithFit Workout Management</h1>
            </div>
            <div class="admin-actions">
                <a href="admin_dashboard.php" class="btn-admin btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="admin_dashboard.php?logout=true" class="btn-admin btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="workout-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <h2>Workout Management</h2>
            <p>Create and manage workout plans for FaithFit users</p>
        </section>

        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Forms Grid -->
        <div class="workout-grid">
            <!-- Create Workout Plan -->
            <div class="workout-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Create New Workout Plan
                    </h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="create_workout_plan" value="1">
                    
                    <div class="form-group">
                        <label for="plan_name">Plan Name</label>
                        <input type="text" class="form-control" id="plan_name" name="plan_name" required 
                               placeholder="e.g., 4-Week Beginner Strength Program">
                    </div>
                    
                    <div class="form-group">
                        <label for="plan_description">Description</label>
                        <textarea class="form-control" id="plan_description" name="plan_description" 
                                  placeholder="Describe this workout plan..." rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_weeks">Duration (Weeks)</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="duration_weeks" name="duration_weeks" required>
                                <option value="4">4 Weeks</option>
                                <option value="6">6 Weeks</option>
                                <option value="8">8 Weeks</option>
                                <option value="12">12 Weeks</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="difficulty">Difficulty Level</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="difficulty" name="difficulty" required>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-dumbbell"></i>
                        Create Workout Plan
                    </button>
                </form>
            </div>

            <!-- Create Exercise -->
            <div class="workout-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus-circle"></i>
                        Add New Exercise
                    </h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="create_exercise" value="1">
                    
                    <div class="form-group">
                        <label for="exercise_name">Exercise Name</label>
                        <input type="text" class="form-control" id="exercise_name" name="exercise_name" required 
                               placeholder="e.g., Barbell Bench Press">
                    </div>
                    
                    <div class="form-group">
                        <label for="exercise_description">Description</label>
                        <textarea class="form-control" id="exercise_description" name="exercise_description" 
                                  placeholder="How to perform this exercise..." rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="muscle_group">Muscle Group</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="muscle_group" name="muscle_group" required>
                                <option value="chest">Chest</option>
                                <option value="back">Back</option>
                                <option value="legs">Legs</option>
                                <option value="shoulders">Shoulders</option>
                                <option value="arms">Arms</option>
                                <option value="core">Core</option>
                                <option value="full_body">Full Body</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="equipment">Equipment</label>
                        <div class="select-wrapper">
                            <select class="form-control" id="equipment" name="equipment" required>
                                <option value="barbell">Barbell</option>
                                <option value="dumbbell">Dumbbell</option>
                                <option value="machine">Machine</option>
                                <option value="bodyweight">Bodyweight</option>
                                <option value="cable">Cable</option>
                                <option value="kettlebell">Kettlebell</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="video_url">Demonstration Video URL (Optional)</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" 
                               placeholder="https://youtube.com/...">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Exercise
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Workout Plans -->
        <div class="workout-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Existing Workout Plans
                </h3>
                <span class="plan-difficulty beginner">
                    <?php echo count($workoutPlans); ?> Plans
                </span>
            </div>
            <div class="plans-grid">
                <?php if (!empty($workoutPlans)): ?>
                    <?php foreach ($workoutPlans as $plan): ?>
                        <div class="plan-item" onclick="window.location.href='admin_workout_days.php?plan_id=<?php echo $plan['id']; ?>'">
                            <div class="plan-header">
                                <span class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></span>
                                <span class="plan-difficulty <?php echo $plan['difficulty']; ?>">
                                    <?php echo ucfirst($plan['difficulty']); ?>
                                </span>
                            </div>
                            <div class="plan-meta">
                                <?php echo $plan['duration_weeks']; ?> weeks • 
                                Created by <?php echo htmlspecialchars($plan['created_by_name'] ?? 'Admin'); ?> •
                                <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                            </div>
                            <?php if ($plan['description']): ?>
                                <div class="plan-description">
                                    <?php echo htmlspecialchars($plan['description']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-dumbbell"></i>
                        <p>No workout plans created yet.</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #64748b;">
                            Create your first workout plan using the form above.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Exercise Library -->
        <div class="workout-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-dumbbell"></i>
                    Exercise Library
                </h3>
                <span class="plan-difficulty intermediate">
                    <?php echo count($exercises); ?> Exercises
                </span>
            </div>
            <div class="exercises-grid">
                <?php if (!empty($exercises)): ?>
                    <?php foreach ($exercises as $exercise): ?>
                        <div class="exercise-item">
                            <div class="exercise-name"><?php echo htmlspecialchars($exercise['name']); ?></div>
                            <div class="exercise-meta">
                                <span class="exercise-tag">
                                    <?php echo ucfirst($exercise['muscle_group']); ?>
                                </span>
                                <span class="exercise-tag">
                                    <?php echo ucfirst($exercise['equipment']); ?>
                                </span>
                            </div>
                            <?php if ($exercise['description']): ?>
                                <div style="color: #94a3b8; font-size: 0.9rem; margin-top: 0.75rem; line-height: 1.4;">
                                    <?php echo htmlspecialchars(substr($exercise['description'], 0, 100)); ?>
                                    <?php if (strlen($exercise['description']) > 100): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                <a href="admin_edit_exercise.php?id=<?php echo $exercise['id']; ?>" 
                                class="btn btn-primary" style="padding: 8px 16px; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i>
                                    Edit
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-dumbbell"></i>
                        <p>No exercises in library yet.</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #64748b;">
                            Add exercises using the form above to build your library.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to all interactive elements
            const interactiveElements = document.querySelectorAll('.plan-item, .exercise-item, .btn');
            interactiveElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Form validation enhancement
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after 3 seconds if still on page (form didn't redirect)
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }, 3000);
                });
            });

            // Auto-focus first input
            const firstInput = document.querySelector('input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }

            // Enhanced select interactions
            const selects = document.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                select.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });

                select.addEventListener('change', function() {
                    if (this.value) {
                        this.style.color = '#fff';
                    }
                });
            });
        });

        // Add some visual feedback for form interactions
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>