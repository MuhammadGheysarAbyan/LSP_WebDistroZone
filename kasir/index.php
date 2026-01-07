<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$kasir_id = $_SESSION['user_id'];

// Get statistics for today
$today = date('Y-m-d');
$stats = [];

// Today's Revenue
$query = "SELECT SUM(grand_total) as total FROM transaksi 
          WHERE DATE(tanggal) = :today AND kasir_id = :kasir_id 
          AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Today's Transactions
$query = "SELECT COUNT(*) as total FROM transaksi 
          WHERE DATE(tanggal) = :today AND kasir_id = :kasir_id 
          AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Today's Profit
$query = "SELECT SUM(dt.laba) as total FROM detail_transaksi dt
          INNER JOIN transaksi t ON dt.transaksi_id = t.id
          WHERE DATE(t.tanggal) = :today AND t.kasir_id = :kasir_id 
          AND t.status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_profit'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Transactions (All Time)
$query = "SELECT COUNT(*) as total FROM transaksi 
          WHERE kasir_id = :kasir_id AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $kasir_id]);
$stats['total_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent transactions by this kasir
$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t 
          LEFT JOIN users u ON t.customer_id = u.id 
          WHERE t.kasir_id = :kasir_id 
          ORDER BY t.created_at DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $kasir_id]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-card-title {
            color: #64748B;
            font-size: 14px;
            font-weight: 500;
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .icon-blue { background: #DBEAFE; color: #3B82F6; }
        .icon-green { background: #D1FAE5; color: #10B981; }
        .icon-orange { background: #FEF3C7; color: #F59E0B; }
        .icon-purple { background: #E9D5FF; color: #8B5CF6; }
        
        .stat-card-value {
            font-size: 32px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 4px;
        }
        
        .stat-card-subtitle {
            font-size: 12px;
            color: #94A3B8;
        }
        
        /* Content Card */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .content-card h3 {
            margin-bottom: 20px;
            color: #1E293B;
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
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .action-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            background: #3B82F6;
            color: white;
        }
        
        .action-card:hover .action-icon {
            background: rgba(255,255,255,0.2);
        }
        
        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: #DBEAFE;
            color: #3B82F6;
        }
        
        .action-text h4 {
            margin-bottom: 4px;
            font-size: 16px;
        }
        
        .action-text p {
            font-size: 12px;
            opacity: 0.8;
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
                    <a href="index.php" class="nav-link active">
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
                    <a href="profile.php" class="nav-link">
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
                <h2>Dashboard Kasir</h2>
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
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="transaksi.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div class="action-text">
                        <h4>Transaksi Baru</h4>
                        <p>Buat transaksi penjualan baru</p>
                    </div>
                </a>
                
                <a href="transaksi.php?view=recent" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="action-text">
                        <h4>Riwayat Transaksi</h4>
                        <p>Lihat transaksi terbaru</p>
                    </div>
                </a>
                
                <a href="../customer/shop.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <div class="action-text">
                        <h4>Lihat Produk</h4>
                        <p>Lihat stok kaos tersedia</p>
                    </div>
                </a>
                
                <a href="laporan.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="action-text">
                        <h4>Laporan Harian</h4>
                        <p>Lihat laporan penjualan</p>
                    </div>
                </a>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Omzet Hari Ini</div>
                        </div>
                        <div class="stat-card-icon icon-blue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['today_revenue']); ?></div>
                    <div class="stat-card-subtitle">Total pendapatan hari ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Transaksi Hari Ini</div>
                        </div>
                        <div class="stat-card-icon icon-green">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['today_transactions']); ?></div>
                    <div class="stat-card-subtitle">Total transaksi hari ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Laba Hari Ini</div>
                        </div>
                        <div class="stat-card-icon icon-orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['today_profit']); ?></div>
                    <div class="stat-card-subtitle">Total laba hari ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Total Transaksi</div>
                        </div>
                        <div class="stat-card-icon icon-purple">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_transactions']); ?></div>
                    <div class="stat-card-subtitle">Seluruh transaksi Anda</div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="content-card">
                <h3>Transaksi Terakhir</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_transactions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #94A3B8;">
                                    <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    Belum ada transaksi
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_transactions as $trx): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($trx['kode_transaksi']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;"><?php echo $trx['waktu']; ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($trx['customer_name'] ?? 'Guest'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($trx['tanggal'])); ?></td>
                                <td><?php echo format_rupiah($trx['grand_total']); ?></td>
                                <td>
                                    <?php if($trx['status'] == 'completed' || $trx['status'] == 'verified'): ?>
                                        <span class="badge badge-success">Selesai</span>
                                    <?php elseif($trx['status'] == 'pending'): ?>
                                        <span class="badge badge-warning">Menunggu</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Dibatalkan</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>