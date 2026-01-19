<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_admin();

$db = new Database();
$conn = $db->getConnection();

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Filter clause for queries - only include verified/completed transactions
$date_filter = "WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date' AND t.status IN ('verified', 'completed')";

// 1. Summary Statistics
$query = "SELECT 
            COUNT(DISTINCT t.id) as total_trx,
            SUM(t.grand_total) as total_sales,
            SUM(d.qty) as total_items
          FROM transaksi t
          JOIN detail_transaksi d ON t.id = d.transaksi_id
          $date_filter";
$stmt = $conn->query($query);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate profit (Total Sales - Total Modal)
$query = "SELECT 
            SUM(d.qty * k.harga_pokok) as total_modal
          FROM transaksi t
          JOIN detail_transaksi d ON t.id = d.transaksi_id
          JOIN kaos_varian k ON d.kaos_id = k.id
          $date_filter";
$stmt = $conn->query($query);
$total_modal = $stmt->fetch(PDO::FETCH_ASSOC)['total_modal'];
$total_profit = ($summary['total_sales'] ?? 0) - ($total_modal ?? 0);

// 2. Top Selling Products
$query = "SELECT 
            m.nama_kaos, 
            v.kode_varian as kode_kaos,
            SUM(d.qty) as total_sold,
            SUM(d.subtotal) as total_revenue,
            SUM(d.subtotal - (d.qty * v.harga_pokok)) as total_profit
          FROM transaksi t
          JOIN detail_transaksi d ON t.id = d.transaksi_id
          JOIN kaos_varian v ON d.kaos_id = v.id
          JOIN kaos_master m ON v.kaos_master_id = m.id
          $date_filter
          GROUP BY v.id
          ORDER BY total_sold DESC
          LIMIT 5";
$stmt = $conn->query($query);
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Daily Sales Chart Data
$query = "SELECT 
            DATE(t.created_at) as date,
            SUM(t.grand_total) as daily_sales
          FROM transaksi t
          $date_filter
          GROUP BY DATE(t.created_at)
          ORDER BY date ASC";
$stmt = $conn->query($query);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare Chart Data
$chart_labels = [];
$chart_data = [];
foreach ($daily_sales as $day) {
    $chart_labels[] = date('d M', strtotime($day['date']));
    $chart_data[] = $day['daily_sales'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - DistroZone</title>
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

        /* Stats Cards */
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
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-title {
            color: var(--text-light);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(255,255,255,0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .date-input {
            padding: 10px 16px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            font-family: 'Outfit', sans-serif;
            background: white;
            color: var(--text-dark);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
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
        
        .btn-outline {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(16, 185, 129, 0.05);
        }
        
        /* Charts & Tables */
        .chart-container {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
            border: 1px solid rgba(255,255,255,0.5);
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
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        tbody tr:hover {
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        /* Print Styles */
        @media print {
            .sidebar, .top-bar, .filter-section, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            body {
                background: white;
            }
            .stat-card, .chart-container {
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
                    <a href="kaos.php" class="nav-link">
                        <i class="fas fa-tshirt"></i>
                        Kelola Kaos
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link active">
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
                <h2>Laporan Penjualan</h2>
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
            
            <!-- Filter Section -->
            <form class="filter-section">
                <div class="date-filter">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--text-light); font-size: 14px;">Dari:</span>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="date-input">
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: var(--text-light); font-size: 14px;">Sampai:</span>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="date-input">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
                
                <div class="no-print">
                    <button type="button" class="btn btn-outline" onclick="window.print()">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                    <!-- <button type="button" class="btn btn-outline" style="margin-left: 8px;">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button> -->
                </div>
            </form>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="stat-title">Total Transaksi</div>
                    <div class="stat-value"><?php echo number_format($summary['total_trx'] ?? 0); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3B82F6;">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                    <div class="stat-title">Produk Terjual</div>
                    <div class="stat-value"><?php echo number_format($summary['total_items'] ?? 0); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="stat-title">Total Pendapatan</div>
                    <div class="stat-value" style="font-size: 24px;"><?php echo format_rupiah($summary['total_sales'] ?? 0); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-title">Total Keuntungan</div>
                    <div class="stat-value" style="font-size: 24px;"><?php echo format_rupiah($total_profit); ?></div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="chart-container">
                <h3 style="margin-bottom: 24px; color: var(--text-dark);">Grafik Penjualan Harian</h3>
                <canvas id="salesChart" height="100"></canvas>
            </div>
            
            <!-- Top Products Table -->
            <div class="chart-container">
                <h3 style="margin-bottom: 24px; color: var(--text-dark);">5 Produk Terlaris</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th>Terjual</th>
                            <th>Pendapatan</th>
                            <th>Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: var(--text-light);">Belum ada data penjualan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['kode_kaos']); ?></td>
                                <td style="font-weight: 500;"><?php echo htmlspecialchars($product['nama_kaos']); ?></td>
                                <td><?php echo number_format($product['total_sold']); ?> pcs</td>
                                <td><?php echo format_rupiah($product['total_revenue']); ?></td>
                                <td style="font-weight: 600; color: #10B981;"><?php echo format_rupiah($product['total_profit']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
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
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumSignificantDigits: 3 }).format(value);
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
    </script>
</body>
</html>