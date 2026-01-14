<?php
// api/add_to_cart.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $customer_id = $_SESSION['user_id'];
    $kaos_id = isset($_POST['kaos_id']) ? intval($_POST['kaos_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    
    if ($kaos_id <= 0 || $qty <= 0) {
        throw new Exception("Data tidak valid");
    }
    
    // Check stock
    $stmt = $conn->prepare("SELECT stok FROM kaos_varian WHERE id = :id");
    $stmt->execute([':id' => $kaos_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product || $product['stok'] < $qty) {
        throw new Exception("Stok tidak mencukupi");
    }
    
    // Check if item already exists in cart
    $stmt = $conn->prepare("SELECT id, qty FROM cart WHERE customer_id = :uid AND kaos_id = :kid");
    $stmt->execute([':uid' => $customer_id, ':kid' => $kaos_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update quantity
        $new_qty = $existing['qty'] + $qty;
        
        // Ensure new qty doesn't exceed stock
        if ($new_qty > $product['stok']) {
            throw new Exception("Total jumlah melebihi stok yang tersedia");
        }
        
        $update = $conn->prepare("UPDATE cart SET qty = :qty, created_at = NOW() WHERE id = :id");
        $update->execute([':qty' => $new_qty, ':id' => $existing['id']]);
    } else {
        // Insert new item
        $insert = $conn->prepare("INSERT INTO cart (customer_id, kaos_id, qty) VALUES (:uid, :kid, :qty)");
        $insert->execute([':uid' => $customer_id, ':kid' => $kaos_id, ':qty' => $qty]);
    }
    
    // Get updated cart count
    $cart_count = get_cart_count($conn, $customer_id);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Berhasil ditambahkan ke keranjang',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>