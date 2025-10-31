// Theme functionality with AJAX
function initTheme() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');
    const body = document.body;

    themeToggle.addEventListener('click', function () {
        const currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Update theme immediately for better UX
        updateThemeLocally(newTheme, themeIcon, themeToggle);

        // Send AJAX request to save theme preference
        fetch('api.php?api=theme', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                theme: newTheme
            })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save theme');
                }
                // Show success notification
                showNotification('Theme updated successfully!', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert theme if save failed
                updateThemeLocally(currentTheme, themeIcon, themeToggle);
                showNotification('Failed to save theme preference', 'error');
            });
    });
}

// Helper function to update theme locally
function updateThemeLocally(theme, themeIcon, themeToggle) {
    const body = document.body;

    // Remove existing theme classes
    body.classList.remove('dark-mode', 'light-mode');

    // Add new theme class
    body.classList.add(theme === 'dark' ? 'dark-mode' : 'light-mode');

    // Update theme icon
    themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

    // Update button title
    themeToggle.title = `Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`;

    // Update logo
    updateLogo(theme);
}

// Update logo based on theme
function updateLogo(theme) {
    const logoImg = document.querySelector('.logo img');
    if (logoImg) {
        logoImg.src = 'imgs/dark-logo.png';
    }
}

// Update time and date
function updateTime() {
    const now = new Date();
    const timeElement = document.getElementById('current-time');
    const dateElement = document.getElementById('current-date');

    // Format time
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    if (timeElement) {
        timeElement.textContent = `${hours}:${minutes}`;
    }

    // Format date
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Navigation functionality
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const pages = document.querySelectorAll('.page');

    navItems.forEach(item => {
        item.addEventListener('click', function () {
            const targetPage = this.getAttribute('data-page');

            // Update active nav item
            navItems.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');

            // Show target page
            pages.forEach(page => page.classList.remove('active'));
            document.getElementById(targetPage).classList.add('active');
        });
    });
}

// Tab functionality for community page
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(tab => {
        tab.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');

            // Update active tab
            tabBtns.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Show target tab content
            tabContents.forEach(content => content.classList.remove('active'));
            document.querySelector(`.tab-content[data-tab="${targetTab}"]`).classList.add('active');
        });
    });
}

// Habit completion toggle
function initHabitTracking() {
    const habitCheckboxes = document.querySelectorAll('.habit-checkbox');

    habitCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('click', function () {
            const isCompleted = this.classList.contains('checked');
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api.php';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'action';
            input.value = 'complete_devotion';

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        });
    });
}

