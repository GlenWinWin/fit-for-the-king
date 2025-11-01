<?php
session_start();

class Database {
    private $host = "localhost";
    private $db_name = "fitfortheking";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return false;
        }
        return $this->conn;
    }
}

function getDatabaseConnection() {
    $database = new Database();
    return $database->getConnection();
}

// Response helper functions
function sendResponse($success, $data = null, $message = "", $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

function sendError($message, $code = 400) {
    sendResponse(false, null, $message, $code);
}

function sendSuccess($data = null, $message = "Success") {
    sendResponse(true, $data, $message);
}

// Authentication helper
function authenticateUser() {
    if (!isset($_SESSION['user_id'])) {
        if (isset($_GET['api'])) {
            sendError('Authentication required', 401);
        } else {
            header('Location: login.php');
            exit;
        }
    }
    return $_SESSION['user_id'];
}

// Check if user is logged in (for redirects)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Logout function
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>