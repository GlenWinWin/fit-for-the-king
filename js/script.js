// Global utility functions
function getCurrentPage() {
    const path = window.location.pathname;
    return path.split('/').pop() || 'index.php';
}

function setActiveNavItem() {
    const currentPage = getCurrentPage();
    const navItems = document.querySelectorAll('.nav-item');

    navItems.forEach(item => {
        if (item && item.href) {
            const itemPage = item.href.split('/').pop();
            if (itemPage === currentPage) {
                safeAddClass(item, 'active');
            } else {
                safeRemoveClass(item, 'active');
            }
        }
    });
}

// Safe element query with null check
function safeQuerySelector(selector) {
    const element = document.querySelector(selector);
    return element || null;
}

// Safe class list operations
function safeAddClass(element, className) {
    if (element && element.classList) {
        element.classList.add(className);
    }
}

function safeRemoveClass(element, className) {
    if (element && element.classList) {
        element.classList.remove(className);
    }
}

// Safe event listener attachment
function safeAddEventListener(element, event, handler) {
    if (element && typeof handler === 'function') {
        element.addEventListener(event, handler);
    }
}

// Utility function to show loading state
function showLoading(element) {
    if (element && element.classList) {
        element.classList.add('loading');
        element.disabled = true;
    }
}

// Utility function to hide loading state
function hideLoading(element) {
    if (element && element.classList) {
        element.classList.remove('loading');
        element.disabled = false;
    }
}

