<?php
require_once 'config.php';

class FaithFitFunctions {
    private $conn;
    
    public function __construct() {
        $this->conn = getDatabaseConnection();
    }

    // AUTHENTICATION FUNCTIONS
    public function authenticateUser($email, $password) {
        try {
            $query = "SELECT id, username, email, password_hash, first_name, last_name, avatar_color, theme_preference, daily_step_goal 
                      FROM users WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Remove password hash from returned user data
                unset($user['password_hash']);
                return $user;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function registerUser($first_name, $last_name, $email, $password) {
        try {
            // Check if user already exists
            $checkQuery = "SELECT id FROM users WHERE email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":email", $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Generate username from email
            $username = strtolower($first_name . $last_name) . rand(100, 999);
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                      VALUES (:username, :email, :password_hash, :first_name, :last_name)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password_hash", $password_hash);
            $stmt->bindParam(":first_name", $first_name);
            $stmt->bindParam(":last_name", $last_name);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'User registered successfully'];
            } else {
                return ['success' => false, 'message' => 'Error creating user account'];
            }
        } catch(PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    // USER FUNCTIONS
    public function getUserProfile($user_id) {
        try {
            $query = "SELECT id, username, email, first_name, last_name, avatar_color, theme_preference, daily_step_goal 
                      FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function updateUserTheme($user_id, $theme) {
        try {
            $query = "UPDATE users SET theme_preference = :theme WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":theme", $theme);
            $stmt->bindParam(":user_id", $user_id);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // DEVOTION FUNCTIONS
    public function getDailyDevotion() {
        try {
            // Get a random devotion from the database
            $query = "SELECT * FROM daily_devotions ORDER BY RAND() LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $devotion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no devotion exists, return a default one
            if (!$devotion) {
                $devotion = [
                    'verse_text' => "I can do all things through Christ who strengthens me.",
                    'verse_reference' => "Philippians 4:13",
                    'devotion_text' => "In our fitness journey, we often focus on building physical strength through our own efforts. But true strength comes from surrendering to God's plan and allowing His power to work through us.",
                ];
            }
            
            return $devotion;
        } catch(PDOException $e) {
            error_log("Get devotion error: " . $e->getMessage());
            return false;
        }
    }

    // Update the markDevotionComplete function
    public function markDevotionComplete($user_id, $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        try {
            // Just update the daily todo - no longer tracking completion in daily_devotions table
            $this->updateDailyTodo($user_id, 'devotion_completed', true, $date);
            
            return true;
        } catch(PDOException $e) {
            error_log("Devotion complete error: " . $e->getMessage());
            return false;
        }
    }

    // Update the logWeight function
    public function logWeight($user_id, $weight, $unit = 'kg', $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        try {
            $query = "INSERT INTO weight_entries (user_id, weight_value, weight_unit, entry_date) 
                    VALUES (:user_id, :weight_value, :weight_unit, :entry_date)
                    ON DUPLICATE KEY UPDATE weight_value = :weight_value, weight_unit = :weight_unit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":weight_value", $weight);
            $stmt->bindParam(":weight_unit", $unit);
            $stmt->bindParam(":entry_date", $date);
            $result = $stmt->execute();
            
            if ($result) {
                // Update daily todo
                $this->updateDailyTodo($user_id, 'weight_logged', true, $date);
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Log weight error: " . $e->getMessage());
            return false;
        }
    }

    // Update the logSteps function
    public function logSteps($user_id, $steps, $date = null) {
        if (!$date) $date = date('Y-m-d');
        
        try {
            $query = "INSERT INTO step_entries (user_id, steps_count, entry_date) 
                    VALUES (:user_id, :steps_count, :entry_date)
                    ON DUPLICATE KEY UPDATE steps_count = :steps_count";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":steps_count", $steps);
            $stmt->bindParam(":entry_date", $date);
            $result = $stmt->execute();
            
            if ($result) {
                // Update daily todo
                $this->updateDailyTodo($user_id, 'steps_logged', true, $date);
                
                // Update streak if goal is met
                $user = $this->getUserProfile($user_id);
                if ($steps >= $user['daily_step_goal']) {
                    $this->updateStreak($user_id, 'steps');
                }
            }
            
            return $result;
        } catch(PDOException $e) {
            error_log("Log steps error: " . $e->getMessage());
            return false;
        }
    }

    public function getWeightHistory($user_id, $limit = 30) {
        try {
            $query = "SELECT * FROM weight_entries 
                      WHERE user_id = :user_id 
                      ORDER BY entry_date DESC 
                      LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // STEPS TRACKING FUNCTIONS

    public function getStepsHistory($user_id, $limit = 30) {
        try {
            $query = "SELECT * FROM step_entries 
                      WHERE user_id = :user_id 
                      ORDER BY entry_date DESC 
                      LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    // PRAYER REQUEST FUNCTIONS
    public function getPrayerRequests($limit = 50) {
        try {
            $query = "SELECT pr.*, u.first_name, u.last_name,
                            (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_request_id = pr.id) as prayer_count,
                            (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_request_id = pr.id AND user_id = :current_user_id) as user_prayed
                    FROM prayer_requests pr 
                    JOIN users u ON pr.user_id = u.id 
                    WHERE pr.is_anonymous = FALSE 
                    ORDER BY pr.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_user_id", $_SESSION['user_id']);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Prayer requests error: " . $e->getMessage());
            return false;
        }
    }

    public function createPrayerRequest($user_id, $content, $category = 'fitness', $is_anonymous = false, $title = null) {
        try {
            $query = "INSERT INTO prayer_requests (user_id, title, content, category, is_anonymous) 
                      VALUES (:user_id, :title, :content, :category, :is_anonymous)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":is_anonymous", $is_anonymous, PDO::PARAM_BOOL);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function addPrayer($user_id, $prayer_request_id) {
        try {
            // Check if user already prayed for this request
            $checkQuery = "SELECT id FROM prayer_interactions 
                          WHERE user_id = :user_id AND prayer_request_id = :prayer_request_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":user_id", $user_id);
            $checkStmt->bindParam(":prayer_request_id", $prayer_request_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return false; // Already prayed
            }
            
            // Add prayer interaction
            $interactionQuery = "INSERT INTO prayer_interactions (user_id, prayer_request_id) 
                               VALUES (:user_id, :prayer_request_id)";
            $interactionStmt = $this->conn->prepare($interactionQuery);
            $interactionStmt->bindParam(":user_id", $user_id);
            $interactionStmt->bindParam(":prayer_request_id", $prayer_request_id);
            $interactionStmt->execute();
            
            // Update prayer count
            $updateQuery = "UPDATE prayer_requests 
                           SET prayer_count = prayer_count + 1 
                           WHERE id = :prayer_request_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":prayer_request_id", $prayer_request_id);
            return $updateStmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // TESTIMONIAL FUNCTIONS
    public function getTestimonials($limit = 50) {
        try {
            $query = "SELECT t.*, u.first_name, u.last_name,
                            (SELECT COUNT(*) FROM testimonial_likes WHERE testimonial_id = t.id) as like_count,
                            (SELECT COUNT(*) FROM testimonial_likes WHERE testimonial_id = t.id AND user_id = :current_user_id) as user_liked
                    FROM testimonials t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.is_approved = TRUE 
                    ORDER BY t.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":current_user_id", $_SESSION['user_id']);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Testimonials error: " . $e->getMessage());
            return false;
        }
    }

    public function createTestimonial($user_id, $title, $content, $category = 'other', $image_path = null) {
        try {
            $query = "INSERT INTO testimonials (user_id, title, content, category, image_path) 
                    VALUES (:user_id, :title, :content, :category, :image_path)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":image_path", $image_path);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function likeTestimonial($user_id, $testimonial_id) {
        try {
            // Check if user already liked this testimonial
            $checkQuery = "SELECT id FROM testimonial_likes 
                          WHERE user_id = :user_id AND testimonial_id = :testimonial_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":user_id", $user_id);
            $checkStmt->bindParam(":testimonial_id", $testimonial_id);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                // Unlike
                $deleteQuery = "DELETE FROM testimonial_likes 
                               WHERE user_id = :user_id AND testimonial_id = :testimonial_id";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->bindParam(":user_id", $user_id);
                $deleteStmt->bindParam(":testimonial_id", $testimonial_id);
                $deleteStmt->execute();
                
                // Decrement like count
                $updateQuery = "UPDATE testimonials 
                               SET like_count = GREATEST(0, like_count - 1) 
                               WHERE id = :testimonial_id";
            } else {
                // Like
                $insertQuery = "INSERT INTO testimonial_likes (user_id, testimonial_id) 
                               VALUES (:user_id, :testimonial_id)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bindParam(":user_id", $user_id);
                $insertStmt->bindParam(":testimonial_id", $testimonial_id);
                $insertStmt->execute();
                
                // Increment like count
                $updateQuery = "UPDATE testimonials 
                               SET like_count = like_count + 1 
                               WHERE id = :testimonial_id";
            }
            
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":testimonial_id", $testimonial_id);
            return $updateStmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    // STREAK FUNCTIONS
    private function updateStreak($user_id, $streak_type) {
        try {
            $today = date('Y-m-d');
            
            $query = "INSERT INTO user_streaks (user_id, streak_type, current_streak, longest_streak, last_activity_date) 
                      VALUES (:user_id, :streak_type, 1, 1, :today)
                      ON DUPLICATE KEY UPDATE 
                      current_streak = IF(DATEDIFF(:today, last_activity_date) = 1, current_streak + 1, 1),
                      longest_streak = IF(DATEDIFF(:today, last_activity_date) = 1, 
                                         GREATEST(longest_streak, current_streak + 1), 
                                         GREATEST(longest_streak, 1)),
                      last_activity_date = :today";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":streak_type", $streak_type);
            $stmt->bindParam(":today", $today);
            return $stmt->execute();
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getUserStreaks($user_id) {
        try {
            $query = "SELECT streak_type, current_streak, longest_streak 
                      FROM user_streaks 
                      WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    public function getDashboardStats($user_id) {
        $stats = [];
        $today = date('Y-m-d');
        
        // Get devotion streak
        $devotionStreak = $this->getUserStreaks($user_id);
        $stats['devotion_streak'] = 0;
        foreach ($devotionStreak as $streak) {
            if ($streak['streak_type'] == 'devotion') {
                $stats['devotion_streak'] = $streak['current_streak'];
            }
        }
        
        // Get today's steps
        try {
            $stepsQuery = "SELECT steps_count FROM step_entries WHERE user_id = :user_id AND entry_date = :today";
            $stepsStmt = $this->conn->prepare($stepsQuery);
            $stepsStmt->bindParam(":user_id", $user_id);
            $stepsStmt->bindParam(":today", $today);
            $stepsStmt->execute();
            $todaySteps = $stepsStmt->fetch(PDO::FETCH_ASSOC);
            $stats['today_steps'] = $todaySteps ? $todaySteps['steps_count'] : 0;
        } catch(PDOException $e) {
            $stats['today_steps'] = 0;
        }
        
        // Get today's weight
        try {
            $weightQuery = "SELECT weight_value, weight_unit FROM weight_entries WHERE user_id = :user_id AND entry_date = :today";
            $weightStmt = $this->conn->prepare($weightQuery);
            $weightStmt->bindParam(":user_id", $user_id);
            $weightStmt->bindParam(":today", $today);
            $weightStmt->execute();
            $stats['current_weight'] = $weightStmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $stats['current_weight'] = null;
        }
        
        // Get user's step goal
        $user = $this->getUserProfile($user_id);
        $stats['step_goal'] = $user['daily_step_goal'] ?? 8000;
        
        return $stats;
    }

    // DAILY TODO FUNCTIONS
    public function getDailyTodos($user_id, $date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            $query = "SELECT * FROM daily_todos WHERE user_id = :user_id AND todo_date = :todo_date";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":todo_date", $date);
            $stmt->execute();

            $todos = $stmt->fetch(PDO::FETCH_ASSOC);

            // If no todos for TODAY, create fresh ones
            if (!$todos) {
                // Create new todo record for today
                $insertQuery = "INSERT INTO daily_todos (user_id, todo_date, devotion_completed, weight_logged, steps_logged) 
                               VALUES (:user_id, :todo_date, false, false, false)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->bindParam(":user_id", $user_id);
                $insertStmt->bindParam(":todo_date", $date);

                if ($insertStmt->execute()) {
                    // Return the fresh structure for today
                    return [
                        'devotion_completed' => false,
                        'weight_logged' => false,
                        'steps_logged' => false,
                        'todo_date' => $date
                    ];
                }
            }

            // Return whatever we found (could be today's or an old date)
            return $todos;

        } catch(PDOException $e) {
            error_log("Todo error: " . $e->getMessage());
            // Return default structure on error
            return [
                'devotion_completed' => false,
                'weight_logged' => false,
                'steps_logged' => false,
                'todo_date' => $date
            ];
        }
    }

    public function updateDailyTodo($user_id, $field, $completed = true, $date = null) {
        if (!$date) $date = date('Y-m-d');

        try {
            // First ensure a todo record exists for today
            $this->getDailyTodos($user_id, $date);

            // Now update the specific field for today's date
            $query = "UPDATE daily_todos SET $field = :completed, updated_at = NOW() 
                     WHERE user_id = :user_id AND todo_date = :todo_date";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":completed", $completed, PDO::PARAM_BOOL);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":todo_date", $date);
            $result = $stmt->execute();

            // Update streak if all todos are completed FOR TODAY
            if ($result) {
                $this->checkAndUpdateStreak($user_id, $date);
            }

            return $result;
        } catch(PDOException $e) {
            error_log("Update todo error: " . $e->getMessage());
            return false;
        }
    }

    private function checkAndUpdateStreak($user_id, $date) {
        try {
            $query = "SELECT devotion_completed, weight_logged, steps_logged 
                    FROM daily_todos 
                    WHERE user_id = :user_id AND todo_date = :todo_date";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":todo_date", $date);
            $stmt->execute();
            
            $todos = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($todos && $todos['devotion_completed'] && $todos['weight_logged'] && $todos['steps_logged']) {
                // All todos completed - update streak
                $this->updateStreak($user_id, 'daily_tasks');
            }
            
            return true;
        } catch(PDOException $e) {
            error_log("Check streak error: " . $e->getMessage());
            return false;
        }
    }

    public function removePrayer($user_id, $prayer_request_id) {
        try {
            // Remove prayer interaction
            $deleteQuery = "DELETE FROM prayer_interactions 
                        WHERE user_id = :user_id AND prayer_request_id = :prayer_request_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(":user_id", $user_id);
            $deleteStmt->bindParam(":prayer_request_id", $prayer_request_id);
            $deleteStmt->execute();
            
            // Update prayer count
            $updateQuery = "UPDATE prayer_requests 
                        SET prayer_count = GREATEST(0, prayer_count - 1) 
                        WHERE id = :prayer_request_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(":prayer_request_id", $prayer_request_id);
            return $updateStmt->execute();
        } catch(PDOException $e) {
            error_log("Remove prayer error: " . $e->getMessage());
            return false;
        }
    }

    // ADMIN FUNCTIONS
    public function authenticateAdmin($email, $password) {
        try {
            $query = "SELECT id, email, password_hash, first_name, last_name, role, is_active 
                    FROM admin_users WHERE email = :email AND is_active = TRUE";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Remove password hash from returned admin data
                unset($admin['password_hash']);
                return $admin;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Admin authentication error: " . $e->getMessage());
            return false;
        }
    }

    public function registerAdmin($first_name, $last_name, $email, $password) {
        try {
            // Check if admin already exists
            $checkQuery = "SELECT id FROM admin_users WHERE email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":email", $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Admin email already registered'];
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO admin_users (email, password_hash, first_name, last_name, role) 
                    VALUES (:email, :password_hash, :first_name, :last_name, 'admin')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password_hash", $password_hash);
            $stmt->bindParam(":first_name", $first_name);
            $stmt->bindParam(":last_name", $last_name);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Admin registered successfully'];
            } else {
                return ['success' => false, 'message' => 'Error creating admin account'];
            }
        } catch(PDOException $e) {
            error_log("Admin registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getAdminStats() {
        try {
            $stats = [];
            
            // Total users
            $userQuery = "SELECT COUNT(*) as total FROM users";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->execute();
            $stats['total_users'] = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total testimonials
            $testimonialQuery = "SELECT COUNT(*) as total FROM testimonials";
            $testimonialStmt = $this->conn->prepare($testimonialQuery);
            $testimonialStmt->execute();
            $stats['total_testimonials'] = $testimonialStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total prayer requests
            $prayerQuery = "SELECT COUNT(*) as total FROM prayer_requests";
            $prayerStmt = $this->conn->prepare($prayerQuery);
            $prayerStmt->execute();
            $stats['total_prayers'] = $prayerStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total steps
            $stepsQuery = "SELECT SUM(steps_count) as total FROM step_entries";
            $stepsStmt = $this->conn->prepare($stepsQuery);
            $stepsStmt->execute();
            $stats['total_steps'] = $stepsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            return $stats;
        } catch(PDOException $e) {
            error_log("Admin stats error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'total_testimonials' => 0,
                'total_prayers' => 0,
                'total_steps' => 0
            ];
        }
    }

    public function getRecentUsers($limit = 5) {
        try {
            $query = "SELECT id, first_name, last_name, email, created_at 
                    FROM users 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Recent users error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentTestimonials($limit = 5) {
        try {
            $query = "SELECT t.*, u.first_name, u.last_name 
                    FROM testimonials t 
                    JOIN users u ON t.user_id = u.id 
                    ORDER BY t.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Recent testimonials error: " . $e->getMessage());
            return [];
        }
    }

    public function getRecentPrayerRequests($limit = 5) {
        try {
            $query = "SELECT pr.*, u.first_name, u.last_name 
                    FROM prayer_requests pr 
                    JOIN users u ON pr.user_id = u.id 
                    ORDER BY pr.created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Recent prayers error: " . $e->getMessage());
            return [];
        }
    }

    // WORKOUT FUNCTIONS
    public function createWorkoutPlan($name, $description, $duration_weeks, $difficulty, $created_by) {
        try {
            $query = "INSERT INTO workout_plans (name, description, duration_weeks, difficulty, created_by) 
                    VALUES (:name, :description, :duration_weeks, :difficulty, :created_by)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":duration_weeks", $duration_weeks, PDO::PARAM_INT);
            $stmt->bindParam(":difficulty", $difficulty);
            $stmt->bindParam(":created_by", $created_by, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'plan_id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Error creating workout plan'];
            }
        } catch(PDOException $e) {
            error_log("Create workout plan error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function addWorkoutDay($workout_plan_id, $day_number, $name, $description = '') {
        try {
            $query = "INSERT INTO workout_days (workout_plan_id, day_number, name, description) 
                    VALUES (:workout_plan_id, :day_number, :name, :description)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":workout_plan_id", $workout_plan_id, PDO::PARAM_INT);
            $stmt->bindParam(":day_number", $day_number, PDO::PARAM_INT);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            
            if ($stmt->execute()) {
                return ['success' => true, 'day_id' => $this->conn->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Error adding workout day'];
            }
        } catch(PDOException $e) {
            error_log("Add workout day error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function addExerciseToDay($workout_day_id, $exercise_id, $exercise_order, $sets, $reps, $rest_seconds = 60, $notes = '') {
        try {
            $query = "INSERT INTO workout_day_exercises (workout_day_id, exercise_id, exercise_order, sets, reps, rest_seconds, notes) 
                    VALUES (:workout_day_id, :exercise_id, :exercise_order, :sets, :reps, :rest_seconds, :notes)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":workout_day_id", $workout_day_id, PDO::PARAM_INT);
            $stmt->bindParam(":exercise_id", $exercise_id, PDO::PARAM_INT);
            $stmt->bindParam(":exercise_order", $exercise_order, PDO::PARAM_INT);
            $stmt->bindParam(":sets", $sets, PDO::PARAM_INT);
            $stmt->bindParam(":reps", $reps);
            $stmt->bindParam(":rest_seconds", $rest_seconds, PDO::PARAM_INT);
            $stmt->bindParam(":notes", $notes);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Add exercise to day error: " . $e->getMessage());
            return false;
        }
    }

    public function getExercises($muscle_group = null) {
        try {
            $query = "SELECT * FROM exercises";
            if ($muscle_group) {
                $query .= " WHERE muscle_group = :muscle_group";
            }
            $query .= " ORDER BY name ASC";
            
            $stmt = $this->conn->prepare($query);
            if ($muscle_group) {
                $stmt->bindParam(":muscle_group", $muscle_group);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get exercises error: " . $e->getMessage());
            return [];
        }
    }

    public function getWorkoutPlans() {
        try {
            $query = "SELECT wp.*, CONCAT(au.first_name, ' ', au.last_name) as created_by_name 
                    FROM workout_plans wp 
                    LEFT JOIN admin_users au ON wp.created_by = au.id 
                    ORDER BY wp.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get workout plans error: " . $e->getMessage());
            return [];
        }
    }

    public function getWorkoutPlanDetails($plan_id) {
        try {
            // Get plan basic info
            $planQuery = "SELECT * FROM workout_plans WHERE id = :plan_id";
            $planStmt = $this->conn->prepare($planQuery);
            $planStmt->bindParam(":plan_id", $plan_id, PDO::PARAM_INT);
            $planStmt->execute();
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$plan) return null;
            
            // Get workout days with exercises
            $daysQuery = "SELECT wd.*, 
                                GROUP_CONCAT(CONCAT(e.name, '|', wde.sets, '|', wde.reps, '|', wde.rest_seconds) ORDER BY wde.exercise_order) as exercises
                        FROM workout_days wd 
                        LEFT JOIN workout_day_exercises wde ON wd.id = wde.workout_day_id 
                        LEFT JOIN exercises e ON wde.exercise_id = e.id 
                        WHERE wd.workout_plan_id = :plan_id 
                        GROUP BY wd.id 
                        ORDER BY wd.day_number";
            $daysStmt = $this->conn->prepare($daysQuery);
            $daysStmt->bindParam(":plan_id", $plan_id, PDO::PARAM_INT);
            $daysStmt->execute();
            $plan['days'] = $daysStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $plan;
        } catch(PDOException $e) {
            error_log("Get workout plan details error: " . $e->getMessage());
            return null;
        }
    }

    public function createExercise($name, $description, $muscle_group, $equipment, $video_url = null) {
        try {
            $query = "INSERT INTO exercises (name, description, muscle_group, equipment, demonstration_video_url) 
                    VALUES (:name, :description, :muscle_group, :equipment, :video_url)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":muscle_group", $muscle_group);
            $stmt->bindParam(":equipment", $equipment);
            $stmt->bindParam(":video_url", $video_url);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Create exercise error: " . $e->getMessage());
            return false;
        }
    }

    // WORKOUT DAY MANAGEMENT
    public function getWorkoutDays($plan_id) {
        try {
            $query = "SELECT * FROM workout_days WHERE workout_plan_id = :plan_id ORDER BY day_number";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":plan_id", $plan_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get workout days error: " . $e->getMessage());
            return [];
        }
    }

    public function getWorkoutDayWithExercises($day_id) {
        try {
            $query = "SELECT wde.*, e.name as exercise_name, e.muscle_group, e.equipment, e.demonstration_video_url
                    FROM workout_day_exercises wde
                    JOIN exercises e ON wde.exercise_id = e.id
                    WHERE wde.workout_day_id = :day_id
                    ORDER BY wde.exercise_order";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":day_id", $day_id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get workout day exercises error: " . $e->getMessage());
            return [];
        }
    }

    public function updateExercise($exercise_id, $name, $description, $muscle_group, $equipment, $video_url = null) {
        try {
            $query = "UPDATE exercises SET name = :name, description = :description, 
                    muscle_group = :muscle_group, equipment = :equipment, 
                    demonstration_video_url = :video_url, updated_at = NOW() 
                    WHERE id = :exercise_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":muscle_group", $muscle_group);
            $stmt->bindParam(":equipment", $equipment);
            $stmt->bindParam(":video_url", $video_url);
            $stmt->bindParam(":exercise_id", $exercise_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update exercise error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteExercise($exercise_id) {
        try {
            // Check if exercise is used in any workout plans
            $checkQuery = "SELECT COUNT(*) as usage_count FROM workout_day_exercises WHERE exercise_id = :exercise_id";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(":exercise_id", $exercise_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $usage = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usage['usage_count'] > 0) {
                return ['success' => false, 'message' => 'Cannot delete exercise. It is being used in workout plans.'];
            }
            
            $query = "DELETE FROM exercises WHERE id = :exercise_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":exercise_id", $exercise_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Exercise deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Error deleting exercise'];
            }
        } catch(PDOException $e) {
            error_log("Delete exercise error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
}

// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// File upload helper function
function uploadProgressPhoto($file) {
    $upload_dir = 'uploads/progress-photos/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, GIF, and WebP files are allowed'];
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size must be less than 5MB'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return ['success' => true, 'file_path' => $file_path, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Error uploading file'];
    }
}

// Password validation helper
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}
?>