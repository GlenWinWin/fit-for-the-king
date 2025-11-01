<?php
require_once 'config.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$faithFit = new FaithFitFunctions();
$user_id = authenticateUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    
    switch($action) {
        case 'log_weight':
            $weight = floatval($_POST['weight']);
            $unit = $_POST['unit'] ?? 'kg';
            
            if ($weight > 0) {
                $result = $faithFit->logWeight($user_id, $weight, $unit);
                if ($result) {
                    $_SESSION['message'] = 'Weight logged successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error logging weight';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please enter a valid weight';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'log_steps':
            $steps = intval($_POST['steps']);
            
            if ($steps >= 0) {
                $result = $faithFit->logSteps($user_id, $steps);
                if ($result) {
                    $_SESSION['message'] = 'Steps logged successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error logging steps';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please enter a valid step count';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'create_testimonial':
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $category = $_POST['category'] ?? 'other';
            $image_path = null;
            
            // Handle image upload
            if (isset($_FILES['testimonial_image']) && $_FILES['testimonial_image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadProgressPhoto($_FILES['testimonial_image']);
                if ($upload_result['success']) {
                    $image_path = $upload_result['file_path'];
                } else {
                    $_SESSION['message'] = 'Error uploading image: ' . $upload_result['error'];
                    $_SESSION['message_type'] = 'error';
                    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
                    exit;
                }
            }
            
            if (!empty($title) && !empty($content)) {
                $result = $faithFit->createTestimonial($user_id, $title, $content, $category, $image_path);
                if ($result) {
                    $_SESSION['message'] = 'Testimonial shared successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error sharing testimonial';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please fill in all fields';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'create_prayer_request':
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content']);
            $category = $_POST['category'] ?? 'fitness';
            $is_anonymous = false;
            
            if (!empty($content)) {
                $result = $faithFit->createPrayerRequest($user_id, $content, $category, $is_anonymous, $title);
                if ($result) {
                    $_SESSION['message'] = 'Prayer request shared successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error sharing prayer request';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please enter your prayer request';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'complete_devotion':
            $result = $faithFit->markDevotionComplete($user_id);
            if ($result) {
                $_SESSION['message'] = 'Devotion marked complete!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error marking devotion complete';
                $_SESSION['message_type'] = 'error';
            }
            break;

        case 'assign_workout':
            $workout_plan_id = intval($_POST['workout_plan_id']);
            if ($workout_plan_id > 0) {
                $result = $faithFit->assignWorkoutPlan($user_id, $workout_plan_id);
                if ($result) {
                    $_SESSION['message'] = 'Workout plan assigned successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error assigning workout plan';
                    $_SESSION['message_type'] = 'error';
                }
            }
            break;

        case 'set_workout_schedule':
            $workout_plan_id = intval($_POST['workout_plan_id']);
            $scheduled_days = $_POST['scheduled_days'] ?? '';
            
            if ($workout_plan_id > 0 && !empty($scheduled_days)) {
                $result = $faithFit->setWorkoutSchedule($user_id, $workout_plan_id, $scheduled_days);
                if ($result) {
                    $_SESSION['message'] = 'Workout schedule set successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error setting workout schedule';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please select workout days';
                $_SESSION['message_type'] = 'error';
            }
            break;

        case 'log_workout_set':
            $workout_day_exercise_id = intval($_POST['workout_day_exercise_id']);
            $set_number = intval($_POST['set_number']);
            $weight = floatval($_POST['weight']);
            $reps = intval($_POST['reps']);
            $unit = $_POST['unit'] ?? 'kg';
            
            if ($workout_day_exercise_id > 0 && $set_number > 0 && $reps > 0) {
                $result = $faithFit->logWorkoutSet($user_id, $workout_day_exercise_id, $set_number, $weight, $reps, $unit);
                if ($result) {
                    $_SESSION['message'] = 'Set logged successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error logging set';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Please fill in all fields';
                $_SESSION['message_type'] = 'error';
            }
            break;

        case 'complete_workout':
            $result = $faithFit->completeWorkout($user_id);
            if ($result) {
                $_SESSION['message'] = 'Workout completed! Great job!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error completing workout';
                $_SESSION['message_type'] = 'error';
            }
            break;

        case 'complete_workout_day':
            $workout_day_id = intval($_POST['workout_day_id']);
            if ($workout_day_id > 0) {
                $result = $faithFit->completeWorkoutDay($user_id, $workout_day_id);
                if ($result) {
                    $_SESSION['message'] = 'Workout completed! Great job!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error completing workout';
                    $_SESSION['message_type'] = 'error';
                }
            }
            break;

        case 'advance_workout_day':
            $result = $faithFit->advanceWorkoutDay($user_id);
            if ($result) {
                $_SESSION['message'] = 'Moving to next workout day!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error advancing workout day';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'update_theme':
            $theme = $_POST['theme'] ?? 'dark';
            $result = $faithFit->updateUserTheme($user_id, $theme);
            if ($result) {
                setcookie('theme', $theme, time() + (86400 * 30), "/"); // 30 days
                $_SESSION['message'] = 'Theme updated!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Error updating theme';
                $_SESSION['message_type'] = 'error';
            }
            break;
            
        case 'add_prayer':
            $prayer_request_id = intval($_POST['prayer_request_id']);
            if ($prayer_request_id > 0) {
                $result = $faithFit->addPrayer($user_id, $prayer_request_id);
                if ($result) {
                    $_SESSION['message'] = 'Prayer counted!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'You have already prayed for this request';
                    $_SESSION['message_type'] = 'info';
                }
            }
            break;
            
        case 'like_testimonial':
            $testimonial_id = intval($_POST['testimonial_id']);
            if ($testimonial_id > 0) {
                $result = $faithFit->likeTestimonial($user_id, $testimonial_id);
                if ($result) {
                    $_SESSION['message'] = 'Like updated!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error updating like';
                    $_SESSION['message_type'] = 'error';
                }
            }
            break;
    }
    
    // Redirect back to the page
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// Handle AJAX/API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
    header('Access-Control-Allow-Headers: Content-Type');
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        switch($_GET['api']) {
            case 'dashboard':
                if ($method == 'GET') {
                    $stats = $faithFit->getDashboardStats($user_id);
                    $devotion = $faithFit->getDailyDevotion($user_id);
                    $weight_history = $faithFit->getWeightHistory($user_id, 7);
                    $steps_history = $faithFit->getStepsHistory($user_id, 7);
                    
                    sendSuccess([
                        'stats' => $stats,
                        'devotion' => $devotion,
                        'weight_history' => $weight_history,
                        'steps_history' => $steps_history
                    ]);
                }
                break;

            case 'devotion':
                if ($method == 'GET') {
                    $date = $_GET['date'] ?? null;
                    $devotion = $faithFit->getDailyDevotion($user_id, $date);
                    sendSuccess($devotion);
                } elseif ($method == 'POST') {
                    $date = $input['date'] ?? null;
                    $result = $faithFit->markDevotionComplete($user_id, $date);
                    sendSuccess(['completed' => $result], $result ? 'Devotion marked complete' : 'Error marking devotion complete');
                }
                break;

            case 'weight':
                if ($method == 'GET') {
                    $limit = $_GET['limit'] ?? 30;
                    $history = $faithFit->getWeightHistory($user_id, $limit);
                    sendSuccess($history);
                } elseif ($method == 'POST') {
                    $weight = floatval($input['weight']);
                    $unit = $input['unit'] ?? 'kg';
                    $date = $input['date'] ?? null;
                    
                    if ($weight > 0) {
                        $result = $faithFit->logWeight($user_id, $weight, $unit, $date);
                        sendSuccess(['logged' => $result], $result ? 'Weight logged successfully' : 'Error logging weight');
                    } else {
                        sendError('Please enter a valid weight');
                    }
                }
                break;

            case 'steps':
                if ($method == 'GET') {
                    $limit = $_GET['limit'] ?? 30;
                    $history = $faithFit->getStepsHistory($user_id, $limit);
                    sendSuccess($history);
                } elseif ($method == 'POST') {
                    $steps = intval($input['steps']);
                    $date = $input['date'] ?? null;
                    
                    if ($steps >= 0) {
                        $result = $faithFit->logSteps($user_id, $steps, $date);
                        sendSuccess(['logged' => $result], $result ? 'Steps logged successfully' : 'Error logging steps');
                    } else {
                        sendError('Please enter a valid step count');
                    }
                }
                break;

            case 'prayer-requests':
                if ($method == 'GET') {
                    $limit = $_GET['limit'] ?? 50;
                    $requests = $faithFit->getPrayerRequests($limit);
                    sendSuccess($requests);
                } elseif ($method == 'POST') {
                    $content = trim($input['content']);
                    $category = $input['category'] ?? 'fitness';
                    $is_anonymous = $input['is_anonymous'] ?? false;
                    $title = $input['title'] ?? null;
                    
                    if (!empty($content)) {
                        $result = $faithFit->createPrayerRequest($user_id, $content, $category, $is_anonymous, $title);
                        sendSuccess(['created' => $result], $result ? 'Prayer request created' : 'Error creating prayer request');
                    } else {
                        sendError('Please enter your prayer request');
                    }
                }
                break;

            case 'pray':
                if ($method == 'POST') {
                    $prayer_request_id = intval($input['prayer_request_id']);
                    if ($prayer_request_id > 0) {
                        $result = $faithFit->addPrayer($user_id, $prayer_request_id);
                        if ($result) {
                            sendSuccess(['prayed' => true], 'Prayer counted');
                        } else {
                            // This means user already prayed, so remove the prayer
                            $removeResult = $faithFit->removePrayer($user_id, $prayer_request_id);
                            sendSuccess(['prayed' => false], $removeResult ? 'Prayer removed' : 'Already prayed for this request');
                        }
                    } else {
                        sendError('Invalid prayer request ID');
                    }
                }
                break;

            case 'testimonials':
                if ($method == 'GET') {
                    $limit = $_GET['limit'] ?? 50;
                    $testimonials = $faithFit->getTestimonials($limit);
                    sendSuccess($testimonials);
                } elseif ($method == 'POST') {
                    $title = trim($input['title']);
                    $content = trim($input['content']);
                    $category = $input['category'] ?? 'other';
                    
                    if (!empty($title) && !empty($content)) {
                        $result = $faithFit->createTestimonial($user_id, $title, $content, $category);
                        sendSuccess(['created' => $result], $result ? 'Testimonial shared' : 'Error sharing testimonial');
                    } else {
                        sendError('Please fill in all fields');
                    }
                }
                break;

            case 'like-testimonial':
                if ($method == 'POST') {
                    $testimonial_id = intval($input['testimonial_id']);
                    if ($testimonial_id > 0) {
                        $result = $faithFit->likeTestimonial($user_id, $testimonial_id);
                        sendSuccess(['liked' => $result], $result ? 'Like added' : 'Like removed');
                    } else {
                        sendError('Invalid testimonial ID');
                    }
                }
                break;

            case 'user-profile':
                if ($method == 'GET') {
                    $profile = $faithFit->getUserProfile($user_id);
                    sendSuccess($profile);
                }
                break;

            case 'theme':
                if ($method == 'POST') {
                    $theme = $input['theme'] ?? 'dark';
                    $result = $faithFit->updateUserTheme($user_id, $theme);
                    if ($result) {
                        setcookie('theme', $theme, time() + (86400 * 30), "/");
                        sendSuccess(['updated' => true], 'Theme updated');
                    } else {
                        sendError('Error updating theme');
                    }
                }
                break;

            case 'streaks':
                if ($method == 'GET') {
                    $streaks = $faithFit->getUserStreaks($user_id);
                    sendSuccess($streaks);
                }
                break;

            case 'workouts':
                if ($method == 'GET') {
                    $workouts = $faithFit->getWorkoutPlans();
                    sendSuccess($workouts);
                }
                break;

            case 'active-workout':
                if ($method == 'GET') {
                    $workout = $faithFit->getUserActiveWorkout($user_id);
                    sendSuccess($workout);
                }
                break;

            case 'todays-workout':
                if ($method == 'GET') {
                    $workout = $faithFit->getTodaysWorkout($user_id);
                    sendSuccess($workout);
                }
                break;

            case 'workout-schedule':
                if ($method == 'GET') {
                    $schedule = $faithFit->getUserWorkoutSchedule($user_id);
                    sendSuccess($schedule);
                } elseif ($method == 'POST') {
                    $workout_plan_id = intval($input['workout_plan_id']);
                    $scheduled_days = $input['scheduled_days'] ?? '';
                    
                    if ($workout_plan_id > 0 && !empty($scheduled_days)) {
                        $result = $faithFit->setWorkoutSchedule($user_id, $workout_plan_id, $scheduled_days);
                        sendSuccess(['scheduled' => $result], $result ? 'Workout schedule set' : 'Error setting schedule');
                    } else {
                        sendError('Please select workout days');
                    }
                }
                break;

            case 'workout-history':
                if ($method == 'GET') {
                    $limit = $_GET['limit'] ?? 30;
                    $history = $faithFit->getWorkoutHistory($user_id, $limit);
                    sendSuccess($history);
                }
                break;

            case 'exercise-progress':
                if ($method == 'GET') {
                    $exercise_id = $_GET['exercise_id'] ?? null;
                    $days_back = $_GET['days_back'] ?? 30;
                    if ($exercise_id) {
                        $progress = $faithFit->getExerciseProgress($user_id, $exercise_id, $days_back);
                        sendSuccess($progress);
                    } else {
                        sendError('Exercise ID required');
                    }
                }
                break;

            case 'log-workout-set':
                if ($method == 'POST') {
                    $workout_day_exercise_id = intval($input['workout_day_exercise_id']);
                    $set_number = intval($input['set_number']);
                    $weight = floatval($input['weight']);
                    $reps = intval($input['reps']);
                    $unit = $input['unit'] ?? 'kg';
                    
                    if ($workout_day_exercise_id > 0 && $set_number > 0 && $reps > 0) {
                        $result = $faithFit->logWorkoutSet($user_id, $workout_day_exercise_id, $set_number, $weight, $reps, $unit);
                        sendSuccess(['logged' => $result], $result ? 'Set logged successfully' : 'Error logging set');
                    } else {
                        sendError('Please fill in all fields');
                    }
                }
                break;

            case 'complete-workout-day':
                if ($method == 'POST') {
                    $workout_day_id = intval($input['workout_day_id']);
                    if ($workout_day_id > 0) {
                        $result = $faithFit->completeWorkoutDay($user_id, $workout_day_id);
                        sendSuccess(['completed' => $result], $result ? 'Workout completed' : 'Error completing workout');
                    } else {
                        sendError('Invalid workout day ID');
                    }
                }
                break;

            default:
                sendError('API endpoint not found', 404);
        }
    } catch (Exception $e) {
        sendError('Server error: ' . $e->getMessage(), 500);
    }
    exit;
}
?>