// Main initialization
document.addEventListener('DOMContentLoaded', function () {
    console.log('FaithFit App Initialized');

    // Set active navigation item
    setActiveNavItem();

    // Theme toggle functionality
    const themeToggle = safeQuerySelector('#theme-toggle');
    if (themeToggle) {
        safeAddEventListener(themeToggle, 'click', function () {
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
            }).catch(error => {
                console.error('Error updating theme:', error);
            });
        });
    }

    // User dropdown functionality
    const userDropdowns = document.querySelectorAll('.user-dropdown');
    userDropdowns.forEach(dropdown => {
        safeAddEventListener(dropdown, 'click', function (e) {
            e.stopPropagation();
            const content = this.querySelector('.dropdown-content');
            if (content) {
                content.style.display = content.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    // Close dropdowns when clicking outside
    safeAddEventListener(document, 'click', function () {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        });
    });

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close');

    // Close modals
    closeButtons.forEach(btn => {
        safeAddEventListener(btn, 'click', function () {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside
    safeAddEventListener(window, 'click', function (event) {
        modals.forEach(modal => {
            if (modal && event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Form submission handling with error prevention
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        if (form && !form.classList.contains('no-ajax')) {
            safeAddEventListener(form, 'submit', function (e) {
                // Only prevent default for forms that should use AJAX
                if (this.getAttribute('data-ajax') !== 'false') {
                    e.preventDefault();

                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        showLoading(submitButton);
                    }

                    const formData = new FormData(this);
                    const action = this.getAttribute('action') || 'api.php';

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
                            // Handle success
                            window.location.reload();
                        })
                        .catch(error => {
                            console.error('Form submission error:', error);
                            if (submitButton) {
                                hideLoading(submitButton);
                            }
                            // Fallback to normal form submission
                            this.submit();
                        });
                }
            });
        }
    });

    // Navigation click handling - Fixed version
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        safeAddEventListener(item, 'click', function (e) {
            // Only handle if it's not an external link and has href
            if (this.href && !this.target && this.href.startsWith(window.location.origin)) {
                e.preventDefault();

                // Update active state safely
                navItems.forEach(nav => {
                    safeRemoveClass(nav, 'active');
                });

                safeAddClass(this, 'active');

                // Navigate to the page
                window.location.href = this.href;
            }
        });
    });

    // Floating action buttons
    const floatingButtons = document.querySelectorAll('.floating-action-btn');
    floatingButtons.forEach(btn => {
        safeAddEventListener(btn, 'click', function () {
            const targetModal = this.getAttribute('data-modal');
            if (targetModal) {
                const modal = document.getElementById(targetModal);
                if (modal) {
                    modal.style.display = 'flex';
                }
            }
        });
    });

    // Auto-hide messages after 5 seconds
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        if (message) {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.parentNode.removeChild(message);
                    }
                }, 300);
            }, 5000);
        }
    });

    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        safeAddEventListener(button, 'click', function () {
            if (this.type === 'submit' || this.getAttribute('type') === 'submit') {
                showLoading(this);

                // Re-enable after 5 seconds in case of error
                setTimeout(() => {
                    hideLoading(this);
                }, 5000);
            }
        });
    });

    // Task click handlers for dashboard
    const todoItems = document.querySelectorAll('.todo-item');
    todoItems.forEach(item => {
        safeAddEventListener(item, 'click', function () {
            const taskType = this.getAttribute('data-task');

            switch (taskType) {
                case 'devotion':
                    // Scroll to devotion section
                    const devotionCard = safeQuerySelector('.devotion-card');
                    if (devotionCard) {
                        devotionCard.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                    break;
                case 'weight':
                    const weightModal = safeQuerySelector('#weight-modal');
                    if (weightModal) {
                        weightModal.style.display = 'flex';
                    }
                    break;
                case 'steps':
                    const stepsModal = safeQuerySelector('#steps-modal');
                    if (stepsModal) {
                        stepsModal.style.display = 'flex';
                    }
                    break;
                case 'workout':
                    // Navigate to workout tracker
                    window.location.href = 'workout-tracker.php';
                    break;
            }
        });
    });

    // Complete devotion button
    const completeDevotionBtn = safeQuerySelector('#complete-devotion');
    safeAddEventListener(completeDevotionBtn, 'click', function () {
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

    // Workout form submission handling
    const workoutForms = document.querySelectorAll('.workout-complete-form, .workout-advance-form');
    workoutForms.forEach(form => {
        safeAddEventListener(form, 'submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to normal submission
                    this.submit();
                });
        });
    });

    // Auto-save for workout sets
    const setForms = document.querySelectorAll('.sets-form');
    setForms.forEach(form => {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            safeAddEventListener(input, 'change', function () {
                // Add a small delay to avoid too many requests
                clearTimeout(window.saveTimeout);
                window.saveTimeout = setTimeout(() => {
                    if (form.requestSubmit) {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }, 500);
            });
        });

        // Handle set form submission
        safeAddEventListener(form, 'submit', function (e) {
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
                    if (submitBtn) {
                        const originalHtml = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                        submitBtn.style.background = '#4CAF50';

                        setTimeout(() => {
                            submitBtn.innerHTML = originalHtml;
                            submitBtn.style.background = '';
                        }, 1500);
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });

    // Update time display
    function updateTime() {
        const now = new Date();
        const timeElement = safeQuerySelector('#current-time');
        const dateElement = safeQuerySelector('#current-date');

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

    // Prayer request modal
    const sharePrayerBtn = safeQuerySelector('#share-prayer-btn');
    const prayerModal = safeQuerySelector('#prayer-modal');

    safeAddEventListener(sharePrayerBtn, 'click', function () {
        if (prayerModal) {
            prayerModal.style.display = 'flex';
        }
    });

    // Testimonial modal
    const shareTestimonialBtn = safeQuerySelector('#share-testimonial-btn');
    const testimonialModal = safeQuerySelector('#testimonial-modal');

    if (shareTestimonialBtn && testimonialModal) {
        safeAddEventListener(shareTestimonialBtn, 'click', function () {
            testimonialModal.style.display = 'flex';
        });
    }

    // Image preview for testimonial uploads
    const imageInput = safeQuerySelector('#testimonial_image');
    if (imageInput) {
        safeAddEventListener(imageInput, 'change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const preview = safeQuerySelector('#image-preview') || document.createElement('img');
                    preview.id = 'image-preview';
                    preview.src = e.target.result;
                    preview.style.maxWidth = '200px';
                    preview.style.maxHeight = '200px';
                    preview.style.marginTop = '10px';
                    preview.style.borderRadius = '8px';

                    if (!document.getElementById('image-preview')) {
                        imageInput.parentNode.appendChild(preview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Like functionality for testimonials and prayers
    const likeButtons = document.querySelectorAll('.like-btn, .pray-btn');
    likeButtons.forEach(btn => {
        safeAddEventListener(btn, 'click', function () {
            const itemId = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            const isLiked = this.classList.contains('liked');

            if (type === 'testimonial') {
                const formData = new FormData();
                formData.append('action', 'like_testimonial');
                formData.append('testimonial_id', itemId);

                fetch('api.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const likeCount = this.querySelector('.like-count');
                            if (likeCount) {
                                const currentCount = parseInt(likeCount.textContent);
                                likeCount.textContent = isLiked ? currentCount - 1 : currentCount + 1;
                            }
                            this.classList.toggle('liked');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    });

    // Initialize any charts if present
    const charts = document.querySelectorAll('.chart-container');
    if (charts.length > 0) {
        // Initialize charts here if needed
        console.log('Charts found, initialize if needed');
    }

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        safeAddEventListener(link, 'click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetElement = safeQuerySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialize tooltips if any
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        safeAddEventListener(tooltip, 'mouseenter', function () {
            // Tooltip implementation can be added here
        });
    });

    console.log('FaithFit App Initialization Complete');
});

// Export functions for use in other scripts (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getCurrentPage,
        setActiveNavItem,
        safeQuerySelector,
        safeAddClass,
        safeRemoveClass,
        showLoading,
        hideLoading
    };
}