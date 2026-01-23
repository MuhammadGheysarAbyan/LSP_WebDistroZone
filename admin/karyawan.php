<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $user_code = generate_code('KSR');
        
        // Handle photo upload
        $foto_path = NULL;
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_file($_FILES['foto'], '../assets/uploads/users/');
            if ($upload['success']) {
                $foto_path = 'assets/uploads/users/' . $upload['filename'];
            }
        }

        // Check if username or email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $check_stmt->execute(['username' => $_POST['username'], 'email' => $_POST['email']]);
        if ($check_stmt->fetch()) {
            header('Location: karyawan.php?action=add&error=Username atau Email sudah digunakan');
            exit;
        }

        $data = [
            'user_code' => $user_code,
            'username' => $_POST['username'],
            'nama' => $_POST['nama'],
            'password' => $_POST['password'],
            'role' => 'kasir',
            'email' => $_POST['email'],
            'no_telp' => $_POST['no_telp'],
            'alamat' => $_POST['alamat'],
            'shift' => $_POST['shift'],
            'status' => 'active',
            'nik' => $_POST['nik'],
            'foto' => $foto_path,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $sql = "INSERT INTO users (" . implode(', ', array_keys($data)) . ") 
                    VALUES (:" . implode(', :', array_keys($data)) . ")";
            $stmt = $conn->prepare($sql);
            $stmt->execute($data);
            
            header('Location: karyawan.php?success=Karyawan berhasil ditambahkan');
            exit;
        } catch (PDOException $e) {
            header('Location: karyawan.php?action=add&error=Gagal menambahkan karyawan: ' . $e->getMessage());
            exit;
        }
    }
    elseif ($action === 'edit' && $id) {
        // Check if username or email already exists for another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
        $check_stmt->execute(['username' => $_POST['username'], 'email' => $_POST['email'], 'id' => $id]);
        if ($check_stmt->fetch()) {
            header('Location: karyawan.php?action=edit&id=' . $id . '&error=Username atau Email sudah digunakan');
            exit;
        }

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
        
        // Handle photo upload
        if (!empty($_FILES['foto']['name'])) {
            $upload = upload_file($_FILES['foto'], '../assets/uploads/users/');
            if ($upload['success']) {
                $data['foto'] = 'assets/uploads/users/' . $upload['filename'];
            }
        }

        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password']; 
        }
        
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        
        try {
            $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = :id";
            $data['id'] = $id;
            $stmt = $conn->prepare($sql);
            $stmt->execute($data);
            
            header('Location: karyawan.php?success=Data karyawan berhasil diupdate');
            exit;
        } catch (PDOException $e) {
            header('Location: karyawan.php?action=edit&id=' . $id . '&error=Gagal mengupdate data: ' . $e->getMessage());
            exit;
        }
    }
}

// Handle GET actions (Delete)
if ($action === 'delete' && $id) {
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id' => $id]);
    
    header('Location: karyawan.php?success=Karyawan berhasil dihapus permanen');
    exit;
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
         :root {
            --primary: #10B981;
            --primary-dark: #047857;
            --secondary: #0F766E;
            --bg-color: #ECFDF5;
            --text-dark: #1F2937;
            --text-light: #64748B;
            --white: #FFFFFF;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(15, 118, 110, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, rgba(15, 118, 110, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .logo {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo i {
            font-size: 24px;
            color: var(--primary);
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }
        
        .nav-item {
            margin-bottom: 8px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .top-bar {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .top-bar h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* Content Card */
        .content-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
            margin-bottom: 24px;
        }
        
        .content-card h3 {
            margin-bottom: 24px;
            color: var(--text-dark);
            font-size: 20px;
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
            padding: 12px 16px 12px 48px;
            background: white;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--text-light);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .btn-secondary:hover {
            background: var(--bg-color);
            color: var(--primary);
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
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            border-bottom: 2px solid rgba(16, 185, 129, 0.1);
        }
        
        td {
            padding: 16px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            vertical-align: middle;
        }
        
        tbody tr {
            transition: background-color 0.3s;
        }

        tbody tr:hover {
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        /* Badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary);
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
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: var(--text-dark);
            font-size: 20px;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 24px;
            border-top: 1px solid rgba(16, 185, 129, 0.1);
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
            color: var(--text-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        /* Alert */
        .alert {
            padding: 16px 24px;
            border-radius: 12px;
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
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon.edit {
            background: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
        }
        
        .btn-icon.edit:hover {
            background: rgba(59, 130, 246, 0.2);
        }
        
        .btn-icon.delete {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }
        
        .btn-icon.delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .btn-icon.view {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }
        
        .btn-icon.view:hover {
            background: rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-layer-group"></i>
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
                    <a href="chat.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        Live Chat
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
                        <div style="font-size: 12px; color: var(--text-light);">Administrator</div>
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
                            <th>Foto</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>No. Telp</th>
                            <th>Alamat</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($karyawan)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                                    Belum ada data karyawan
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($karyawan as $k): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($k['foto'])): ?>
                                        <img src="../<?php echo htmlspecialchars($k['foto']); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #E5E7EB; display: flex; align-items: center; justify-content: center; color: #9CA3AF;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($k['user_code']); ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($k['nama']); ?></td>
                                <td><?php echo htmlspecialchars($k['nik']); ?></td>
                                <td><?php echo htmlspecialchars($k['username']); ?></td>
                                <td><?php echo htmlspecialchars($k['email']); ?></td>
                                <td><?php echo htmlspecialchars($k['no_telp']); ?></td>
                                <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($k['alamat'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($k['alamat'] ?? '-'); ?>
                                </td>
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
                <button class="btn-icon" onclick="closeModal()" style="background: none; color: var(--text-light);">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="karyawan.php?action=<?php echo $action; ?><?php echo $id ? '&id=' . $id : ''; ?>" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" id="nama" name="nama" class="form-control" 
                               value="<?php echo htmlspecialchars($karyawan_detail['nama'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="foto">Foto Karyawan</label>
                        <?php if (!empty($karyawan_detail['foto'])): ?>
                            <div style="margin-bottom: 10px;">
                                <img src="../<?php echo htmlspecialchars($karyawan_detail['foto']); ?>" alt="Current Photo" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                                <p style="font-size: 12px; color: var(--text-light);">Foto saat ini</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
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
        
        function editKaryawan(id) {
            window.location.href = `karyawan.php?action=edit&id=${id}`;
        }
        
        function deleteKaryawan(id) {
            if (confirm('Apakah Anda yakin ingin menonaktifkan karyawan ini?')) {
                window.location.href = `karyawan.php?action=delete&id=${id}`;
            }
        }
        
        function closeModal() {
            window.location.href = 'karyawan.php';
        }
    </script>
</body>
</html>