<?php
/**
 * API: Chat Conversations (Admin only)
 * Endpoint untuk mengambil daftar percakapan
 */

header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all conversations with customer info and last message
    $query = "
        SELECT 
            c.id,
            c.customer_id,
            c.status,
            c.created_at,
            c.updated_at,
            u.nama as customer_name,
            u.foto as customer_foto,
            (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT attachment FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_attachment,
            (SELECT attachment_type FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_attachment_type,
            (SELECT created_at FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender_role = 'customer' AND is_read = 0) as unread_count
        FROM chat_conversations c
        JOIN users u ON c.customer_id = u.id
        ORDER BY c.updated_at DESC
    ";
    
    $stmt = $conn->query($query);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total unread count
    $stmt = $conn->query("SELECT COUNT(*) as total FROM chat_messages WHERE sender_role = 'customer' AND is_read = 0");
    $total_unread = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'total_unread' => $total_unread
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
