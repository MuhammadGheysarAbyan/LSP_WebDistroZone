<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$kasir_id = $_SESSION['user_id'];

// Set default date range (this month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get sales summary for this kasir (web transactions only)
$query = "SELECT 
            COUNT(*) as total_transactions,
            SUM(grand_total) as total_revenue,
            SUM(grand_total - shipping_cost) as total_sales
          FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Kasir tidak bisa melihat profit - data ini hanya untuk admin
// Query profit dihapus untuk kasir

// Get top selling products for this kasir
        $query = "SELECT 
            km.nama_kaos,
            km.merek,
            kv.kode_varian as kode_kaos,
            SUM(dt.qty) as total_sold,
            SUM(dt.subtotal) as total_revenue
          FROM detail_transaksi dt
          INNER JOIN kaos_varian kv ON dt.kaos_id = kv.id
          INNER JOIN kaos_master km ON kv.kaos_master_id = km.id
          INNER JOIN transaksi t ON dt.transaksi_id = t.id
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status = 'completed'
          GROUP BY km.id
          ORDER BY total_sold DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales by day for this kasir
$query = "SELECT 
            DATE(tanggal) as date,
            COUNT(*) as transactions,
            SUM(grand_total) as revenue
          FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status = 'completed'
          GROUP BY DATE(tanggal)
          ORDER BY date";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart
$chart_labels = [];
$chart_data = [];
foreach ($daily_sales as $day) {
    $chart_labels[] = date('d M', strtotime($day['date']));
    $chart_data[] = $day['revenue'];
}
// Get payment method breakdown
$query = "SELECT 
            payment_method,
            COUNT(*) as trx_count,
            SUM(grand_total) as method_revenue
          FROM transaksi t
          LEFT JOIN payment_proof p ON t.id = p.transaksi_id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND (t.kasir_id = :kasir_id OR p.verified_by = :kasir_id)
          AND t.status IN ('completed', 'verified', 'selesai', 'paid', 'sent')
          GROUP BY payment_method
          ORDER BY method_revenue DESC";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kasir - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        
        /* Date Filter */
        .date-filter {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(255,255,255,0.5);
        }
        
        .filter-form {
            display: flex;
            gap: 16px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1;
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
        
        .btn-success {
            background: #10B981;
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border: 1px solid rgba(255,255,255,0.5);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-card-title {
            color: var(--text-light);
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
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 4px;
        }
        
        .stat-card-subtitle {
            font-size: 12px;
            color: var(--text-light);
        }
        
        /* Chart Container */
        .chart-container {
            height: 300px;
            margin-bottom: 24px;
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
        
        tbody tr:hover {
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            justify-content: flex-end;
        }
        
        @media print {
            .sidebar, .top-bar, .date-filter, .export-buttons {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            body {
                background: white;
            }
            .stat-card, .content-card {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }
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
                <li class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-home"></i>Dashboard</a></li>
                <li class="nav-item"><a href="transaksi.php?view=recent" class="nav-link"><i class="fas fa-history"></i>Riwayat Transaksi</a></li>
                <li class="nav-item"><a href="verifikasi.php" class="nav-link"><i class="fas fa-check-circle"></i>Verifikasi</a></li>
                <li class="nav-item"><a href="laporan.php" class="nav-link active"><i class="fas fa-chart-line"></i>Laporan</a></li>
                <li class="nav-item"><a href="profile.php" class="nav-link"><i class="fas fa-user"></i>Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Laporan Penjualan</h2>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo $_SESSION['nama']; ?></div>
                        <div style="font-size: 12px; color: var(--text-light);">Kasir • <?php echo $_SESSION['shift'] ?? 'Shift'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Date Filter -->
            <div class="date-filter">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" 
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" 
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="form-group" style="max-width: 200px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Export Buttons -->
            <div class="export-buttons">
                <button class="btn btn-success" onclick="printReport()">
                    <i class="fas fa-print"></i>
                    Cetak Laporan
                </button>
            </div>
            
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Total Transaksi</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($summary['total_transactions'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">
                        Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Total Penjualan</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($summary['total_sales'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">Net sales (excluding shipping)</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Total Revenue</div>
                        </div>
                        <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($summary['total_revenue'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">Including shipping costs</div>
                </div>
            </div>
            
            <!-- Payment Method Stats -->
            <div class="content-card" style="margin-bottom: 24px;">
                <h3><i class="fas fa-wallet" style="color: var(--secondary); margin-right: 8px;"></i>Rincian Pembayaran</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <?php if(empty($payment_stats)): ?>
                        <div style="grid-column: 1 / -1; text-align: center; color: var(--text-light); padding: 20px;">
                            <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; opacity: 0.5;"></i><br>
                            Belum ada data pembayaran
                        </div>
                    <?php else: ?>
                        <?php foreach($payment_stats as $stat): ?>
                        <div style="background: #F8FAFC; padding: 16px; border-radius: 12px; border: 1px solid #E2E8F0; transition: transform 0.2s; cursor: default;" 
                             onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                <div style="font-size: 14px; color: var(--text-light); text-transform: uppercase; font-weight: 700;">
                                    <?php 
                                        $pm = htmlspecialchars($stat['payment_method'] ?: 'TUNAI'); 
                                        if (strpos(strtolower($pm), 'qris') !== false) echo '<i class="fas fa-qrcode" style="color: #6366F1; margin-right: 6px;"></i>' . $pm;
                                        elseif (strpos(strtolower($pm), 'tunai') !== false || strpos(strtolower($pm), 'cash') !== false) echo '<i class="fas fa-money-bill" style="color: #10B981; margin-right: 6px;"></i>' . $pm;
                                        elseif (strpos(strtolower($pm), 'bca') !== false || strpos(strtolower($pm), 'transfer') !== false) echo '<i class="fas fa-university" style="color: #3B82F6; margin-right: 6px;"></i>' . $pm;
                                        else echo '<i class="fas fa-credit-card" style="margin-right: 6px;"></i>' . $pm;
                                    ?>
                                </div>
                                <span style="background: rgba(0,0,0,0.05); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                    <?php echo $stat['trx_count']; ?> Trx
                                </span>
                            </div>
                            <div style="font-size: 20px; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px;">
                                <?php echo format_rupiah($stat['method_revenue']); ?>
                            </div>
                            <div style="margin-top: 8px; height: 4px; background: #E2E8F0; border-radius: 2px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo ($stat['method_revenue'] / ($summary['total_revenue'] ?: 1) * 100); ?>%; background: var(--primary); border-radius: 2px;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sales Chart -->
            <div class="content-card">
                <h3>Grafik Penjualan Harian</h3>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Top Products -->
            <div class="content-card">
                <h3>Produk Terlaris Anda</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th>Terjual</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-light);">
                                    <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 16px; display: block; opacity: 0.5;"></i>
                                    Tidak ada data penjualan pada periode ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-light);"><?php echo htmlspecialchars($product['merek']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($product['kode_kaos']); ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo number_format($product['total_sold']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-light);">pcs</div>
                                </td>
                                <td><?php echo format_rupiah($product['total_revenue']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Penjualan',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10B981',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                         mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1F2937',
                        bodyColor: '#10B981',
                        borderColor: '#E2E8F0',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                         grid: {
                            borderDash: [2, 4],
                            color: '#E2E8F0'
                        },
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp' + (value / 1000000).toFixed(1) + 'jt';
                                }
                                return 'Rp' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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
        
        // Print report
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>