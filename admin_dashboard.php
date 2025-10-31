<?php
require_once 'config.php';
require_once 'functions.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$faithFit = new FaithFitFunctions();

// Get admin statistics
$stats = $faithFit->getAdminStats();
$recentUsers = $faithFit->getRecentUsers(5);
$recentTestimonials = $faithFit->getRecentTestimonials(5);
$recentPrayers = $faithFit->getRecentPrayerRequests(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaithFit | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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

        .admin-welcome {
            color: #94a3b8;
            font-size: 0.9rem;
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

        .admin-container {
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
            font-size: 1.5rem;
            font-weight: 700;
        }

        .welcome-section p {
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
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

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.3);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-card.users::before { background: var(--gradient-admin); }
        .stat-card.testimonials::before { background: var(--gradient-success); }
        .stat-card.prayers::before { background: var(--gradient-info); }
        .stat-card.steps::before { background: var(--gradient-warning); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(255,255,255,0.05);
        }

        .stat-icon.users { color: #8B5CF6; }
        .stat-icon.testimonials { color: #10B981; }
        .stat-icon.prayers { color: #3B82F6; }
        .stat-icon.steps { color: #F59E0B; }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .stat-trend.down {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-content p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .section-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid var(--glass-border);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #8B5CF6;
            font-size: 1.1rem;
        }

        .view-all {
            color: #8B5CF6;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .view-all:hover {
            color: #7C3AED;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            background: rgba(255,255,255,0.05);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(139, 92, 246, 0.1);
            color: #8B5CF6;
            font-size: 1rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            color: #fff;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .activity-content p {
            color: #94a3b8;
            font-size: 0.85rem;
        }

        .activity-time {
            color: #64748b;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 1.5rem 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .action-btn:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: rgba(139, 92, 246, 0.3);
            color: #8B5CF6;
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 1.5rem;
        }

        .action-btn span {
            font-size: 0.85rem;
            font-weight: 600;
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
            .admin-container {
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .section-card {
                padding: 1.5rem;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .admin-header {
                padding: 1rem;
            }

            .admin-container {
                padding: 0.5rem;
            }

            .welcome-section {
                padding: 1.5rem;
            }

            .section-card {
                padding: 1rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .activity-item {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .activity-time {
                align-self: center;
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

        .stat-card, .section-card {
            animation: fadeInUp 0.6s ease;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body class="dark-mode">
    <!-- Admin Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-brand">
                <i class="fas fa-shield-alt"></i>
                <h1>FaithFit Admin Dashboard</h1>
            </div>
            <div class="admin-actions">
                <span class="admin-welcome">
                    Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                </span>
                <a href="admin_dashboard.php?logout=true" class="btn-admin btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="admin-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <h2>Dashboard Overview</h2>
            <p>Monitor and manage your FaithFit application from one centralized location</p>
            <div class="quick-actions">
                <a href="admin_users.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_testimonials.php" class="action-btn">
                    <i class="fas fa-comment"></i>
                    <span>Testimonials</span>
                </a>
                <a href="admin_prayers.php" class="action-btn">
                    <i class="fas fa-pray"></i>
                    <span>Prayer Requests</span>
                </a>
                <a href="admin_analytics.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
                <a href="admin_workouts.php" class="action-btn">
                    <i class="fas fa-dumbbell"></i>
                    <span>Manage Workouts</span>
                </a>
            </div>
        </section>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card users">
                <div class="stat-header">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        12%
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_users'] ?? '0'; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card testimonials">
                <div class="stat-header">
                    <div class="stat-icon testimonials">
                        <i class="fas fa-comment"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        8%
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_testimonials'] ?? '0'; ?></h3>
                    <p>Testimonials</p>
                </div>
            </div>

            <div class="stat-card prayers">
                <div class="stat-header">
                    <div class="stat-icon prayers">
                        <i class="fas fa-pray"></i>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        15%
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_prayers'] ?? '0'; ?></h3>
                    <p>Prayer Requests</p>
                </div>
            </div>

            <div class="stat-card steps">
                <div class="stat-header">
                    <div class="stat-icon steps">
                        <i class="fas fa-shoe-prints"></i>
                    </div>
                    <div class="stat-trend down">
                        <i class="fas fa-arrow-down"></i>
                        3%
                    </div>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_steps'] ?? '0'; ?></h3>
                    <p>Total Steps Tracked</p>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Recent Users -->
                <section class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-user-plus"></i>
                            Recent Users
                        </h3>
                        <a href="admin_users.php" class="view-all">
                            View All
                        </a>
                    </div>
                    <div class="activity-list">
                        <?php if (!empty($recentUsers)): ?>
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Recent Testimonials -->
                <section class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-comment-medical"></i>
                            Recent Testimonials
                        </h3>
                        <a href="admin_testimonials.php" class="view-all">
                            View All
                        </a>
                    </div>
                    <div class="activity-list">
                        <?php if (!empty($recentTestimonials)): ?>
                            <?php foreach ($recentTestimonials as $testimonial): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-quote-left"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4><?php echo htmlspecialchars($testimonial['user_name']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($testimonial['testimonial'], 0, 60) . '...'); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j', strtotime($testimonial['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-comment-slash"></i>
                                <p>No testimonials yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Recent Prayer Requests -->
                <section class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-praying-hands"></i>
                            Recent Prayers
                        </h3>
                        <a href="admin_prayers.php" class="view-all">
                            View All
                        </a>
                    </div>
                    <div class="activity-list">
                        <?php if (!empty($recentPrayers)): ?>
                            <?php foreach ($recentPrayers as $prayer): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-hands-praying"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h4><?php echo htmlspecialchars($prayer['user_name']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($prayer['prayer_request'], 0, 50) . '...'); ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j', strtotime($prayer['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-pray"></i>
                                <p>No prayer requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- System Status -->
                <section class="section-card">
                    <div class="section-header">
                        <h3 class="section-title">
                            <i class="fas fa-server"></i>
                            System Status
                        </h3>
                    </div>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Application</h4>
                                <p>All systems operational</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Database</h4>
                                <p>Connected and running</p>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Security</h4>
                                <p>Protected and monitored</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.stat-card, .section-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add loading animation
            const stats = document.querySelectorAll('.stat-content h3');
            stats.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 30;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        stat.textContent = finalValue.toLocaleString();
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(currentValue).toLocaleString();
                    }
                }, 50);
            });
        });
    </script>
</body>
</html>