// Modal functionality
function initModals() {
    const weightModal = document.getElementById('weight-modal');
    const stepsModal = document.getElementById('steps-modal');
    const storyModal = document.getElementById('story-modal');
    const prayerModal = document.getElementById('prayer-modal');

    const weightBtn = document.getElementById('log-weight-btn');
    const stepsBtn = document.getElementById('log-steps-btn');
    const shareStoryBtn = document.getElementById('share-story-btn');

    const closeButtons = document.querySelectorAll('.close');

    // Open modals
    if (weightBtn) {
        weightBtn.addEventListener('click', function () {
            weightModal.style.display = 'flex';
            // Auto-focus weight input
            setTimeout(() => {
                const weightInput = document.getElementById('weight-value');
                if (weightInput) weightInput.focus();
            }, 100);
        });
    }

    if (stepsBtn) {
        stepsBtn.addEventListener('click', function () {
            stepsModal.style.display = 'flex';
            // Auto-focus steps input
            setTimeout(() => {
                const stepsInput = document.getElementById('steps-value');
                if (stepsInput) stepsInput.focus();
            }, 100);
        });
    }

    if (shareStoryBtn) {
        shareStoryBtn.addEventListener('click', function () {
            storyModal.style.display = 'flex';
        });
    }

    // Close modals
    closeButtons.forEach(button => {
        button.addEventListener('click', function () {
            weightModal.style.display = 'none';
            stepsModal.style.display = 'none';
            storyModal.style.display = 'none';
            prayerModal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target === weightModal) {
            weightModal.style.display = 'none';
        }
        if (event.target === stepsModal) {
            stepsModal.style.display = 'none';
        }
        if (event.target === storyModal) {
            storyModal.style.display = 'none';
        }
        if (event.target === prayerModal) {
            prayerModal.style.display = 'none';
        }
    });
}

// Prayer button functionality with AJAX
function initPrayerButtons() {
    const prayerButtons = document.querySelectorAll('.prayer-action-btn');

    prayerButtons.forEach(button => {
        // Only handle pray buttons (not comment buttons)
        if (button.querySelector('.fa-hands-praying')) {
            button.addEventListener('click', async function (e) {
                e.preventDefault();
                e.stopPropagation();

                const prayerRequestId = this.getAttribute('data-request-id');
                if (!prayerRequestId) return;

                const isActive = this.classList.contains('active');
                const prayerCountElement = this.querySelector('.prayer-count');

                try {
                    const response = await fetch('api.php?api=pray', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            prayer_request_id: prayerRequestId
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Toggle active state
                        this.classList.toggle('active');

                        // Update count
                        let currentCount = parseInt(prayerCountElement.textContent);
                        if (data.prayed) {
                            prayerCountElement.textContent = currentCount + 1;
                            showNotification('Prayer counted! 🙏', 'success');
                        } else {
                            prayerCountElement.textContent = Math.max(0, currentCount - 1);
                            showNotification('Prayer removed', 'info');
                        }
                    } else {
                        showNotification(data.message || 'Error updating prayer', 'error');
                    }
                } catch (error) {
                    console.error('Prayer error:', error);
                    showNotification('Error updating prayer', 'error');
                }
            });
        }
    });
}

// Testimonial like functionality with AJAX
function initTestimonialLikes() {
    const testimonialButtons = document.querySelectorAll('.testimonial-action-btn');

    testimonialButtons.forEach(button => {
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const testimonialId = this.getAttribute('data-testimonial-id');
            if (!testimonialId) return;

            const isActive = this.classList.contains('active');
            const likeCountElement = this.querySelector('.like-count');

            try {
                const response = await fetch('api.php?api=like-testimonial', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        testimonial_id: testimonialId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Toggle active state
                    this.classList.toggle('active');

                    // Update count
                    let currentCount = parseInt(likeCountElement.textContent);
                    if (data.liked) {
                        likeCountElement.textContent = currentCount + 1;
                        showNotification('Liked! ❤️', 'success');
                    } else {
                        likeCountElement.textContent = Math.max(0, currentCount - 1);
                        showNotification('Like removed', 'info');
                    }
                } else {
                    showNotification(data.message || 'Error updating like', 'error');
                }
            } catch (error) {
                console.error('Like error:', error);
                showNotification('Error updating like', 'error');
            }
        });
    });
}

// Quick actions functionality
function initQuickActions() {
    const quickPrayerBtn = document.getElementById('quick-prayer');
    const quickWorkoutBtn = document.getElementById('quick-workout');
    const quickBibleBtn = document.getElementById('quick-bible');

    if (quickPrayerBtn) {
        quickPrayerBtn.addEventListener('click', function () {
            document.getElementById('prayer-modal').style.display = 'flex';
        });
    }

    if (quickWorkoutBtn) {
        quickWorkoutBtn.addEventListener('click', function () {
            // Implement workout functionality
            showNotification('Starting workout timer...', 'success');
        });
    }

    if (quickBibleBtn) {
        quickBibleBtn.addEventListener('click', function () {
            // Implement Bible reading functionality
            showNotification('Opening daily reading...', 'success');
        });
    }
}

// Mobile dropdown functionality for user menu
function initMobileDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');

    if (userDropdown) {
        // For mobile devices, add click event for better touch support
        userDropdown.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();
                const dropdownContent = this.querySelector('.dropdown-content');
                const isVisible = dropdownContent.style.display === 'block';

                // Close all other dropdowns
                document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });

                // Toggle this dropdown
                dropdownContent.style.display = isVisible ? 'none' : 'block';
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target)) {
                const dropdownContent = userDropdown.querySelector('.dropdown-content');
                if (dropdownContent) {
                    dropdownContent.style.display = 'none';
                }
            }
        });

        // Close dropdown when a link is clicked
        const dropdownLinks = userDropdown.querySelectorAll('.dropdown-content a');
        dropdownLinks.forEach(link => {
            link.addEventListener('click', function () {
                const dropdownContent = userDropdown.querySelector('.dropdown-content');
                if (dropdownContent) {
                    dropdownContent.style.display = 'none';
                }
            });
        });
    }
}

