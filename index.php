<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get user data
$user = $faithFit->getUserProfile($user_id);
$dashboard_data = $faithFit->getDashboardStats($user_id);
$devotion = $faithFit->getDailyDevotion($user_id);

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
        /* Floating Action Button Styles */
        .floating-action-buttons {
            position: fixed;
            bottom: 80px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
        }

        .floating-action-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
        }

        .floating-action-btn.primary {
            background: var(--primary-color);
            color: white;
        }

        .floating-action-btn.secondary {
            background: var(--secondary-color);
            color: white;
        }

        .floating-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .floating-action-btn:active {
            transform: translateY(0);
        }

        /* Tooltip for buttons */
        .floating-action-btn::after {
            content: attr(title);
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--card-bg);
            color: var(--text-color);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            margin-right: 10px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .floating-action-btn:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Mobile-specific improvements */
        @media (max-width: 768px) {
            .floating-action-buttons {
                bottom: 70px;
                right: 15px;
            }

            .floating-action-btn {
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
            }

            .floating-action-btn::after {
                display: none; /* Hide tooltips on mobile */
            }
        }

        /* Prayer Request Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            margin: 0;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
        }

        .close:hover {
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: normal;
        }

        .checkbox-label input {
            margin-right: 8px;
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
                    <div class="main-content-area">
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

                    <!-- Sidebar -->
                    <div class="sidebar">
                        <!-- Progress Stats -->
                        <div class="stats-card">
                            <h3>Weekly Progress</h3>
                            <div class="stat-item">
                                <div class="stat-info">
                                    <div class="stat-label">Workouts Completed</div>
                                    <div class="stat-value">2/4</div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 50%;"></div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-info">
                                    <div class="stat-label">Prayer Time</div>
                                    <div class="stat-value">45min</div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 75%;"></div>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-info">
                                    <div class="stat-label">Steps Average</div>
                                    <div class="stat-value"><?php echo number_format($dashboard_data['today_steps'] ?? 6842); ?></div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, (($dashboard_data['today_steps'] ?? 0) / ($dashboard_data['step_goal'] ?? 8000)) * 100); ?>%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick-actions-card">
                            <h3>Quick Actions</h3>
                            <button class="quick-btn" id="quick-prayer">
                                <i class="fas fa-pray"></i>
                                Prayer Time
                            </button>
                            <button class="quick-btn" id="quick-workout">
                                <i class="fas fa-dumbbell"></i>
                                Start Workout
                            </button>
                            <button class="quick-btn" id="quick-bible">
                                <i class="fas fa-bible"></i>
                                Daily Reading
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Floating Action Buttons -->
        <div class="floating-action-buttons">
            <button class="floating-action-btn primary" id="quick-prayer-request" title="Quick Prayer Request">
                <i class="fas fa-hands-praying"></i>
            </button>
            <button class="floating-action-btn secondary" id="quick-note" title="Add Quick Note">
                <i class="fas fa-sticky-note"></i>
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
                <h3 class="modal-title">Quick Prayer Request</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="create_prayer_request">
                <div class="form-group">
                    <label for="prayer-title">Title (Optional)</label>
                    <input type="text" class="form-control" id="prayer-title" name="title" placeholder="Brief title for your request">
                </div>
                <div class="form-group">
                    <label for="prayer-content">Your Request</label>
                    <textarea class="form-control" id="prayer-content" name="content" placeholder="Share your prayer request..." required rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Share Prayer
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Note Modal -->
    <div class="modal" id="note-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Quick Note</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="create_note">
                <div class="form-group">
                    <label for="note-title">Title (Optional)</label>
                    <input type="text" class="form-control" id="note-title" name="title" placeholder="Note title">
                </div>
                <div class="form-group">
                    <label for="note-content">Your Note</label>
                    <textarea class="form-control" id="note-content" name="content" placeholder="Write your note..." required rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Note
                </button>
            </form>
        </div>
    </div>

    <!-- Existing Modals (Weight and Steps) -->
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
        const quickPrayerBtn = document.getElementById('quick-prayer-request');
        const quickNoteBtn = document.getElementById('quick-note');
        const prayerModal = document.getElementById('prayer-modal');
        const noteModal = document.getElementById('note-modal');

        // Open prayer modal
        if (quickPrayerBtn) {
            quickPrayerBtn.addEventListener('click', function() {
                prayerModal.style.display = 'flex';
            });
        }

        // Open note modal
        if (quickNoteBtn) {
            quickNoteBtn.addEventListener('click', function() {
                noteModal.style.display = 'flex';
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

        // Quick action buttons
        const quickPrayerActionBtn = document.getElementById('quick-prayer');
        const quickWorkoutBtn = document.getElementById('quick-workout');
        const quickBibleBtn = document.getElementById('quick-bible');

        if (quickPrayerActionBtn) {
            quickPrayerActionBtn.addEventListener('click', function() {
                // Redirect to community page with prayer tab active
                window.location.href = 'community.php';
            });
        }

        if (quickWorkoutBtn) {
            quickWorkoutBtn.addEventListener('click', function() {
                // Placeholder for workout functionality
                alert('Workout feature coming soon!');
            });
        }

        if (quickBibleBtn) {
            quickBibleBtn.addEventListener('click', function() {
                // Scroll to devotion section
                document.querySelector('.devotion-card').scrollIntoView({ 
                    behavior: 'smooth' 
                });
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

        // Form submission handling for new modals
        const prayerForm = document.querySelector('#prayer-modal form');
        const noteForm = document.querySelector('#note-modal form');

        if (prayerForm) {
            prayerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch(this.action, {
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
                    // Close modal and show success message
                    prayerModal.style.display = 'none';
                    alert('Prayer request shared successfully!');
                    // Optionally refresh the page or update UI
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sharing prayer request. Please try again.');
                });
            });
        }

        if (noteForm) {
            noteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch(this.action, {
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
                    // Close modal and show success message
                    noteModal.style.display = 'none';
                    alert('Note saved successfully!');
                    // Optionally refresh the page or update UI
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving note. Please try again.');
                });
            });
        }
    });
    </script>
</body>
</html>