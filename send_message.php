<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}
require_once 'db.php';

$chat_id = $_POST['chat_id'] ?? '';
$message = $_POST['message'] ?? '';
$sender_id = $_SESSION['user_id'];

if ($chat_id && $message && $sender_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO messages (chat_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$chat_id, $sender_id, $message]);
        http_response_code(200);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
}
?>
