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

if (!isset($data['kaos_id']) || !isset($data['size']) || !isset($data['qty'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

$kaos_id = $data['kaos_id'];
$size = $data['size'];
$qty = (int)$data['qty'];

// Koneksi database
$db = new Database();
$conn = $db->getConnection();

// Cek ketersediaan stok
$query = "SELECT * FROM kaos WHERE id = :id AND stok >= :qty";
$stmt = $conn->prepare($query);
$stmt->execute(['id' => $kaos_id, 'qty' => $qty]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(400);
    echo json_encode(['error' => 'Stok tidak mencukupi']);
    exit;
}

// Inisialisasi cart jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek apakah produk sudah ada di cart
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['kaos_id'] == $kaos_id && $item['size'] == $size) {
        $item['qty'] += $qty;
        $found = true;
        break;
    }
}

// Jika belum ada, tambahkan ke cart
if (!$found) {
    $_SESSION['cart'][] = [
        'kaos_id' => $kaos_id,
        'size' => $size,
        'qty' => $qty
    ];
}

// Hitung total item di cart
$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['qty'];
}

// Response sukses
echo json_encode([
    'success' => true,
    'message' => 'Produk berhasil ditambahkan ke keranjang',
    'cart_count' => count($_SESSION['cart']),
    'total_items' => $total_items
]);
?>