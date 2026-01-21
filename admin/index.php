<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Get statistics
$stats = [];

// Total Revenue
$query = "SELECT SUM(grand_total) as total FROM transaksi WHERE status IN ('completed', 'verified')";
$stmt = $conn->query($query);
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Profit
$query = "SELECT SUM(laba) as total FROM detail_transaksi 
          INNER JOIN transaksi ON detail_transaksi.transaksi_id = transaksi.id 
          WHERE transaksi.status IN ('completed', 'verified')";
$stmt = $conn->query($query);
$stats['profit'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total Transactions
$query = "SELECT COUNT(*) as total FROM transaksi WHERE status IN ('completed', 'verified')";
$stmt = $conn->query($query);
$stats['transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Low Stock Products
$query = "SELECT COUNT(*) as total FROM kaos_varian WHERE stok < 10";
$stmt = $conn->query($query);
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Pending Verifications
$query = "SELECT COUNT(*) as total FROM payment_proof WHERE status = 'pending'";
$stmt = $conn->query($query);
$stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Recent transactions
$query = "SELECT t.*, u.nama as customer_name 
          FROM transaksi t 
          LEFT JOIN users u ON t.customer_id = u.id 
          ORDER BY t.created_at DESC LIMIT 10";
$stmt = $conn->query($query);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chart Data: Sales per day (Last 7 Days)
$query = "SELECT DATE(tanggal) as date, SUM(grand_total) as total 
          FROM transaksi 
          WHERE status IN ('completed', 'verified') 
          AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY DATE(tanggal)
          ORDER BY DATE(tanggal) ASC";
$stmt = $conn->query($query);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = [];
$sales = [];
foreach ($daily_sales as $day) {
    $dates[] = date('d M', strtotime($day['date']));
    $sales[] = $day['total'];
}

// Chart Data: Top 5 Categories
$query = "SELECT c.nama_kategori, COUNT(dt.id) as total_sold
          FROM detail_transaksi dt
          JOIN kaos_varian v ON dt.kaos_id = v.id
          JOIN kaos_master k ON v.kaos_master_id = k.id
          JOIN kategori c ON k.kategori_id = c.id
          JOIN transaksi t ON dt.transaksi_id = t.id
          WHERE t.status IN ('completed', 'verified')
          GROUP BY c.id
          ORDER BY total_sold DESC LIMIT 5";
$stmt = $conn->query($query);
$top_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cat_names = [];
$cat_totals = [];
foreach ($top_categories as $cat) {
    $cat_names[] = $cat['nama_kategori'];
    $cat_totals[] = $cat['total_sold'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - DistroZone</title>
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
        .icon-red { background: #FEE2E2; color: #EF4444; }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
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
            <div class="logo" style="display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-layer-group" style="font-size: 24px; color: #10B981;"></i>
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
                    <a href="karyawan.php" class="nav-link">
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
                <h2>Dashboard Overview</h2>
                <div class="user-info">
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: var(--text-dark);"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--primary);">Administrator</div>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Total Omzet</div>
                        <div class="stat-card-icon icon-blue">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['revenue']); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Total Laba</div>
                        <div class="stat-card-icon icon-green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($stats['profit']); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Total Transaksi</div>
                        <div class="stat-card-icon icon-orange">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['transactions']); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-title">Stok Menipis</div>
                        <div class="stat-card-icon icon-red">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo $stats['low_stock']; ?></div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 32px;">
                <!-- Sales Chart -->
                <div class="content-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <h3>Grafik Penjualan 7 Hari Terakhir</h3>
                    </div>
                    <div>
                        <canvas id="salesChart" height="150"></canvas>
                    </div>
                </div>
                
                <!-- Category Chart -->
                <div class="content-card">
                    <h3>Kategori Terlaris</h3>
                    <div style="position: relative; height: 250px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
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
                            <?php foreach($recent_transactions as $trx): ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: var(--primary);"><?php echo $trx['kode_transaksi']; ?></span>
                                </td>
                                <td><?php echo $trx['customer_name'] ?? 'Guest'; ?></td>
                                <td><?php echo format_datetime($trx['created_at']); ?></td>
                                <td style="font-weight: 600;"><?php echo format_rupiah($trx['grand_total']); ?></td>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sales Chart
        const ctxSales = document.getElementById('salesChart').getContext('2d');
        new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?php echo json_encode($sales); ?>,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#FFFFFF',
                    pointBorderColor: '#10B981',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#F1F5F9'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Category Chart
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($cat_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($cat_totals); ?>,
                    backgroundColor: [
                        '#10B981',
                        '#3B82F6',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>