<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

check_kasir();

$db = new Database();
$conn = $db->getConnection();

$kasir_id = $_SESSION['user_id'];

// Set default date range (this month)
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Get sales summary for this kasir
$query = "SELECT 
            COUNT(*) as total_transactions,
            SUM(grand_total) as total_revenue,
            SUM(grand_total - shipping_cost) as total_sales
          FROM transaksi 
          WHERE DATE(tanggal) BETWEEN :start_date AND :end_date
          AND kasir_id = :kasir_id
          AND status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get profit summary for this kasir
$query = "SELECT 
            SUM(dt.laba) as total_profit,
            SUM(dt.harga_jual * dt.qty) as total_sales_value,
            SUM(dt.harga_modal * dt.qty) as total_cost
          FROM detail_transaksi dt
          INNER JOIN transaksi t ON dt.transaksi_id = t.id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND t.kasir_id = :kasir_id
          AND t.status IN ('completed', 'verified')";
$stmt = $conn->prepare($query);
$stmt->execute([
    'start_date' => $start_date, 
    'end_date' => $end_date,
    'kasir_id' => $kasir_id
]);
$profit_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get top selling products for this kasir
$query = "SELECT 
            k.nama_kaos,
            k.merek,
            k.kode_kaos,
            SUM(dt.qty) as total_sold,
            SUM(dt.subtotal) as total_revenue,
            SUM(dt.laba) as total_profit
          FROM detail_transaksi dt
          INNER JOIN kaos k ON dt.kaos_id = k.id
          INNER JOIN transaksi t ON dt.transaksi_id = t.id
          WHERE DATE(t.tanggal) BETWEEN :start_date AND :end_date
          AND t.kasir_id = :kasir_id
          AND t.status IN ('completed', 'verified')
          GROUP BY k.id
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
          FROM transaksi
          WHERE DATE(tanggal) BETWEEN :start_date AND :end_date
          AND kasir_id = :kasir_id
          AND status IN ('completed', 'verified')
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kasir - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }
        
        /* Date Filter */
        .date-filter {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        
        /* Stats Grid */
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
        
        /* Chart Container */
        .chart-container {
            height: 300px;
            margin-bottom: 24px;
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
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            justify-content: flex-end;
        }
        
        .btn-success {
            background: #10B981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
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
                    <a href="laporan.php" class="nav-link active">
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
                <h2>Laporan Penjualan</h2>
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
                        <div class="stat-card-icon icon-blue">
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
                        <div class="stat-card-icon icon-green">
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
                        <div class="stat-card-icon icon-orange">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($summary['total_revenue'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">Including shipping costs</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-title">Total Laba</div>
                        </div>
                        <div class="stat-card-icon icon-purple">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                    </div>
                    <div class="stat-card-value"><?php echo format_rupiah($profit_summary['total_profit'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">
                        Profit margin: 
                        <?php 
                            if ($profit_summary['total_sales_value'] > 0) {
                                $margin = ($profit_summary['total_profit'] / $profit_summary['total_sales_value']) * 100;
                                echo number_format($margin, 1) . '%';
                            } else {
                                echo '0%';
                            }
                        ?>
                    </div>
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
                            <th>Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: #94A3B8;">
                                    <i class="fas fa-chart-bar" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    Tidak ada data penjualan pada periode ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($product['nama_kaos']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;"><?php echo htmlspecialchars($product['merek']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($product['kode_kaos']); ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo number_format($product['total_sold']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">pcs</div>
                                </td>
                                <td><?php echo format_rupiah($product['total_revenue']); ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #059669;"><?php echo format_rupiah($product['total_profit']); ?></div>
                                    <div style="font-size: 12px; color: #94A3B8;">
                                        <?php 
                                            if ($product['total_revenue'] > 0) {
                                                $margin = ($product['total_profit'] / $product['total_revenue']) * 100;
                                                echo number_format($margin, 1) . '% margin';
                                            }
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Profit Details -->
            <div class="content-card">
                <h3>Detail Laba Rugi</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th>Jumlah</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Total Nilai Penjualan</td>
                            <td><?php echo format_rupiah($profit_summary['total_sales_value'] ?? 0); ?></td>
                            <td>100%</td>
                        </tr>
                        <tr>
                            <td>Total Biaya Pokok</td>
                            <td><?php echo format_rupiah($profit_summary['total_cost'] ?? 0); ?></td>
                            <td>
                                <?php 
                                    if ($profit_summary['total_sales_value'] > 0) {
                                        $cost_percentage = ($profit_summary['total_cost'] / $profit_summary['total_sales_value']) * 100;
                                        echo number_format($cost_percentage, 1) . '%';
                                    } else {
                                        echo '0%';
                                    }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Total Laba Kotor</td>
                            <td style="font-weight: 600; color: #059669;"><?php echo format_rupiah($profit_summary['total_profit'] ?? 0); ?></td>
                            <td style="font-weight: 600; color: #059669;">
                                <?php 
                                    if ($profit_summary['total_sales_value'] > 0) {
                                        $profit_percentage = ($profit_summary['total_profit'] / $profit_summary['total_sales_value']) * 100;
                                        echo number_format($profit_percentage, 1) . '%';
                                    } else {
                                        echo '0%';
                                    }
                                ?>
                            </td>
                        </tr>
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
                    label: 'Penjualan (Rp)',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('id-ID', {
                                    style: 'currency',
                                    currency: 'IDR',
                                    minimumFractionDigits: 0
                                }).format(context.parsed.y);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp' + (value / 1000000).toFixed(1) + 'jt';
                                }
                                return 'Rp' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    }
                }
            }
        });
        
        // Print report
        function printReport() {
            window.print();
        }
        
        // Auto update chart on filter
        document.querySelector('.filter-form').addEventListener('submit', function(e) {
            // Chart will update when page reloads with new data
        });
    </script>
</body>
</html>