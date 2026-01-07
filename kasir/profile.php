<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$kasir_id = $_SESSION['user_id'];

// Get kasir details
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->execute(['id' => $kasir_id]);
$kasir = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = [
            'nama' => $_POST['nama'],
            'email' => $_POST['email'],
            'no_telp' => $_POST['no_telp'],
            'alamat' => $_POST['alamat'],
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
        $data['id'] = $kasir_id;
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($data)) {
            $_SESSION['nama'] = $data['nama'];
            $success = "Profile berhasil diperbarui";
        } else {
            $error = "Gagal memperbarui profile";
        }
    }
}

// Get performance statistics
$today = date('Y-m-d');
$this_month = date('Y-m');

// Today's performance
$query = "SELECT 
            COUNT(*) as transactions_today,
            SUM(grand_total) as revenue_today,
            AVG(grand_total) as avg_transaction_today
          FROM transaksi 
          WHERE DATE(tanggal) = :today 
          AND kasir_id = :kasir_id
          AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$today_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// This month's performance
$query = "SELECT 
            COUNT(*) as transactions_month,
            SUM(grand_total) as revenue_month,
            AVG(grand_total) as avg_transaction_month
          FROM transaksi 
          WHERE DATE_FORMAT(tanggal, '%Y-%m') = :month
          AND kasir_id = :kasir_id
          AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['month' => $this_month, 'kasir_id' => $kasir_id]);
$month_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Best selling day
$query = "SELECT 
            DATE(tanggal) as best_day,
            COUNT(*) as transaction_count,
            SUM(grand_total) as revenue
          FROM transaksi 
          WHERE kasir_id = :kasir_id
          AND status IN ('completed', 'verified')
          GROUP BY DATE(tanggal)
          ORDER BY revenue DESC
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $kasir_id]);
$best_day = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Kasir - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same base styles as dashboard */
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
            padding-bottom: 16px;
            border-bottom: 1px solid #F1F5F9;
        }
        
        /* Profile Header */
        .profile-header {
            display: flex;
            gap: 32px;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #3B82F6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
        }
        
        .profile-info h2 {
            margin-bottom: 8px;
            color: #1E293B;
        }
        
        .profile-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748B;
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
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Buttons */
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
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-title {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 4px;
        }
        
        .stat-subtitle {
            font-size: 12px;
            color: #94A3B8;
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
        
        /* Tabs */
        .profile-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            color: #64748B;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn:hover {
            background: #F1F5F9;
            color: #475569;
        }
        
        .tab-btn.active {
            background: #3B82F6;
            color: white;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
                    <a href="transaksi.php" class="nav-link">
                        <i class="fas fa-cash-register"></i>
                        Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php" class="nav-link active">
                        <i class="fas fa-user"></i>
                        Profile
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
                <h2>Profile Kasir</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: #64748B;">Kasir • <?php echo $_SESSION['shift'] ?? 'Shift'; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Profile Header -->
            <div class="content-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($kasir['nama'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($kasir['nama']); ?></h2>
                        <div style="color: #64748B; margin-bottom: 4px;">
                            <?php echo htmlspecialchars($kasir['user_code']); ?> • Kasir
                        </div>
                        <div style="color: #64748B;">Bergabung sejak <?php echo date('d M Y', strtotime($kasir['created_at'])); ?></div>
                        
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($kasir['email']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($kasir['no_telp']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo htmlspecialchars($kasir['shift'] ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Stats -->
            <div class="content-card">
                <h3>Statistik Performa</h3>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-title">Hari Ini</div>
                        <div class="stat-value"><?php echo number_format($today_stats['transactions_today'] ?? 0); ?></div>
                        <div class="stat-subtitle">Transaksi hari ini</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-title">Revenue Hari Ini</div>
                        <div class="stat-value"><?php echo format_rupiah($today_stats['revenue_today'] ?? 0); ?></div>
                        <div class="stat-subtitle">Pendapatan hari ini</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-title">Bulan Ini</div>
                        <div class="stat-value"><?php echo number_format($month_stats['transactions_month'] ?? 0); ?></div>
                        <div class="stat-subtitle">Transaksi bulan ini</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-title">Revenue Bulan Ini</div>
                        <div class="stat-value"><?php echo format_rupiah($month_stats['revenue_month'] ?? 0); ?></div>
                        <div class="stat-subtitle">Pendapatan bulan ini</div>
                    </div>
                </div>
                
                <?php if ($best_day): ?>
                <div style="background: #F8FAFC; border-radius: 12px; padding: 16px; margin-top: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #DBEAFE; color: #3B82F6; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Hari Terbaik</div>
                            <div style="color: #64748B; font-size: 14px;">
                                <?php echo date('d M Y', strtotime($best_day['best_day'])); ?> • 
                                <?php echo number_format($best_day['transaction_count']); ?> transaksi • 
                                <?php echo format_rupiah($best_day['revenue']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Edit Profile Form -->
            <div class="content-card">
                <h3>Edit Profile</h3>
                
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="nama">Nama Lengkap *</label>
                        <input type="text" id="nama" name="nama" class="form-control" 
                               value="<?php echo htmlspecialchars($kasir['nama']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($kasir['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="no_telp">No. Telepon *</label>
                        <input type="tel" id="no_telp" name="no_telp" class="form-control" 
                               value="<?php echo htmlspecialchars($kasir['no_telp']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea id="alamat" name="alamat" class="form-control"><?php echo htmlspecialchars($kasir['alamat'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password Baru (Kosongkan jika tidak diubah)</label>
                        <input type="password" id="password" name="password" class="form-control">
                        <div style="font-size: 12px; color: #94A3B8; margin-top: 4px;">
                            Minimal 6 karakter
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: flex-end; margin-top: 32px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>