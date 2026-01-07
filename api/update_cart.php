<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Cek apakah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan']);
    exit;
}

// Ambil data dari POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['index'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

$action = $data['action'];
$index = (int)$data['index'];
$qty = isset($data['qty']) ? (int)$data['qty'] : 1;

// Cek apakah cart ada
if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$index])) {
    http_response_code(400);
    echo json_encode(['error' => 'Item tidak ditemukan di keranjang']);
    exit;
}

// Koneksi database untuk cek stok
$db = new Database();
$conn = $db->getConnection();

$item = $_SESSION['cart'][$index];
$kaos_id = $item['kaos_id'];

switch ($action) {
    case 'update':
        // Cek stok tersedia
        $query = "SELECT stok FROM kaos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $kaos_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || $product['stok'] < $qty) {
            http_response_code(400);
            echo json_encode(['error' => 'Stok tidak mencukupi']);
            exit;
        }
        
        $_SESSION['cart'][$index]['qty'] = $qty;
        break;
        
    case 'remove':
        // Hapus item dari cart
        array_splice($_SESSION['cart'], $index, 1);
        break;
        
    case 'clear':
        // Kosongkan seluruh cart
        $_SESSION['cart'] = [];
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Aksi tidak valid']);
        exit;
}

// Hitung ulang total cart
$cart_count = count($_SESSION['cart']);
$total_items = 0;
$cart_total = 0;

if ($cart_count > 0) {
    // Ambil detail produk untuk hitung total
    foreach ($_SESSION['cart'] as $cart_item) {
        $query = "SELECT harga FROM kaos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute(['id' => $cart_item['kaos_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $total_items += $cart_item['qty'];
            $cart_total += $product['harga'] * $cart_item['qty'];
        }
    }
}

// Response sukses
echo json_encode([
    'success' => true,
    'message' => 'Keranjang berhasil diperbarui',
    'cart_count' => $cart_count,
    'total_items' => $total_items,
    'cart_total' => $cart_total
]);
?>