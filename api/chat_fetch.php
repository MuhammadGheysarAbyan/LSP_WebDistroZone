<?php
/**
 * API: Fetch Chat Messages
 * Endpoint untuk mengambil pesan chat (polling)
 */

header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
    
    if ($user_role === 'customer') {
        // Get customer's active conversation
        $stmt = $conn->prepare("SELECT id FROM chat_conversations WHERE customer_id = :customer_id AND status = 'open'");
        $stmt->execute(['customer_id' => $user_id]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$conv) {
            echo json_encode(['success' => true, 'messages' => [], 'conversation_id' => null]);
            exit;
        }
        
        $conversation_id = $conv['id'];
        
        // Mark messages as read for customer
        $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :conv_id AND sender_role = 'admin' AND is_read = 0");
        $stmt->execute(['conv_id' => $conversation_id]);
        
    } else if ($user_role === 'admin') {
        if (!$conversation_id) {
            echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
            exit;
        }
        
        // Mark messages as read for admin
        $stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :conv_id AND sender_role = 'customer' AND is_read = 0");
        $stmt->execute(['conv_id' => $conversation_id]);
    }
    
    // Fetch messages
    $stmt = $conn->prepare("
        SELECT m.*, u.nama as sender_name, u.foto as sender_foto
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = :conv_id AND m.id > :last_id
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([
        'conv_id' => $conversation_id,
        'last_id' => $last_id
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unread count for badge
    $unread_count = 0;
    if ($user_role === 'customer') {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM chat_messages WHERE conversation_id = :conv_id AND sender_role = 'admin' AND is_read = 0");
        $stmt->execute(['conv_id' => $conversation_id]);
        $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'conversation_id' => $conversation_id,
        'unread_count' => $unread_count
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
