<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get devotion ID from query parameter
$devotion_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$devotion_id) {
    // Redirect to dashboard if no devotion ID provided
    header('Location: index.php');
    exit;
}

// Get devotion details
$devotion = $faithFit->getDevotionById($devotion_id, $user_id);

if (!$devotion) {
    // Redirect if devotion not found
    header('Location: index.php');
    exit;
}

// Get user data for theme
$user = $faithFit->getUserProfile($user_id);
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($devotion['title'] ?? 'Daily Devotion'); ?> | FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .devotion-detail-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--hover-color);
            color: var(--text-color);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }

        .devotion-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .devotion-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 15px;
        }

        .devotion-date {
            font-size: 1.1rem;
            color: var(--text-muted);
        }

        .devotion-content {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .bible-verse {
            margin-bottom: 30px;
            padding: 25px;
            background: var(--hover-color);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .verse-text {
            font-size: 1.4rem;
            font-style: italic;
            color: var(--text-color);
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .verse-reference {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .devotion-section {
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-content {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-color);
        }

        .reflection-points {
            list-style: none;
            padding: 0;
        }

        .reflection-points li {
            margin-bottom: 15px;
            padding-left: 25px;
            position: relative;
        }

        .reflection-points li:before {
            content: "•";
            color: var(--primary-color);
            font-size: 1.5rem;
            position: absolute;
            left: 0;
            top: -2px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .devotion-detail-container {
                padding: 15px;
            }

            .devotion-title {
                font-size: 2rem;
            }

            .devotion-content {
                padding: 25px 20px;
            }

            .verse-text {
                font-size: 1.2rem;
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
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="devotion-detail-container">
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>

                <div class="devotion-header">
                    <h1 class="devotion-title"><?php echo htmlspecialchars($devotion['title'] ?? 'Daily Devotion'); ?></h1>
                    <div class="devotion-date">
                        <?php echo date('F j, Y', strtotime($devotion['date'] ?? 'now')); ?>
                    </div>
                </div>

                <div class="devotion-content">
                    <!-- Bible Verse -->
                    <div class="bible-verse">
                        <p class="verse-text">"<?php echo htmlspecialchars($devotion['verse_text']); ?>"</p>
                        <p class="verse-reference">— <?php echo htmlspecialchars($devotion['verse_reference']); ?></p>
                    </div>

                    <!-- Reflection -->
                    <div class="devotion-section">
                        <h2 class="section-title">
                            <i class="fas fa-lightbulb"></i>
                            Reflection
                        </h2>
                        <div class="section-content">
                            <p><?php echo nl2br(htmlspecialchars($devotion['reflection'])); ?></p>
                            
                            <?php if (!empty($devotion['reflection_points'])): ?>
                                <ul class="reflection-points">
                                    <?php 
                                    $points = explode("\n", $devotion['reflection_points']);
                                    foreach ($points as $point): 
                                        if (trim($point)): 
                                    ?>
                                        <li><?php echo htmlspecialchars(trim($point)); ?></li>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Question -->
                    <div class="devotion-section">
                        <h2 class="section-title">
                            <i class="fas fa-question-circle"></i>
                            Today's Question
                        </h2>
                        <div class="section-content">
                            <p><?php echo nl2br(htmlspecialchars($devotion['question'])); ?></p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn btn-primary" id="mark-complete">
                            <i class="fas fa-check"></i>
                            Mark as Complete
                        </button>
                        <button class="btn btn-secondary" id="save-reflection">
                            <i class="fas fa-bookmark"></i>
                            Save Reflection
                        </button>
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

    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark devotion as complete
            document.getElementById('mark-complete').addEventListener('click', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'complete_devotion';
                
                const devotionInput = document.createElement('input');
                devotionInput.type = 'hidden';
                devotionInput.name = 'devotion_id';
                devotionInput.value = '<?php echo $devotion_id; ?>';
                
                form.appendChild(actionInput);
                form.appendChild(devotionInput);
                document.body.appendChild(form);
                form.submit();
            });

            // Save reflection
            document.getElementById('save-reflection').addEventListener('click', function() {
                // Implement save reflection functionality
                alert('Reflection saved!');
            });

            // Theme toggle
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', function() {
                    const currentTheme = document.body.classList.contains('light-mode') ? 'light' : 'dark';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    
                    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
                    window.location.reload();
                });
            }
        });
    </script>
</body>
</html>