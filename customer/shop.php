<?php
require_once '../config/session.php';
require_once '../config/database.php';

// Helper functions
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Inisialisasi database
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch(Exception $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Get filters dengan sanitasi
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$size = isset($_GET['size']) ? clean_input($_GET['size']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'terbaru';

// Query untuk produk
$query = "SELECT k.*, kat.nama_kategori, MIN(v.harga) as harga, SUM(v.stok) as total_stok
          FROM kaos_master k 
          LEFT JOIN kategori kat ON k.kategori_id = kat.id 
          JOIN kaos_varian v ON k.id = v.kaos_master_id
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (k.nama_kaos LIKE :search OR k.merek LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($kategori > 0) {
    $query .= " AND k.kategori_id = :kategori";
    $params[':kategori'] = $kategori;
}

if (!empty($size)) {
    $query .= " AND EXISTS (SELECT 1 FROM kaos_varian v2 WHERE v2.kaos_master_id = k.id AND v2.size = :size)";
    $params[':size'] = $size;
}

$query .= " GROUP BY k.id HAVING total_stok > 0";

// Sorting
switch($sort) {
    case 'termurah':
        $query .= " ORDER BY harga ASC";
        break;
    case 'termahal':
        $query .= " ORDER BY harga DESC";
        break;
    case 'nama':
        $query .= " ORDER BY k.nama_kaos ASC";
        break;
    default:
        $query .= " ORDER BY k.created_at DESC";
}

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare variants data for JS
    $variants_data = [];
    foreach ($products as $p) {
        $q = "SELECT id, warna, warna_hex, size, harga, foto_varian as foto, stok FROM kaos_varian WHERE kaos_master_id = :master_id";
        $st = $conn->prepare($q);
        $st->execute(['master_id' => $p['id']]);
        $variants_data[$p['id']] = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $products = [];
}

// Get categories
try {
    $stmt_kat = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
    $categories = $stmt_kat->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_cart = $conn->prepare("SELECT SUM(qty) as total FROM cart WHERE customer_id = :customer_id");
        $stmt_cart->execute([':customer_id' => $_SESSION['user_id']]);
        $cart_result = $stmt_cart->fetch(PDO::FETCH_ASSOC);
        $cart_count = $cart_result['total'] ?? 0;
    } catch(PDOException $e) {
        // Error silent
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #10B981; /* Emerald 500 */
            --secondary: #0F766E; /* Teal 700 */
            --dark: #1F2937;
            --light: #F9FAFB;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ECFDF5;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            color: #334155;
            min-height: 100vh;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: var(--primary);
        }
        
        .cart-icon {
            position: relative;
            font-size: 18px;
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        
        .shop-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
        }
        
        /* Sidebar Filters */
        .filters-sidebar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .filter-section {
            margin-bottom: 30px;
        }
        
        .filter-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            font-size: 16px;
        }
        
        .filter-option {
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 12px;
            cursor: pointer;
            color: #64748B;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .filter-option:hover {
            background: rgba(79, 70, 229, 0.05);
            color: var(--primary);
        }
        
        .filter-option.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        
        /* Products Area */
        .shop-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .search-bar {
            width: 100%;
            padding: 14px 20px 14px 48px;
            border: 2px solid #E5E7EB;
            border-radius: 14px;
            font-size: 15px;
            background: white url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="%2364748B" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>') no-repeat 16px center;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .search-bar:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        
        .sort-select {
            padding: 12px 20px;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            color: var(--dark);
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .sort-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            background-size: cover;
            background-position: center;
            background-color: #f1f5f9;
            position: relative;
            overflow: hidden;
        }
        
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .product-card:hover .product-overlay {
            opacity: 1;
        }
        
        .product-info {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category {
            font-size: 12px;
            color: var(--primary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .product-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .product-meta {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 16px;
        }
        
        .product-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
        }
        
        .btn-add-cart {
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
        }
        
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
        
        .btn-add-cart:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            background: #94a3b8;
            box-shadow: none;
            transform: none;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            color: #64748B;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .custom-notification {
            z-index: 1000;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-out;
            font-weight: 500;
        }
        
        @keyframes slideIn {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            color: #64748B;
        }
        
        @media (max-width: 1024px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            .filters-sidebar {
                position: relative;
                top: 0;
            }
        }
        
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 16px;
            }
            .shop-header {
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }
        
        /* SweetAlert on top of modal */
        .swal-on-top {
            z-index: 3000 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-layer-group"></i>
                DistroZone
            </a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="shop.php" class="active">Shop</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="orders.php" title="Pesanan Saya"><i class="fas fa-box"></i></a>
                    <a href="settings.php" title="Pengaturan"><i class="fas fa-cog"></i></a>
                    <a href="../auth/logout.php" class="btn">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="shop-layout">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <div class="filter-section">
                    <h3 class="filter-title">Kategori</h3>
                    <a href="shop.php" class="filter-option <?php echo $kategori == 0 ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Semua Kategori
                    </a>
                    <?php foreach($categories as $kat): ?>
                        <a href="?kategori=<?php echo $kat['id']; ?><?php echo !empty($size) ? '&size='.$size : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $kategori == $kat['id'] ? 'active' : ''; ?>">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Size</h3>
                    <div class="filter-buttons">
                        <a href="shop.php<?php echo $kategori > 0 ? '?kategori='.$kategori : ''; ?><?php echo !empty($search) ? (($kategori > 0 ? '&' : '?').'search='.urlencode($search)) : ''; ?>" 
                           class="filter-btn <?php echo empty($size) ? 'active' : ''; ?>">
                            Semua
                        </a>
                        <?php 
                        $sizes = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
                        foreach($sizes as $s): ?>
                            <a href="?size=<?php echo $s; ?><?php echo $kategori > 0 ? '&kategori='.$kategori : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                               class="filter-btn <?php echo $size == $s ? 'active' : ''; ?>">
                                <?php echo $s; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Reset Filter</h3>
                    <a href="shop.php" class="btn-add-cart" style="justify-content: center; text-decoration: none;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </aside>
            
            <!-- Products Area -->
            <div class="products-area">
                <div class="shop-header">
                    <div class="results-count" style="color: #64748B; font-weight: 500;">
                        Menampilkan <?php echo count($products); ?> produk
                    </div>
                    
                    <form method="GET" style="display: flex; gap: 12px; align-items: center; flex: 1; justify-content: flex-end;">
                        <?php if($kategori > 0): ?> <input type="hidden" name="kategori" value="<?php echo $kategori; ?>"> <?php endif; ?>
                        <?php if(!empty($size)): ?> <input type="hidden" name="size" value="<?php echo $size; ?>"> <?php endif; ?>
                        <?php if(!empty($search)): ?> <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"> <?php endif; ?>
                        
                        <select name="sort" class="sort-select" onchange="this.form.submit()">
                            <option value="terbaru" <?php echo $sort == 'terbaru' ? 'selected' : ''; ?>>Terbaru</option>
                            <option value="termurah" <?php echo $sort == 'termurah' ? 'selected' : ''; ?>>Harga Terendah</option>
                            <option value="termahal" <?php echo $sort == 'termahal' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                            <option value="nama" <?php echo $sort == 'nama' ? 'selected' : ''; ?>>Nama A-Z</option>
                        </select>
                    </form>
                </div>
                
                <form method="GET" style="margin-bottom: 30px;">
                    <?php if($kategori > 0): ?> <input type="hidden" name="kategori" value="<?php echo $kategori; ?>"> <?php endif; ?>
                    <?php if(!empty($size)): ?> <input type="hidden" name="size" value="<?php echo $size; ?>"> <?php endif; ?>
                    <input type="text" name="search" class="search-bar" 
                           placeholder="Cari produk apa hari ini?" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </form>
                
                <?php if(empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search" style="font-size: 64px; color: #CBD5E1; margin-bottom: 20px;"></i>
                        <h3>Produk Tidak Ditemukan</h3>
                        <p>Coba kata kunci lain atau reset filter Anda.</p>
                        <a href="shop.php" class="btn-add-cart" style="display: inline-block; text-decoration: none; padding: 12px 24px;">Lihat Semua Produk</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image" style="background-image: url('../<?php echo !empty($product['foto_utama']) ? htmlspecialchars($product['foto_utama']) : 'assets/img/no-image.jpg'; ?>');">
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($product['nama_kategori']); ?></div>
                                <div class="product-name"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                                
                                <div class="product-meta">
                                    <i class="fas fa-layer-group" style="color: var(--primary);"></i> <?php echo htmlspecialchars($product['merek']); ?>
                                    &nbsp;|&nbsp; 
                                    <i class="fas fa-boxes" style="color: var(--primary);"></i> Stok: <?php echo htmlspecialchars($product['total_stok']); ?>
                                </div>
                                
                                <div class="product-footer">
                                    <div class="product-price">Mulai <?php echo format_rupiah($product['harga']); ?></div>
                                    <button onclick="openQuickView(<?php echo $product['id']; ?>)" class="btn-add-cart" style="text-decoration: none; border: none; cursor: pointer; width: 100%;">
                                        Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick View Modal -->
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content-qv {
            background: white;
            padding: 30px;
            border-radius: 24px;
            width: 90%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 40px;
            position: relative;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .modal.active .modal-content-qv {
            transform: translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #64748B;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-img-container {
            border-radius: 16px;
            overflow: hidden;
            background: #F1F5F9;
        }

        .modal-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: opacity 0.3s;
        }

        .variant-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .color-variants {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid white;
            box-shadow: 0 0 0 1px #E2E8F0;
            transition: all 0.2s;
            position: relative;
        }

        .color-option.active {
            box-shadow: 0 0 0 2px var(--primary);
            transform: scale(1.1);
        }

        .color-tooltip {
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: var(--dark);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s;
        }

        .color-option:hover .color-tooltip {
            opacity: 1;
            visibility: visible;
            bottom: 120%;
        }

        .size-variants {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            max-width: 300px;
        }

        .size-option {
            padding: 8px 16px;
            border: 2px solid #F1F5F9;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            color: #64748B;
        }

        .size-option:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .size-option.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
    </style>

    <div id="quickViewModal" class="modal">
        <div class="modal-content-qv">
            <span class="modal-close" onclick="closeQuickView()"><i class="fas fa-times"></i></span>
            <div class="modal-img-container">
                <img id="modalImg" src="" alt="" class="modal-img">
            </div>
            <div class="modal-details">
                <span id="modalMerek" style="color: var(--primary); font-weight: 700; font-size: 14px; display: block; margin-bottom: 8px;"></span>
                <h2 id="modalTitle" style="margin-bottom: 12px; font-weight: 800;"></h2>
                <div id="modalPrice" style="font-size: 24px; font-weight: 800; color: var(--secondary); margin-bottom: 20px;"></div>
                <p id="modalDesc" style="color: #64748B; font-size: 14px; line-height: 1.6; margin-bottom: 24px;"></p>
                
                <div class="variant-selection">
                    <span class="variant-label">Warna</span>
                    <div id="colorContainer" class="color-variants"></div>

                    <span class="variant-label">Ukuran</span>
                    <div id="sizeContainer" class="size-variants"></div>
                </div>

                <div style="font-size: 13px; color: #64748B; margin-bottom: 20px;">
                    Stok Tersedia: <span id="stockValue" style="font-weight: 700; color: var(--dark);"></span>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 16px;">
                    <button class="btn-add-cart" style="flex: 1; padding: 16px; border: none; cursor: pointer; font-size: 14px;" onclick="addToCartFromModal()">
                        <i class="fas fa-shopping-cart"></i> Tambahkan Keranjang
                    </button>
                    <button class="btn-add-cart" style="flex: 1; padding: 16px; border: none; cursor: pointer; font-size: 14px;" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> Beli Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const allVariants = <?php echo json_encode($variants_data); ?>;
        const productsList = <?php echo json_encode($products); ?>;
        let currentProduct = null;
        let selectedVariant = null;

        // Auto-open modal if present in URL
        window.onload = function() {
            const urlParams = new URL(window.location.href).searchParams;
            const modalId = urlParams.get('open_modal');
            if (modalId && productsList) {
                const product = productsList.find(p => p.id == modalId);
                if (product) {
                    openQuickView(product);
                    // Clean URL without refresh
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + window.location.search.replace(/[\?&]open_modal=[^&]+/, '').replace(/^&/, '?');
                     window.history.replaceState({}, document.title, newUrl);
                }
            }
            
            // Existing msg logic
            const msg = urlParams.get('msg');
            if (msg === 'account_deleted') {
                Swal.fire({
                   title: 'Akun Terhapus',
                   text: 'Akun Anda telah berhasil dihapus secara permanen. Terima kasih telah berbelanja di DistroZone.',
                   icon: 'success'
                });
            }
        };

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        function openQuickView(input) {
            // Support passing ID or Object
            const product = (typeof input === 'object') ? input : productsList.find(p => p.id == input);
            if (!product) {
                console.error("Product not found for ID:", input);
                return;
            }

            currentProduct = product;
            const modal = document.getElementById('quickViewModal');
            const variants = allVariants[product.id];
            
            document.getElementById('modalTitle').innerText = product.nama_kaos;
            document.getElementById('modalMerek').innerText = product.merek || 'DistroZone';
            document.getElementById('modalDesc').innerText = product.deskripsi || 'Kaos premium berkualitas.';
            
            const colorContainer = document.getElementById('colorContainer');
            colorContainer.innerHTML = '';
            
            const uniqueColors = [];
            const colorMap = new Map();
            variants.forEach(v => {
                if (!colorMap.has(v.warna)) {
                    colorMap.set(v.warna, v.warna_hex || '#CBD5E1');
                    uniqueColors.push({ name: v.warna, hex: v.warna_hex || '#CBD5E1' });
                }
            });
            
            
            uniqueColors.forEach((colorObj, index) => {
                const opt = document.createElement('div');
                opt.className = 'color-option' + (index === 0 ? ' active' : '');
                opt.style.backgroundColor = colorObj.hex;
                opt.onclick = () => selectColor(colorObj.name, product.id, opt);
                
                const tooltip = document.createElement('span');
                tooltip.className = 'color-tooltip';
                tooltip.innerText = colorObj.name;
                opt.appendChild(tooltip);
                colorContainer.appendChild(opt);
            });
            
            if (uniqueColors.length > 0) selectColor(uniqueColors[0].name, product.id, colorContainer.firstChild);
            modal.classList.add('active');
        }

        function selectColor(color, masterId, element) {
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            const variants = allVariants[masterId];
            const colorVariants = variants.filter(v => v.warna === color);
            const sizeContainer = document.getElementById('sizeContainer');
            sizeContainer.innerHTML = '';
            
            colorVariants.forEach((v, index) => {
                const opt = document.createElement('div');
                opt.className = 'size-option' + (index === 0 ? ' active' : '');
                opt.innerText = v.size;
                opt.onclick = () => selectSize(v, opt);
                sizeContainer.appendChild(opt);
            });
            if (colorVariants.length > 0) selectSize(colorVariants[0], sizeContainer.firstChild);
        }

        function selectSize(variant, element) {
            document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            selectedVariant = variant;
            
            const img = document.getElementById('modalImg');
            img.style.opacity = '0';
            setTimeout(() => {
                img.src = '../' + (variant.foto || currentProduct.foto_utama || 'assets/img/no-image.jpg');
                img.style.opacity = '1';
            }, 300);
            
            document.getElementById('modalPrice').innerText = formatRupiah(variant.harga);
            document.getElementById('stockValue').innerText = variant.stok;
        }

        async function addToCartFromModal() {
            if (!selectedVariant) return;
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    title: 'Belum Login',
                    text: 'Silakan login terlebih dahulu untuk berbelanja.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'Login Sekarang',
                    cancelButtonText: 'Nanti',
                    backdrop: true,
                    customClass: {
                        container: 'swal-on-top'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const currentUrl = new URL(window.location.href);
                        if (currentProduct) {
                            currentUrl.searchParams.set('open_modal', currentProduct.id);
                        }
                        window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(currentUrl.toString());
                    }
                });
                return;
            <?php endif; ?>

            const formData = new FormData();
            formData.append('kaos_id', selectedVariant.id);
            formData.append('qty', 1);

            const response = await fetch('../api/add_to_cart.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                Swal.fire({ title: 'Berhasil!', text: 'Produk masuk keranjang', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                document.querySelectorAll('.cart-badge').forEach(el => el.innerText = data.cart_count);
                closeQuickView();
            }
        }

        async function buyNow() {
            if (!selectedVariant) return;
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                Swal.fire({
                    title: 'Belum Login',
                    text: 'Silakan login terlebih dahulu untuk berbelanja.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'Login Sekarang',
                    cancelButtonText: 'Nanti',
                    backdrop: true,
                    customClass: {
                        container: 'swal-on-top'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const currentUrl = new URL(window.location.href);
                        if (currentProduct) {
                            currentUrl.searchParams.set('open_modal', currentProduct.id);
                        }
                        window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(currentUrl.toString());
                    }
                });
                return;
            <?php endif; ?>

            const formData = new FormData();
            formData.append('kaos_id', selectedVariant.id);
            formData.append('qty', 1);

            const response = await fetch('../api/add_to_cart.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                window.location.href = 'checkout.php';
            } else {
                Swal.fire({
                    title: 'Gagal',
                    text: data.message,
                    icon: 'error'
                });
            }
        }

        function closeQuickView() {
            document.getElementById('quickViewModal').classList.remove('active');
        }

        window.onclick = function(e) { if (e.target.id == 'quickViewModal') closeQuickView(); }

        // Existing addToCart kept for direct clicks if any, but we use Modal mostly
        function showNotification(msg) {
            Swal.fire({ text: msg, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
    </script>

<?php include '../includes/chat_widget.php'; ?>
</body>
</html>