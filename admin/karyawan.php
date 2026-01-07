<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        // Generate user_code: KRY-YYYYMM-XXX
        $prefix = 'KRY';
        $date = date('Ym');
        $query = "SELECT COUNT(*) as count FROM users WHERE user_code LIKE '{$prefix}-{$date}-%'";
        $stmt = $conn->query($query);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;
        $user_code = $prefix . '-' . $date . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
        
        $data = [
            'user_code' => $user_code,
            'username' => $_POST['username'],
            'nama' => $_POST['nama'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => 'kasir',
            'email' => $_POST['email'],
            'no_telp' => $_POST['no_telp'],
            'alamat' => $_POST['alamat'],
            'shift' => $_POST['shift'],
            'status' => 'active',
            'nik' => $_POST['nik'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO users (" . implode(', ', array_keys($data)) . ") 
                VALUES (:" . implode(', :', array_keys($data)) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        header('Location: karyawan.php?success=Karyawan berhasil ditambahkan');
        exit;
    }
    elseif ($action === 'edit' && $id) {
        $data = [
            'username' => $_POST['username'],
            'nama' => $_POST['nama'],
            'email' => $_POST['email'],
            'no_telp' => $_POST['no_telp'],
            'alamat' => $_POST['alamat'],
            'shift' => $_POST['shift'],
            'status' => $_POST['status'],
            'nik' => $_POST['nik'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        
        $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = :id";
        $data['id'] = $id;
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
        
        header('Location: karyawan.php?success=Data karyawan berhasil diupdate');
        exit;
    }
    elseif ($action === 'delete' && $id) {
        $sql = "UPDATE users SET status = 'inactive' WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        header('Location: karyawan.php?success=Karyawan berhasil dinonaktifkan');
        exit;
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'kasir'";
if ($search) {
    $query .= " AND (nama LIKE :search OR username LIKE :search OR user_code LIKE :search OR nik LIKE :search)";
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$karyawan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get karyawan detail for edit
$karyawan_detail = null;
if ($id && $action === 'edit') {
    $query = "SELECT * FROM users WHERE id = :id AND role = 'kasir'";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $id]);
    $karyawan_detail = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse styles from dashboard.css and add specific styles */
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
        
        /* Sidebar - Same as dashboard */
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
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
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
            max-width: 500px;
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
                    <a href="karyawan.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        Kelola Karyawan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kaos.php" class="nav-link">
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
                <h2>Kelola Karyawan</h2>
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
                    <input type="text" id="searchInput" placeholder="Cari karyawan..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button class="btn btn-primary" onclick="openModal('add')">
                    <i class="fas fa-plus"></i>
                    Tambah Karyawan
                </button>
            </div>
            
            <!-- Karyawan Table -->
            <div class="content-card">
                <h3>Daftar Karyawan</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>No. Telp</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($karyawan)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #94A3B8;">
                                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    Belum ada data karyawan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($karyawan as $k): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($k['user_code']); ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($k['nama']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">NIK: <?php echo htmlspecialchars($k['nik']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($k['username']); ?></td>
                                <td><?php echo htmlspecialchars($k['email']); ?></td>
                                <td><?php echo htmlspecialchars($k['no_telp']); ?></td>
                                <td><?php echo htmlspecialchars($k['shift'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($k['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon edit" onclick="editKaryawan(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon delete" onclick="deleteKaryawan(<?php echo $k['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="btn-icon view" onclick="viewKaryawan(<?php echo $k['id']; ?>)">
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
    <div id="karyawanModal" class="modal <?php echo ($action === 'add' || $action === 'edit') ? 'active' : ''; ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $action === 'edit' ? 'Edit Karyawan' : 'Tambah Karyawan Baru'; ?></h3>
                <button class="btn-icon" onclick="closeModal()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="karyawan.php?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" id="nama" name="nama" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['nama'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><?php echo $action === 'edit' ? 'Password (Kosongkan jika tidak diubah)' : 'Password *'; ?></label>
                        <input type="password" id="password" name="password" class="form-control" 
                               <?php echo $action === 'edit' ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="no_telp">No. Telepon *</label>
                        <input type="tel" id="no_telp" name="no_telp" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['no_telp'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nik">NIK *</label>
                        <input type="text" id="nik" name="nik" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['nik'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3"><?php echo htmlspecialchars($karyawan_detail['alamat'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="shift">Shift</label>
                        <select id="shift" name="shift" class="form-control">
                            <option value="">Pilih Shift</option>
                            <option value="Pagi (08:00-16:00)" <?php echo ($karyawan_detail['shift'] ?? '') === 'Pagi (08:00-16:00)' ? 'selected' : ''; ?>>Pagi (08:00-16:00)</option>
                            <option value="Sore (16:00-24:00)" <?php echo ($karyawan_detail['shift'] ?? '') === 'Sore (16:00-24:00)' ? 'selected' : ''; ?>>Sore (16:00-24:00)</option>
                            <option value="Malam (00:00-08:00)" <?php echo ($karyawan_detail['shift'] ?? '') === 'Malam (00:00-08:00)' ? 'selected' : ''; ?>>Malam (00:00-08:00)</option>
                        </select>
                    </div>
                    
                    <?php if ($action === 'edit'): ?>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="active" <?php echo ($karyawan_detail['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo ($karyawan_detail['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                        </select>
                    </div>
                    <?php endif; ?>
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
                <h3>Detail Karyawan</h3>
                <button class="btn-icon" onclick="closeViewModal()" style="background: none; color: #94A3B8;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="karyawanDetail">
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
                window.location.href = `karyawan.php?search=${encodeURIComponent(search)}`;
            }
        });
        
        // Modal functions
        function openModal(action, id = '') {
            if (action === 'add') {
                window.location.href = 'karyawan.php?action=add';
            } else if (action === 'edit') {
                window.location.href = `karyawan.php?action=edit&id=${id}`;
            }
        }
        
        function closeModal() {
            window.location.href = 'karyawan.php';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        function editKaryawan(id) {
            openModal('edit', id);
        }
        
        function deleteKaryawan(id) {
            if (confirm('Apakah Anda yakin ingin menonaktifkan karyawan ini?')) {
                window.location.href = `karyawan.php?action=delete&id=${id}`;
            }
        }
        
        function viewKaryawan(id) {
            fetch(`get_karyawan_detail.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const detailDiv = document.getElementById('karyawanDetail');
                    detailDiv.innerHTML = `
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Kode Karyawan</div>
                            <div style="font-weight: 600; font-size: 18px;">${data.user_code}</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Nama Lengkap</div>
                                <div style="font-weight: 600;">${data.nama}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Username</div>
                                <div>${data.username}</div>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Email</div>
                                <div>${data.email}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">No. Telepon</div>
                                <div>${data.no_telp}</div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">NIK</div>
                            <div>${data.nik}</div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Alamat</div>
                            <div>${data.alamat || '-'}</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Shift</div>
                                <div>${data.shift || '-'}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Status</div>
                                <span class="badge ${data.status === 'active' ? 'badge-success' : 'badge-danger'}">
                                    ${data.status === 'active' ? 'Aktif' : 'Nonaktif'}
                                </span>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Tanggal Bergabung</div>
                                <div>${new Date(data.created_at).toLocaleDateString('id-ID')}</div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">Terakhir Update</div>
                                <div>${data.updated_at ? new Date(data.updated_at).toLocaleDateString('id-ID') : '-'}</div>
                            </div>
                        </div>
                    `;
                    document.getElementById('viewModal').classList.add('active');
                });
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