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

$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("
        SELECT c.id AS chat_id, u.id AS user_id, u.username, u.profile_picture,
               (SELECT message FROM messages WHERE chat_id = c.id ORDER BY sent_at DESC LIMIT 1) AS last_message
        FROM chats c
        JOIN users u ON (u.id = c.user1_id OR u.id = c.user2_id)
        WHERE (c.user1_id = ? OR c.user2_id = ?) AND u.id != ?
        ORDER BY (SELECT MAX(sent_at) FROM messages WHERE chat_id = c.id) DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($chats);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
