<?php
/**
 * API: Send Chat Message
 * Endpoint untuk mengirim pesan chat
 */

header('Content-Type: application/json');
require_once '../config/session.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data - Support both JSON and FormData
$input_data = json_decode(file_get_contents('php://input'), true);
if ($input_data) {
    $message = trim($input_data['message'] ?? '');
    $conversation_id = $input_data['conversation_id'] ?? null;
} else {
    $message = trim($_POST['message'] ?? '');
    $conversation_id = $_POST['conversation_id'] ?? null;
}

$attachment_path = null;
$attachment_type = null;

// Handle File Upload
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['attachment'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'application/pdf'];
    
    if (in_array($file['type'], $allowed_types)) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('chat_') . '.' . $ext;
        $upload_dir = '../assets/uploads/chat/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $dest_path = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest_path)) {
            $attachment_path = 'assets/uploads/chat/' . $filename;
            
            // Determine type simple
            if (strpos($file['type'], 'image') !== false) {
                $attachment_type = 'image';
            } else if (strpos($file['type'], 'video') !== false) {
                $attachment_type = 'video';
            } else {
                $attachment_type = 'file';
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: Images, MP4, PDF']);
        exit;
    }
}

if (empty($message) && empty($attachment_path)) {
    echo json_encode(['success' => false, 'message' => 'Message or attachment is required']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    // If customer, get or create conversation
    if ($user_role === 'customer') {
        // Check existing open conversation
        $stmt = $conn->prepare("SELECT id FROM chat_conversations WHERE customer_id = :customer_id AND status = 'open'");
        $stmt->execute(['customer_id' => $user_id]);
        $conv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conv) {
            $conversation_id = $conv['id'];
        } else {
            // Create new conversation
            $stmt = $conn->prepare("INSERT INTO chat_conversations (customer_id) VALUES (:customer_id)");
            $stmt->execute(['customer_id' => $user_id]);
            $conversation_id = $conn->lastInsertId();
        }
        
        $sender_role = 'customer';
    } else if ($user_role === 'admin') {
        // Admin must specify conversation_id
        if (!$conversation_id) {
            echo json_encode(['success' => false, 'message' => 'Conversation ID required']);
            exit;
        }
        $sender_role = 'admin';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid user role']);
        exit;
    }
    
    // Insert message
    $stmt = $conn->prepare("INSERT INTO chat_messages (conversation_id, sender_id, sender_role, message, attachment, attachment_type) VALUES (:conv_id, :sender_id, :sender_role, :message, :attachment, :attachment_type)");
    $stmt->execute([
        'conv_id' => $conversation_id,
        'sender_id' => $user_id,
        'sender_role' => $sender_role,
        'message' => $message,
        'attachment' => $attachment_path,
        'attachment_type' => $attachment_type
    ]);
    
    // Update conversation timestamp
    $stmt = $conn->prepare("UPDATE chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute(['id' => $conversation_id]);
    
    $message_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'conversation_id' => $conversation_id,
        'attachment' => $attachment_path,
        'attachment_type' => $attachment_type
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
