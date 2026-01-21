<?php
require_once '../config/database.php';
require_once __DIR__ . '/functions.php'; // Fix: undefined function error

$trx_code = $_GET['trx'] ?? '';

$db = new Database();
$conn = $db->getConnection();

// Get store logo
$stmt_logo = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_logo'");
$stmt_logo->execute();
$logo_path = $stmt_logo->fetchColumn();

// Get transaction details
$query = "SELECT t.*, u.nama as customer_name, u.alamat, u.no_telp, 
          k.nama as kasir_name
          FROM transaksi t
          LEFT JOIN users u ON t.customer_id = u.id
          LEFT JOIN users k ON t.kasir_id = k.id
          WHERE t.kode_transaksi = :kode";
$stmt = $conn->prepare($query);
$stmt->execute([':kode' => $trx_code]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    die("Transaksi tidak ditemukan!");
}

// Get transaction items
$query_items = "SELECT dt.*, m.nama_kaos, v.warna, v.size 
                FROM detail_transaksi dt
                INNER JOIN kaos_varian v ON dt.kaos_id = v.id
                INNER JOIN kaos_master m ON v.kaos_master_id = m.id
                WHERE dt.transaksi_id = :trx_id";
$stmt_items = $conn->prepare($query_items);
$stmt_items->execute([':trx_id' => $transaksi['id']]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo $transaksi['kode_transaksi']; ?></title>
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
            padding: 40px 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Green Theme (Emerald) */
        .invoice-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header-bg-icon {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 150px;
            opacity: 0.1;
            color: white;
            transform: rotate(15deg);
        }

        .invoice-logo-img {
            max-height: 80px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .invoice-header h1 {
            font-size: 32px;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .invoice-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .invoice-body {
            padding: 40px;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #F1F5F9;
        }
        
        .info-section h3 {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-section p {
            margin-bottom: 6px;
            color: #334155;
        }
        
        .info-section strong {
            color: #1E293B;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        
        .invoice-table thead {
            background: #F8FAFC;
        }
        
        .invoice-table th {
            padding: 12px;
            text-align: left;
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .invoice-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
        }
        
        .invoice-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .invoice-summary {
            margin-left: auto;
            width: 350px;
            background: #F8FAFC;
            padding: 24px;
            border-radius: 12px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
        }
        
        .summary-row.total {
            border-top: 2px solid #E2E8F0;
            margin-top: 12px;
            padding-top: 16px;
            font-size: 20px;
            font-weight: 700;
            color: #1E293B;
        }
        
        .invoice-footer {
            text-align: center;
            padding: 30px 40px;
            background: #F8FAFC;
            color: #64748B;
            font-size: 13px;
        }
        
        .invoice-footer strong {
            color: #1E293B;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .status-success {
            background: #D1FAE5;
            color: #059669;
        }
        
        .status-warning {
            background: #FEF3C7;
            color: #D97706;
        }
        
        /* Updated Button Color */
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 16px 32px;
            background: #10B981; /* Emerald */
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
            transition: all 0.3s;
        }
        
        .print-button:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <!-- Background Icon Decoration -->
            <i class="fas fa-layer-group header-bg-icon"></i>
            
            <?php 
            // Use DB logo if valid, otherwise use layer.png as requested
            $logo_src = ($logo_path && file_exists('../' . $logo_path)) ? '../' . $logo_path : '../assets/img/layer.png';
            ?>
            
            <div style="display: flex; justify-content: center; margin-bottom: 15px;">
                <img src="<?php echo $logo_src; ?>" alt="Logo" class="invoice-logo-img" onerror="this.src='../assets/img/layer.png'">
            </div>
            
            <h1>INVOICE</h1>
            <p style="letter-spacing: 2px; font-weight: 500;">No. <?php echo $transaksi['kode_transaksi']; ?></p>
        </div>
        
        <!-- Body -->
        <div class="invoice-body">
            <!-- Info Section -->
            <div class="invoice-info">
                <div class="info-section">
                    <h3>Dari</h3>
                    <p><strong>DistroZone</strong></p>
                    <p>Jln. Raya Pegangsaan Timur No.29H</p>
                    <p>Kelapa Gading, Jakarta</p>
                    <p>Telp: +62 812-3456-7890</p>
                </div>
                
                <div class="info-section">
                    <h3>Kepada</h3>
                    <p><strong><?php echo $transaksi['customer_name'] ?: 'Walk-in Customer'; ?></strong></p>
                    <?php if($transaksi['alamat']): ?>
                        <p><?php echo $transaksi['alamat']; ?></p>
                    <?php endif; ?>
                    <?php if($transaksi['no_telp']): ?>
                        <p>Telp: <?php echo $transaksi['no_telp']; ?></p>
                    <?php endif; ?>
                    <p>Tanggal: <?php echo format_datetime($transaksi['created_at']); ?></p>
                    
                    <?php if($transaksi['status'] == 'completed' || $transaksi['status'] == 'verified'): ?>
                        <span class="status-badge status-success">LUNAS</span>
                    <?php else: ?>
                        <span class="status-badge status-warning">PENDING</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th>Spesifikasi</th>
                        <th class="text-right">Harga</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach($items as $item): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><strong><?php echo $item['nama_kaos']; ?></strong></td>
                        <td><?php echo $item['warna']; ?> - Size <?php echo $item['size']; ?></td>
                        <td class="text-right"><?php echo format_rupiah($item['harga_jual']); ?></td>
                        <td class="text-right"><?php echo $item['qty']; ?></td>
                        <td class="text-right"><strong><?php echo format_rupiah($item['subtotal']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="invoice-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span><?php echo format_rupiah($transaksi['total']); ?></span>
                </div>
                
                <?php if($transaksi['shipping_cost'] > 0): ?>
                <div class="summary-row">
                    <span>Ongkir (<?php echo $transaksi['shipping_city']; ?>):</span>
                    <span><?php echo format_rupiah($transaksi['shipping_cost']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="summary-row total">
                    <span>TOTAL:</span>
                    <span><?php echo format_rupiah($transaksi['grand_total']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong>Metode Pembayaran:</strong> <?php echo strtoupper($transaksi['payment_method']); ?></p>
            <?php if($transaksi['kasir_name']): ?>
                <p style="margin-top: 8px;">Dilayani oleh: <?php echo $transaksi['kasir_name']; ?></p>
            <?php endif; ?>
            <p style="margin-top: 20px;">Terima kasih atas pembelian Anda!</p>
            <p>Untuk pertanyaan, hubungi kami di info@distrozone.com</p>
        </div>
    </div>
    
    <button class="print-button" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak Invoice
    </button>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>