<?php
header('Content-Type: application/json');
require_once '../config/database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Allow Admin only for now (or logic owner check)
// Assuming anyone logged in admin dir is admin.
// But better check role if session has it.
// session 'role' is usually set.
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
     echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['message_id'])) {
        echo json_encode(['success' => false, 'message' => 'Message ID required']);
        exit;
    }
    
    $messageId = intval($data['message_id']);
    
    $db = new Database();
    $conn = $db->getConnection();
    
    try {
        // Hard delete or soft delete? User said "hapus". 
        // Let's do Soft Delete usually better, or Hard Delete.
        // Let's do Hard Delete to keep it simple and clean as requested "bisa hapus chat gak".
        // Actually soft delete is safer. But let's delete row.
        
        $stmt = $conn->prepare("DELETE FROM chat_messages WHERE id = :id");
        $stmt->execute(['id' => $messageId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Message not found or already deleted']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
