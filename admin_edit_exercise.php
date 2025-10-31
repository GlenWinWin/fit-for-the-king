<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: admin_workouts.php');
    exit;
}

$exercise_id = $_GET['id'];
$faithFit = new FaithFitFunctions();

// Get exercise details
$exercise = null;
$exercises = $faithFit->getExercises();
foreach ($exercises as $ex) {
    if ($ex['id'] == $exercise_id) {
        $exercise = $ex;
        break;
    }
}

if (!$exercise) {
    header('Location: admin_workouts.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_exercise'])) {
    // Debug: Check what data we're receiving
    error_log("Updating exercise ID: " . $exercise_id);
    error_log("Exercise Name: " . $_POST['exercise_name']);
    error_log("Description: " . $_POST['exercise_description']);
    error_log("Muscle Group: " . $_POST['muscle_group']);
    error_log("Equipment: " . $_POST['equipment']);
    error_log("Video URL: " . $_POST['video_url']);
    
    try {
        $result = $faithFit->updateExercise(
            $exercise_id,
            $_POST['exercise_name'],
            $_POST['exercise_description'],
            $_POST['muscle_group'],
            $_POST['equipment'],
            $_POST['video_url']
        );
        
        if ($result) {
            $success = "Exercise updated successfully!";
            // Refresh exercise data
            $exercises = $faithFit->getExercises();
            foreach ($exercises as $ex) {
                if ($ex['id'] == $exercise_id) {
                    $exercise = $ex;
                    break;
                }
            }
        } else {
            $error = "Error updating exercise - method returned false";
            // Try to get more detailed error information
            if (method_exists($faithFit, 'getLastError')) {
                $error .= ": " . $faithFit->getLastError();
            }
        }
    } catch (Exception $e) {
        $error = "Error updating exercise: " . $e->getMessage();
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exercise'])) {
    $result = $faithFit->deleteExercise($exercise_id);
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header('Location: admin_workouts.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Edit Exercise</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gradient-admin: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            --gradient-info: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            --gradient-danger: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
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
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
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

        .btn-danger {
            background: var(--gradient-danger);
            color: white;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
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

        .delete-section {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .delete-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            color: #ef4444;
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
    </style>
</head>
<body class="dark-mode">
    <!-- Admin Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-brand">
                <i class="fas fa-dumbbell"></i>
                <h1>Edit Exercise</h1>
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
        <!-- Exercise Header -->
        <section class="welcome-section">
            <h2>Edit Exercise: <?php echo htmlspecialchars($exercise['name']); ?></h2>
            <p>Update exercise details and information</p>
        </section>

        <?php if (isset($error)): ?>
            <div class="message error">
                <strong>Error:</strong> <?php echo $error; ?>
                <br><small>Check the server error logs for more details.</small>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Edit Exercise Form -->
        <div class="workout-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i>
                    Exercise Details
                </h3>
            </div>
            <form method="POST">
                <input type="hidden" name="update_exercise" value="1">
                
                <div class="form-group">
                    <label for="exercise_name">Exercise Name</label>
                    <input type="text" class="form-control" id="exercise_name" name="exercise_name" required 
                           value="<?php echo htmlspecialchars($exercise['name']); ?>"
                           placeholder="e.g., Barbell Bench Press">
                </div>
                
                <div class="form-group">
                    <label for="exercise_description">Description</label>
                    <textarea class="form-control" id="exercise_description" name="exercise_description" 
                              placeholder="How to perform this exercise..." rows="4"><?php echo htmlspecialchars($exercise['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="muscle_group">Muscle Group</label>
                    <div class="select-wrapper">
                        <select class="form-control" id="muscle_group" name="muscle_group" required>
                            <option value="chest" <?php echo ($exercise['muscle_group'] == 'chest') ? 'selected' : ''; ?>>Chest</option>
                            <option value="back" <?php echo ($exercise['muscle_group'] == 'back') ? 'selected' : ''; ?>>Back</option>
                            <option value="legs" <?php echo ($exercise['muscle_group'] == 'legs') ? 'selected' : ''; ?>>Legs</option>
                            <option value="shoulders" <?php echo ($exercise['muscle_group'] == 'shoulders') ? 'selected' : ''; ?>>Shoulders</option>
                            <option value="arms" <?php echo ($exercise['muscle_group'] == 'arms') ? 'selected' : ''; ?>>Arms</option>
                            <option value="core" <?php echo ($exercise['muscle_group'] == 'core') ? 'selected' : ''; ?>>Core</option>
                            <option value="full_body" <?php echo ($exercise['muscle_group'] == 'full_body') ? 'selected' : ''; ?>>Full Body</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="equipment">Equipment</label>
                    <div class="select-wrapper">
                        <select class="form-control" id="equipment" name="equipment" required>
                            <option value="barbell" <?php echo ($exercise['equipment'] == 'barbell') ? 'selected' : ''; ?>>Barbell</option>
                            <option value="dumbbell" <?php echo ($exercise['equipment'] == 'dumbbell') ? 'selected' : ''; ?>>Dumbbell</option>
                            <option value="machine" <?php echo ($exercise['equipment'] == 'machine') ? 'selected' : ''; ?>>Machine</option>
                            <option value="bodyweight" <?php echo ($exercise['equipment'] == 'bodyweight') ? 'selected' : ''; ?>>Bodyweight</option>
                            <option value="cable" <?php echo ($exercise['equipment'] == 'cable') ? 'selected' : ''; ?>>Cable</option>
                            <option value="kettlebell" <?php echo ($exercise['equipment'] == 'kettlebell') ? 'selected' : ''; ?>>Kettlebell</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="video_url">Demonstration Video URL (Optional)</label>
                    <input type="url" class="form-control" id="video_url" name="video_url" 
                           value="<?php echo htmlspecialchars($exercise['demonstration_video_url'] ?? ''); ?>"
                           placeholder="https://youtube.com/...">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Exercise
                </button>
            </form>

            <!-- Delete Section -->
            <div class="delete-section">
                <div class="delete-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h4>Danger Zone</h4>
                </div>
                <p style="color: #94a3b8; margin-bottom: 1rem;">
                    Once you delete an exercise, there is no going back. Please be certain.
                </p>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this exercise? This action cannot be undone.');">
                    <input type="hidden" name="delete_exercise" value="1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i>
                        Delete Exercise
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to all interactive elements
            const interactiveElements = document.querySelectorAll('.btn');
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