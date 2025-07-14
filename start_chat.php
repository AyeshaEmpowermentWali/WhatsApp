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

$user_id = $_POST['user_id'] ?? '';
$current_user_id = $_SESSION['user_id'];

if ($user_id && $user_id != $current_user_id) {
    try {
        // Check if chat exists
        $stmt = $pdo->prepare("SELECT id FROM chats WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
        $stmt->execute([$current_user_id, $user_id, $user_id, $current_user_id]);
        $chat = $stmt->fetch();

        if ($chat) {
            echo json_encode(['chat_id' => $chat['id']]);
        } else {
            // Create new chat
            $stmt = $pdo->prepare("INSERT INTO chats (user1_id, user2_id) VALUES (?, ?)");
            $stmt->execute([$current_user_id, $user_id]);
            $chat_id = $pdo->lastInsertId();
            echo json_encode(['chat_id' => $chat_id]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user ID']);
}
?>
