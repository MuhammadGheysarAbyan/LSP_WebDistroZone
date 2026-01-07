<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Get categories for dropdown
$query = "SELECT * FROM kategori ORDER BY nama_kategori";
$stmt = $conn->query($query);
$kategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$search = $_GET['search'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        // Generate kode_kaos: KOS-XXX
        $query = "SELECT COUNT(*) as count FROM kaos WHERE kode_kaos LIKE 'KOS-%'";
        $stmt = $conn->query($query);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
        $kode_kaos = 'KOS-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        
        $data = [
            'kode_kaos' => $kode_kaos,
            'nama_kaos' => $_POST['nama_kaos'],
            'merek' => $_POST['merek'],
            'type' => $_POST['type'],
            'warna' => $_POST['warna'],
            'size' => $_POST['size'],
            'kategori_id' => $_POST['kategori_id'],
            'harga' => $_POST['harga'],
            'harga_pokok' => $_POST['harga_pokok'],
            'stok' => $_POST['stok'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle file upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/products/';
            $fileName = time() . '_' . basename($_FILES['foto']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                $data['foto'] = 'assets/uploads/products/' . $fileName;
            }
        }
        
        $sql = "INSERT INTO kaos (" . implode(', ', array_keys($data)) . ") 
                VALUES (:" . implode(', :', array_keys($data)) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        header('Location: kaos.php?success=Kaos berhasil ditambahkan');
        exit;
    }
    elseif ($action === 'edit' && $id) {
        $data = [
            'nama_kaos' => $_POST['nama_kaos'],
            'merek' => $_POST['merek'],
            'type' => $_POST['type'],
            'warna' => $_POST['warna'],
            'size' => $_POST['size'],
            'kategori_id' => $_POST['kategori_id'],
            'harga' => $_POST['harga'],
            'harga_pokok' => $_POST['harga_pokok'],
            'stok' => $_POST['stok'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle file upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/products/';
            $fileName = time() . '_' . basename($_FILES['foto']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
                $data['foto'] = 'assets/uploads/products/' . $fileName;
            }
        }
        
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        
        $sql = "UPDATE kaos SET " . implode(', ', $setClause) . " WHERE id = :id";
        $data['id'] = $id;
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        header('Location: kaos.php?success=Kaos berhasil diupdate');
        exit;
    }
    elseif ($action === 'delete' && $id) {
        $sql = "DELETE FROM kaos WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        header('Location: kaos.php?success=Kaos berhasil dihapus');
        exit;
    }
}

// Get kaos data with search
$query = "SELECT k.*, kat.nama_kategori FROM kaos k 
          LEFT JOIN kategori kat ON k.kategori_id = kat.id 
          WHERE 1=1";
          
if ($search) {
    $query .= " AND (k.nama_kaos LIKE :search OR k.merek LIKE :search OR k.kode_kaos LIKE :search)";
}

$query .= " ORDER BY k.created_at DESC";
$stmt = $conn->prepare($query);

if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$kaos_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get kaos detail for edit
$kaos_detail = null;
if ($id && $action === 'edit') {
    $query = "SELECT * FROM kaos WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    $kaos_detail = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kaos - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same base styles as karyawan.php */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #334155;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #1E293B;
            color: white;
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 24px;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin: 4px 12px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            color: white;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 24px;
        }
        
        .top-bar {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .top-bar h2 {
            font-size: 24px;
            color: #1E293B;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3B82F6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        
        .content-card h3 {
            margin-bottom: 20px;
            color: #1E293B;
        }
        
        /* Search and Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }
        
        .search-box {
            flex: 1;
            max-width: 400px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3B82F6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563EB;
        }
        
        .btn-secondary {
            background: #F1F5F9;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #E2E8F0;
        }
        
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #DC2626;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #F8FAFC;
        }
        
        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #64748B;
            font-size: 14px;
            border-bottom: 2px solid #E2E8F0;
        }
        
        td {
            padding: 16px 12px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        tbody tr:hover {
            background: #F8FAFC;
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #D1FAE5;
            color: #059669;
        }
        
        .badge-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        
        .badge-danger {
            background: #FEE2E2;
            color: #DC2626;
        }
        
        .badge-info {
            background: #DBEAFE;
            color: #3B82F6;
        }
        
        /* Product Image */
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #E2E8F0;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 24px;
            border-top: 1px solid #E2E8F0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* File Upload */
        .file-upload {
            border: 2px dashed #E2E8F0;
            border-radius: 10px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: #3B82F6;
            background: #F8FAFC;
        }
        
        .file-upload input {
            display: none;
        }
        
        .file-preview {
            margin-top: 16px;
        }
        
        .file-preview img {
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
        }
        
        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 10px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #059669;
            border: 1px solid #A7F3D0;
        }
        
        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon.edit {
            background: #DBEAFE;
            color: #3B82F6;
        }
        
        .btn-icon.edit:hover {
            background: #BFDBFE;
        }
        
        .btn-icon.delete {
            background: #FEE2E2;
            color: #EF4444;
        }
        
        .btn-icon.delete:hover {
            background: #FECACA;
        }
        
        .btn-icon.view {
            background: #D1FAE5;
            color: #10B981;
        }
        
        .btn-icon.view:hover {
            background: #A7F3D0;
        }
        
        /* Stock Status */
        .stock-status {
            font-weight: 600;
        }
        
        .stock-low {
            color: #DC2626;
        }
        
        .stock-medium {
            color: #D97706;
        }
        
        .stock-high {
            color: #059669;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1>DistroZone</h1>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="karyawan.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        Kelola Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kaos.php" class="nav-link active">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="verifikasi.php" class="nav-link">
                        <i class="fas fa-check-circle"></i>
                        Verifikasi Pembayaran
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Pengaturan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Kelola Kaos</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: #64748B;">Administrator</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari kaos..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i>
                    Tambah Kaos
                </button>
            </div>
            
            <!-- Kaos Table -->
            <div class="content-card">
                <h3>Daftar Kaos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama Kaos</th>
                            <th>Merek</th>
                            <th>Kategori</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kaos_list)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #94A3B8;">
                                    <i class="fas fa-tshirt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    Belum ada data kaos
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kaos_list as $k): 
                                $stockClass = $k['stok'] < 10 ? 'stock-low' : ($k['stok'] < 50 ? 'stock-medium' : 'stock-high');
                            ?>
                            <tr>
                                <td>
                                    <?php if ($k['foto']): ?>
                                        <img src="../<?php echo htmlspecialchars($k['foto']); ?>" 
                                             alt="<?php echo htmlspecialchars($k['nama_kaos']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #F1F5F9; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-tshirt" style="color: #94A3B8;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($k['kode_kaos']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">Size: <?php echo htmlspecialchars($k['size']); ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($k['nama_kaos']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">
                                        <?php echo htmlspecialchars($k['type']); ?> • <?php echo htmlspecialchars($k['warna']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($k['merek']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($k['nama_kategori'] ?? '-'); ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo format_rupiah($k['harga']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">Modal: <?php echo format_rupiah($k['harga_pokok']); ?></div>
                                </td>
                                <td>
                                    <div class="stock-status <?php echo $stockClass; ?>">
                                        <?php echo $k['stok']; ?> pcs
                                    </div>
                                    <?php if ($k['stok'] < 10): ?>
                                        <div style="font-size: 12px; color: #DC2626;">Stok menipis!</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon edit" onclick="editKaos(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon delete" onclick="deleteKaos(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn-icon view" onclick="viewKaos(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="kaosModal" class="modal <?php echo ($action === 'add' || $action === 'edit') ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' ? 'Edit Kaos' : 'Tambah Kaos Baru'; ?></h3>
                <button class="btn-icon" onclick="closeModal()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="kaos.php?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_kaos">Nama Kaos *</label>
                        <input type="text" id="nama_kaos" name="nama_kaos" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['nama_kaos'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="merek">Merek *</label>
                        <input type="text" id="merek" name="merek" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['merek'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Pilih Type</option>
                            <option value="Lengan Panjang" <?php echo ($kaos_detail['type'] ?? '') === 'Lengan Panjang' ? 'selected' : ''; ?>>Lengan Panjang</option>
                            <option value="Lengan Pendek" <?php echo ($kaos_detail['type'] ?? '') === 'Lengan Pendek' ? 'selected' : ''; ?>>Lengan Pendek</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="warna">Warna *</label>
                        <input type="text" id="warna" name="warna" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['warna'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="size">Size *</label>
                        <select id="size" name="size" class="form-control" required>
                            <option value="">Pilih Size</option>
                            <option value="XS" <?php echo ($kaos_detail['size'] ?? '') === 'XS' ? 'selected' : ''; ?>>XS</option>
                            <option value="S" <?php echo ($kaos_detail['size'] ?? '') === 'S' ? 'selected' : ''; ?>>S</option>
                            <option value="M" <?php echo ($kaos_detail['size'] ?? '') === 'M' ? 'selected' : ''; ?>>M</option>
                            <option value="L" <?php echo ($kaos_detail['size'] ?? '') === 'L' ? 'selected' : ''; ?>>L</option>
                            <option value="XL" <?php echo ($kaos_detail['size'] ?? '') === 'XL' ? 'selected' : ''; ?>>XL</option>
                            <option value="2XL" <?php echo ($kaos_detail['size'] ?? '') === '2XL' ? 'selected' : ''; ?>>2XL</option>
                            <option value="3XL" <?php echo ($kaos_detail['size'] ?? '') === '3XL' ? 'selected' : ''; ?>>3XL</option>
                            <option value="4XL" <?php echo ($kaos_detail['size'] ?? '') === '4XL' ? 'selected' : ''; ?>>4XL</option>
                            <option value="5XL" <?php echo ($kaos_detail['size'] ?? '') === '5XL' ? 'selected' : ''; ?>>5XL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="kategori_id">Kategori *</label>
                        <select id="kategori_id" name="kategori_id" class="form-control" required>
                            <option value="">Pilih Kategori</option>
                            <?php foreach ($kategories as $kat): ?>
                            <option value="<?php echo $kat['id']; ?>" 
                                <?php echo ($kaos_detail['kategori_id'] ?? '') == $kat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kat['nama_kategori']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga_pokok">Harga Pokok *</label>
                        <input type="number" id="harga_pokok" name="harga_pokok" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['harga_pokok'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga">Harga Jual *</label>
                        <input type="number" id="harga" name="harga" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['harga'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stok">Stok *</label>
                        <input type="number" id="stok" name="stok" class="form-control" 
                               value="<?php echo htmlspecialchars($kaos_detail['stok'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="foto">Foto Kaos</label>
                        <div class="file-upload" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94A3B8; margin-bottom: 12px;"></i>
                            <div style="color: #64748B;">Klik untuk upload foto</div>
                            <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">Format: JPG, PNG. Max: 2MB</div>
                            <input type="file" id="fileInput" name="foto" accept="image/*" onchange="previewImage(event)">
                        </div>
                        <div class="file-preview" id="imagePreview">
                            <?php if ($kaos_detail && $kaos_detail['foto']): ?>
                                <img src="../<?php echo htmlspecialchars($kaos_detail['foto']); ?>" 
                                     alt="Preview" style="max-width: 200px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'edit' ? 'Update' : 'Simpan'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Kaos</h3>
                <button class="btn-icon" onclick="closeViewModal()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="kaosDetail">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Tutup</button>
            </div>
        </div>
    </div>
    
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const search = this.value;
                window.location.href = `kaos.php?search=${encodeURIComponent(search)}`;
            }
        });
        
        // Modal functions
        function openModal(action, id = '') {
            if (action === 'add') {
                window.location.href = 'kaos.php?action=add';
            } else if (action === 'edit') {
                window.location.href = `kaos.php?action=edit&id=${id}`;
            }
        }
        
        function closeModal() {
            window.location.href = 'kaos.php';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        function editKaos(id) {
            openModal('edit', id);
        }
        
        function deleteKaos(id) {
            if (confirm('Apakah Anda yakin ingin menghapus kaos ini?')) {
                window.location.href = `kaos.php?action=delete&id=${id}`;
            }
        }
        
        function viewKaos(id) {
            fetch(`get_kaos_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const detailDiv = document.getElementById('kaosDetail');
                    const stockClass = data.stok < 10 ? 'stock-low' : (data.stok < 50 ? 'stock-medium' : 'stock-high');
                    
                    detailDiv.innerHTML = `
                        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 32px;">
                            <div>
                                ${data.foto ? 
                                    `<img src="../${data.foto}" style="width: 100%; border-radius: 12px; border: 1px solid #E2E8F0;">` :
                                    `<div style="width: 100%; aspect-ratio: 1; background: #F1F5F9; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-tshirt" style="font-size: 64px; color: #94A3B8;"></i>
                                    </div>`
                                }
                            </div>
                            
                            <div>
                                <div style="margin-bottom: 24px;">
                                    <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Kode Kaos</div>
                                    <div style="font-weight: 600; font-size: 18px;">${data.kode_kaos}</div>
                                </div>
                                
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Nama Kaos</div>
                                    <div style="font-weight: 600; font-size: 24px;">${data.nama_kaos}</div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Merek</div>
                                        <div style="font-weight: 600;">${data.merek}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Kategori</div>
                                        <span class="badge badge-info">${data.nama_kategori || '-'}</span>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Type</div>
                                        <div>${data.type}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Warna</div>
                                        <div>${data.warna}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Size</div>
                                        <div>${data.size}</div>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Harga Pokok</div>
                                        <div style="font-weight: 600;">${formatRupiah(data.harga_pokok)}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Harga Jual</div>
                                        <div style="font-weight: 600; font-size: 20px; color: #059669;">${formatRupiah(data.harga)}</div>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 20px;">
                                    <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Stok</div>
                                    <div class="stock-status ${stockClass}" style="font-size: 24px;">
                                        ${data.stok} pcs
                                    </div>
                                    ${data.stok < 10 ? '<div style="font-size: 12px; color: #DC2626; margin-top: 4px;">Stok menipis!</div>' : ''}
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Dibuat</div>
                                        <div>${new Date(data.created_at).toLocaleDateString('id-ID')}</div>
                                    </div>
                                    <div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Terakhir Update</div>
                                        <div>${data.updated_at ? new Date(data.updated_at).toLocaleDateString('id-ID') : '-'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('viewModal').classList.add('active');
                });
        }
        
        function previewImage(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (event.target.files.length > 0) {
                const file = event.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '200px';
                    img.style.borderRadius = '8px';
                    img.style.border = '1px solid #E2E8F0';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        }
        
        // Format currency
        function formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        // Auto close modal on escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeViewModal();
            }
        });
    </script>
</body>
</html>