<?php
require_once 'config.php';
require_once 'functions.php';

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Get user data
$user = $faithFit->getUserProfile($user_id);
$prayer_requests = $faithFit->getPrayerRequests();
$testimonials = $faithFit->getTestimonials();

// Determine theme
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : ($user['theme_preference'] ?? 'dark');
$theme_class = $theme === 'light' ? 'light-mode' : 'dark-mode';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community | FaithFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/community.css">
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
            <!-- Community Page -->
            <div class="page active" id="community-page">
                <div class="page-header">
                    <h1 class="page-title">Community</h1>
                    <p class="page-subtitle">Pray together, grow together</p>
                </div>

                <div class="community-content">
                    <div class="tab-nav">
                        <button class="tab-btn active" data-tab="prayer">
                            <i class="fas fa-hands-praying"></i>
                            Prayer Requests
                        </button>
                        <button class="tab-btn" data-tab="testimonials">
                            <i class="fas fa-heart"></i>
                            Testimonials
                        </button>
                    </div>

                    <!-- Prayer Requests Tab -->
                    <div class="tab-content active" data-tab="prayer">
                        <?php if (!empty($prayer_requests)): ?>
                            <?php foreach ($prayer_requests as $request): ?>
                                <div class="prayer-item">
                                    <div class="prayer-header">
                                        <div class="prayer-user-info">
                                            <div class="prayer-avatar">
                                                <?php echo substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1); ?>
                                            </div>
                                            <div class="prayer-info">
                                                <div class="prayer-name"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                                                <div class="prayer-time"><?php echo time_elapsed_string($request['created_at']); ?></div>
                                            </div>
                                        </div>
                                        <div class="prayer-category-tag">
                                            <?php echo htmlspecialchars(ucfirst($request['category'])); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($request['title'])): ?>
                                        <div class="prayer-title"><?php echo htmlspecialchars($request['title']); ?></div>
                                    <?php endif; ?>
                                    <div class="prayer-content">
                                        <?php echo htmlspecialchars($request['content']); ?>
                                    </div>
                                    <div class="prayer-actions">
                                        <button class="prayer-action-btn <?php echo $request['user_prayed'] ? 'active' : ''; ?>" 
                                                data-request-id="<?php echo $request['id']; ?>">
                                            <i class="fas fa-hands-praying"></i>
                                            Pray (<span class="prayer-count"><?php echo $request['prayer_count']; ?></span>)
                                        </button>
                                        <button class="prayer-action-btn">
                                            <i class="fas fa-comment"></i>
                                            <span><?php echo rand(0, 10); ?></span> responses
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-hands-praying"></i>
                                <p>No prayer requests yet. Be the first to share!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Testimonials Tab -->
                    <div class="tab-content" data-tab="testimonials">
                        <?php if (!empty($testimonials)): ?>
                            <?php foreach ($testimonials as $testimonial): ?>
                                <div class="testimonial-item">
                                    <div class="testimonial-header">
                                        <div class="testimonial-user-info">
                                            <div class="testimonial-avatar">
                                                <?php echo substr($testimonial['first_name'], 0, 1) . substr($testimonial['last_name'], 0, 1); ?>
                                            </div>
                                            <div class="testimonial-info">
                                                <div class="testimonial-name"><?php echo htmlspecialchars($testimonial['first_name'] . ' ' . $testimonial['last_name']); ?></div>
                                                <div class="testimonial-time"><?php echo time_elapsed_string($testimonial['created_at']); ?></div>
                                            </div>
                                        </div>
                                        <div class="testimonial-category-tag">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $testimonial['category']))); ?>
                                        </div>
                                    </div>
                                    <div class="testimonial-title"><?php echo htmlspecialchars($testimonial['title']); ?></div>
                                    <div class="testimonial-content">
                                        <?php echo htmlspecialchars($testimonial['content']); ?>
                                    </div>
                                    <div class="testimonial-actions">
                                        <button class="testimonial-action-btn <?php echo $testimonial['user_liked'] ? 'active' : ''; ?>" 
                                                data-testimonial-id="<?php echo $testimonial['id']; ?>">
                                            <i class="fas fa-heart"></i>
                                            <span class="like-count"><?php echo $testimonial['like_count']; ?></span> likes
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-heart"></i>
                                <p>No testimonials yet. Share your story to inspire others!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Floating Action Buttons -->
                <div class="floating-action-buttons">
                    <button class="floating-action-btn primary" id="share-prayer-btn" title="Share Prayer Request">
                        <i class="fas fa-hands-praying"></i>
                    </button>
                    <button class="floating-action-btn secondary" id="share-testimonial-btn" title="Share Testimonial">
                        <i class="fas fa-heart"></i>
                    </button>
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
            <a href="community.php" class="nav-item active">
                <i class="fas fa-users"></i>
                <span>Community</span>
            </a>
        </nav>
    </div>

    <!-- Prayer Request Modal -->
    <div class="modal" id="prayer-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Share Prayer Request</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="create_prayer_request">
                <div class="form-group">
                    <label for="prayer-title">Title (Optional)</label>
                    <input type="text" class="form-control" id="prayer-title" name="title" placeholder="Brief title for your request">
                </div>
                <div class="form-group">
                    <label for="prayer-category">Category</label>
                    <select class="form-control" id="prayer-category" name="category">
                        <option value="fitness">Fitness Goals</option>
                        <option value="healing">Healing</option>
                        <option value="strength">Strength</option>
                        <option value="discipline">Discipline</option>
                        <option value="spiritual">Spiritual Growth</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="prayer-content">Your Request</label>
                    <textarea class="form-control" id="prayer-content" name="content" placeholder="Share your prayer request..." required rows="5"></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_anonymous" value="1">
                        <span class="checkmark"></span>
                        Share anonymously
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Share Request
                </button>
            </form>
        </div>
    </div>

    <!-- Testimonial Modal -->
    <div class="modal" id="testimonial-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Share Your Story</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="api.php">
                <input type="hidden" name="action" value="create_testimonial">
                <div class="form-group">
                    <label for="testimonial-title">Title</label>
                    <input type="text" class="form-control" id="testimonial-title" name="title" placeholder="Give your story a title" required>
                </div>
                <div class="form-group">
                    <label for="testimonial-category">Category</label>
                    <select class="form-control" id="testimonial-category" name="category">
                        <option value="weight-loss">Weight Loss</option>
                        <option value="strength">Strength Gain</option>
                        <option value="healing">Healing Journey</option>
                        <option value="discipline">Discipline & Consistency</option>
                        <option value="spiritual">Spiritual Growth</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="testimonial-content">Your Story</label>
                    <textarea class="form-control" id="testimonial-content" name="content" placeholder="Share how faith has impacted your fitness journey..." required rows="6"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-share"></i>
                    Share Story
                </button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
    // Community-specific JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                // Update active tab button
                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show active tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.getAttribute('data-tab') === tabName) {
                        content.classList.add('active');
                    }
                });
            });
        });

        // Prayer action buttons
        const prayerActionBtns = document.querySelectorAll('.prayer-action-btn[data-request-id]');
        prayerActionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-request-id');
                const isActive = this.classList.contains('active');
                
                // Toggle prayer state via AJAX
                fetch('api.php?api=pray', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        prayer_request_id: parseInt(requestId)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        this.classList.toggle('active');
                        const prayerCount = this.querySelector('.prayer-count');
                        let count = parseInt(prayerCount.textContent);
                        
                        if (data.data.prayed) {
                            prayerCount.textContent = count + 1;
                        } else {
                            prayerCount.textContent = Math.max(0, count - 1);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // Testimonial like buttons
        const testimonialActionBtns = document.querySelectorAll('.testimonial-action-btn[data-testimonial-id]');
        testimonialActionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const testimonialId = this.getAttribute('data-testimonial-id');
                
                // Toggle like state via AJAX
                fetch('api.php?api=like-testimonial', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        testimonial_id: parseInt(testimonialId)
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        this.classList.toggle('active');
                        const likeCount = this.querySelector('.like-count');
                        let count = parseInt(likeCount.textContent);
                        
                        if (data.data.liked) {
                            likeCount.textContent = count + 1;
                        } else {
                            likeCount.textContent = Math.max(0, count - 1);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // Modal functionality
        const sharePrayerBtn = document.getElementById('share-prayer-btn');
        const shareTestimonialBtn = document.getElementById('share-testimonial-btn');
        const prayerModal = document.getElementById('prayer-modal');
        const testimonialModal = document.getElementById('testimonial-modal');
        const closeButtons = document.querySelectorAll('.close');

        // Open prayer modal
        sharePrayerBtn.addEventListener('click', function() {
            prayerModal.style.display = 'flex';
        });

        // Open testimonial modal
        shareTestimonialBtn.addEventListener('click', function() {
            testimonialModal.style.display = 'flex';
        });

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

        // Form submission handling
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const action = this.getAttribute('action');
                
                fetch(action, {
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
                    // Handle success - you might want to show a success message
                    // and refresh the content instead of redirecting
                    window.location.reload();
                })
                .catch(error => console.error('Error:', error));
            });
        });
    });
    </script>
</body>
</html>