// Enhanced user dropdown hover functionality
function initUserDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');

    if (userDropdown) {
        // Handle hover for desktop
        userDropdown.addEventListener('mouseenter', function () {
            if (window.innerWidth > 768) {
                const dropdownContent = this.querySelector('.dropdown-content');
                if (dropdownContent) {
                    dropdownContent.style.display = 'block';
                }
            }
        });

        userDropdown.addEventListener('mouseleave', function () {
            if (window.innerWidth > 768) {
                const dropdownContent = this.querySelector('.dropdown-content');
                if (dropdownContent) {
                    // Small delay to prevent flickering
                    setTimeout(() => {
                        if (!userDropdown.matches(':hover')) {
                            dropdownContent.style.display = 'none';
                        }
                    }, 100);
                }
            }
        });
    }
}

function showNotification(message, type = 'success') {
    // Remove any existing notifications first
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    });

    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 3000);
}

function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Show message from PHP
function showPHPMessage() {
    // This would be handled by PHP session messages in the actual implementation
    console.log('Message system ready');
}

// Todo functionality
// Simplified Todo functionality
function initTodoSystem() {
    console.log('Initializing todo system...');

    // Add click listeners to all todo items
    document.querySelectorAll('.todo-item').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const task = this.getAttribute('data-task');
            console.log('Todo clicked:', task);

            if (task === 'devotion') {
                // Submit devotion form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api.php';
                form.innerHTML = '<input type="hidden" name="action" value="complete_devotion">';
                document.body.appendChild(form);
                form.submit();
            }
            else if (task === 'weight') {
                // Show weight modal
                const modal = document.getElementById('weight-modal');
                if (modal) modal.style.display = 'flex';
            }
            else if (task === 'steps') {
                // Show steps modal
                const modal = document.getElementById('steps-modal');
                if (modal) modal.style.display = 'flex';
            }
        });
    });

    console.log('Todo system initialized');
}

function handleTodoClick(task, element) {
    switch (task) {
        case 'devotion':
            if (!element.classList.contains('completed')) {
                // Mark devotion as complete
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'api.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'complete_devotion';

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
            break;

        case 'weight':
            // Open weight modal (can update even if already completed)
            document.getElementById('weight-modal').style.display = 'flex';
            setTimeout(() => {
                const weightInput = document.getElementById('weight-value');
                if (weightInput) weightInput.focus();
            }, 100);
            break;

        case 'steps':
            // Open steps modal (can update even if already completed)
            document.getElementById('steps-modal').style.display = 'flex';
            setTimeout(() => {
                const stepsInput = document.getElementById('steps-value');
                if (stepsInput) stepsInput.focus();
            }, 100);
            break;
    }
}

// Update progress when todos are completed (for AJAX updates)
function updateTodoProgress() {
    const completedItems = document.querySelectorAll('.todo-item.completed').length;
    const totalItems = 3;
    const progressPercentage = (completedItems / totalItems) * 100;

    const progressFill = document.querySelector('.progress-fill');
    const progressText = document.querySelector('.progress-text');
    const progressCount = document.querySelector('.progress-count');

    if (progressFill) {
        progressFill.style.width = progressPercentage + '%';
    }
    if (progressText) {
        progressText.textContent = Math.round(progressPercentage) + '% Complete';
    }
    if (progressCount) {
        progressCount.textContent = completedItems + '/3 completed';
    }

    // Celebrate if all tasks are completed
    if (completedItems === totalItems) {
        const streakBadge = document.querySelector('.streak-badge');
        if (streakBadge) {
            streakBadge.classList.add('streak-celebrate');
            setTimeout(() => {
                streakBadge.classList.remove('streak-celebrate');
            }, 500);
        }
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    updateTime();
    setInterval(updateTime, 60000);

    initNavigation();
    initTabs();
    initHabitTracking();
    initModals();
    initPrayerButtons();
    initTestimonialLikes();
    initQuickActions();
    showPHPMessage();

    updateTodoProgress();

    // Initialize dropdown functionality
    initMobileDropdown();
    initUserDropdown();

    initTodoSystem();

    // Add some sample data for demonstration
    setTimeout(() => {
        // Auto-check devotion if not already checked
        const devotionCheckbox = document.querySelector('.habit-checkbox[data-habit="devotion"]');
        if (devotionCheckbox && !devotionCheckbox.classList.contains('checked')) {
            // devotionCheckbox.click(); // Uncomment to auto-complete devotion for demo
        }
    }, 1000);
});

// Handle window resize for responsive behavior
window.addEventListener('resize', function () {
    // Close dropdowns on resize to prevent stuck states
    const dropdownContent = document.querySelector('.dropdown-content');
    if (dropdownContent && window.innerWidth > 768) {
        dropdownContent.style.display = 'none';
    }
});