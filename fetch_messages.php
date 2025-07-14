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

$chat_id = $_GET['chat_id'] ?? '';
$user_id = $_SESSION['user_id'];

if ($chat_id) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.chat_id = ? ORDER BY m.sent_at ASC");
        $stmt->execute([$chat_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE chat_id = ? AND sender_id != ? AND is_read = 0");
        $stmt->execute([$chat_id, $user_id]);

        header('Content-Type: application/json');
        echo json_encode($messages);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing chat_id']);
}
?>
