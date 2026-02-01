<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$kasir_id = $_SESSION['user_id'];

// Auto-complete web orders after estimated delivery days
// This runs when kasir opens dashboard to ensure consistent status updates
// Auto-complete web orders after estimated delivery days
// This runs when kasir opens dashboard to ensure consistent status updates
include_once '../auto_complete_orders.php';

// Get statistics for today
$today = date('Y-m-d');
$stats = [];

// Today's Revenue
$query = "SELECT SUM(t.grand_total) as total FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) = :today 
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Today's Transactions
$query = "SELECT COUNT(*) as total FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) = :today 
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Today's Profit
$query = "SELECT SUM(dt.laba) as total FROM detail_transaksi dt
          INNER JOIN transaksi t ON dt.transaksi_id = t.id
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) = :today 
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute(['today' => $today, 'kasir_id' => $kasir_id]);
$stats['today_profit'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Transactions (All Time)
$query = "SELECT COUNT(*) as total FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute(['kasir_id' => $kasir_id]);
$stats['total_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent transactions by this kasir
$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t 
          LEFT JOIN users u ON t.customer_id = u.id 
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #10B981;
            --primary-dark: #047857;
            --secondary: #0F766E;
            --bg-color: #ECFDF5;
            --text-dark: #1F2937;
            --text-light: #64748B;
            --white: #FFFFFF;
            --sidebar-bg: #FFFFFF;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-color);
            color: var(--text-dark);
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            padding: 24px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            box-shadow: 4px 0 24px rgba(0,0,0,0.02);
        }
        
        .logo {
            padding: 0 24px 24px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E2E8F0;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 800;
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
            padding: 14px 16px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
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
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255,255,255,0.6);
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
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.6);
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(16, 185, 129, 0.2);
            background: white;
            border-color: var(--primary);
        }
        
        .action-card:hover .action-icon {
            background: var(--primary);
            color: white;
        }
        
        .action-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            background: #E0F2FE;
            color: var(--primary);
            transition: all 0.3s;
        }
        
        .action-text h4 {
            margin-bottom: 4px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .action-text p {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid rgba(255,255,255,0.6);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        
        .stat-card-title {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        
        .icon-blue { background: #E0F2FE; color: #0EA5E9; }
        .icon-green { background: #D1FAE5; color: #10B981; }
        .icon-orange { background: #FEF3C7; color: #F59E0B; }
        .icon-purple { background: #E9D5FF; color: #8B5CF6; }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .stat-card-subtitle {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Table */
        .content-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255,255,255,0.6);
        }
        
        .content-card h3 {
            margin-bottom: 24px;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        thead th {
            background: #F8FAFC;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 14px;
            border-bottom: 2px solid #E2E8F0;
        }
        
        thead th:first-child { border-top-left-radius: 12px; }
        thead th:last-child { border-top-right-radius: 12px; }
        
        tbody td {
            padding: 20px 16px;
            border-bottom: 1px solid #F1F5F9;
            color: var(--text-dark);
            font-size: 15px;
        }
        
        tbody tr:last-child td { border-bottom: none; }
        
        tbody tr:hover {
            background: #F8FAFC;
        }
        
        .badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        
        .badge-success { background: #D1FAE5; color: #059669; }
        .badge-warning { background: #FEF3C7; color: #D97706; }
        .badge-danger { background: #FEE2E2; color: #DC2626; }
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
                <li class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-home"></i>Dashboard</a></li>
                <li class="nav-item"><a href="transaksi.php?view=recent" class="nav-link"><i class="fas fa-history"></i>Riwayat Transaksi</a></li>
                <li class="nav-item"><a href="verifikasi.php" class="nav-link"><i class="fas fa-check-circle"></i>Verifikasi</a></li>
                <li class="nav-item"><a href="laporan.php" class="nav-link"><i class="fas fa-chart-line"></i>Laporan</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i>Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Dashboard Overview</h2>
                <div class="user-info">
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--text-dark);"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--primary);">Kasir • <?php echo $_SESSION['shift'] ?? 'On Duty'; ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
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
                        <p>Cek stok kaos yang tersedia</p>
                    </div>
                </a>
                
                <a href="laporan.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="action-text">
                        <h4>Laporan Harian</h4>
                        <p>Rekap penjualan hari ini</p>
                    </div>
                </a>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Omzet Hari Ini</div>
                        <div class="stat-card-icon icon-blue">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['today_revenue']); ?></div>
                    <div class="stat-card-subtitle">Total pendapatan hari ini</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Transaksi Hari Ini</div>
                        <div class="stat-card-icon icon-green">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['today_transactions']); ?></div>
                    <div class="stat-card-subtitle">Total transaksi sukses</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Laba Hari Ini</div>
                        <div class="stat-card-icon icon-orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['today_profit']); ?></div>
                    <div class="stat-card-subtitle">Keuntungan bersih</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Total Transaksi</div>
                        <div class="stat-card-icon icon-purple">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_transactions']); ?></div>
                    <div class="stat-card-subtitle">Sejak awal bergabung</div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="content-card">
                <h3>Transaksi Terakhir</h3>
                <div style="overflow-x: auto;">
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
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-light);">
                                        <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.3;"></i>
                                        Belum ada transaksi hari ini
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $trx): ?>
                                <tr>
                                    <td>
                                        <span style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($trx['kode_transaksi']); ?></span>
                                        <div style="font-size: 12px; color: var(--text-light);"><?php echo $trx['waktu']; ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($trx['customer_name'] ?? 'Guest'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($trx['tanggal'])); ?></td>
                                    <td style="font-weight: 600;"><?php echo format_rupiah($trx['grand_total']); ?></td>
                                    <td>
                                        <?php if($trx['status'] == 'completed'): ?>
                                            <span class="badge badge-success">Selesai</span>
                                        <?php elseif($trx['status'] == 'pending'): ?>
                                            <span class="badge badge-warning">Menunggu</span>
                                        <?php elseif($trx['status'] == 'cancelled'): ?>
                                            <span class="badge badge-danger">Dibatalkan</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?php echo $trx['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>