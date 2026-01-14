<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

check_customer();

$db = new Database();
$conn = $db->getConnection();

$trx_code = isset($_GET['trx']) ? clean_input($_GET['trx']) : '';

if (empty($trx_code)) {
    header("Location: orders.php");
    exit();
}

// Get transaction details
$query = "SELECT * FROM transaksi WHERE kode_transaksi = :code AND customer_id = :uid";
$stmt = $conn->prepare($query);
$stmt->execute([':code' => $trx_code, ':uid' => $_SESSION['user_id']]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: orders.php");
    exit();
}

// Handle File Upload
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['payment_proof'])) {
    $target_dir = "../assets/uploads/payments/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $upload = upload_file($_FILES['payment_proof'], $target_dir);
    
    if ($upload['success']) {
        try {
            // Insert to payment_proof
            $query_proof = "INSERT INTO payment_proof (transaksi_id, file_bukti, status, tanggal_upload) 
                           VALUES (:trx_id, :path, 'pending', NOW())";
            $stmt_proof = $conn->prepare($query_proof);
            $stmt_proof->execute([
                ':trx_id' => $transaction['id'],
                ':path' => 'assets/uploads/payments/' . $upload['filename']
            ]);
            
            // Optional: Update transaction status or just notify admin?
            // For now, let's keep transaction as 'pending' but having proof.
            
            $message = "Bukti pembayaran berhasil diupload! Mohon tunggu verifikasi admin.";
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode(", ", $upload['errors']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - DistroZone</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #10B981;
            --secondary: #0F766E;
            --dark: #1F2937;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #ECFDF5;
            background-image: 
                radial-gradient(at 0% 0%, hsla(160,100%,25%,0.05) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(180,100%,30%,0.05) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(150,100%,30%,0.05) 0, transparent 50%);
            background-size: 200% 200%;
            animation: gradientBG 15s ease infinite;
            color: #334155;
            min-height: 100vh;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0 20px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255,255,255,0.5);
            text-align: center;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #D1FAE5;
            color: #10B981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        
        .trx-code {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 20px;
            font-weight: 600;
            background: #F1F5F9;
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-block;
        }
        
        .amount {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 30px;
        }
        
        .bank-info {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #E5E7EB;
            text-align: left;
        }
        
        .bank-info h4 { margin-bottom: 12px; color: var(--dark); }
        .bank-detail { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        
        .upload-area {
            border: 2px dashed #CBD5E1;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
            background: #F8FAFC;
        }
        
        .upload-area:hover {
            border-color: var(--primary);
            background: #F0FDFA;
        }
        
        .btn-confirm {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success { background: #D1FAE5; color: #065F46; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

    </style>
</head>
<body>

    <div class="container">
        <div class="card">
            <?php if ($message): ?>
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Terima Kasih!</h2>
                <div class="alert alert-success" style="margin-top: 20px;">
                    <?php echo $message; ?>
                </div>
                <div style="margin-top: 30px;">
                    <a href="orders.php" class="btn-confirm" style="text-decoration: none; display: block;">Lihat Pesanan Saya</a>
                </div>
            <?php else: ?>
            
                <div class="success-icon" style="background: #E0E7FF; color: #4F46E5;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                
                <h2>Selesaikan Pembayaran</h2>
                <div class="trx-code">Order ID: <?php echo $transaction['kode_transaksi']; ?></div>
                
                <div class="amount"><?php echo format_rupiah($transaction['grand_total']); ?></div>
                
                <div class="bank-info">
                    <?php if ($transaction['payment_method'] === 'qris'): ?>
                        <h4>Scan QRIS untuk Membayar:</h4>
                        <div style="text-align: center; margin: 20px 0;">
                            <div style="width: 200px; height: 200px; background: #EEE; border: 2px solid #DDD; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                <i class="fas fa-qrcode" style="font-size: 80px; color: #333; margin-bottom: 10px;"></i>
                                <span style="font-weight: 700; color: var(--primary);">QRIS DISTROZONE</span>
                            </div>
                            <p style="font-size: 12px; color: #64748B; margin-top: 10px;">Gunakan GoPay, OVO, Dana, atau Mobile Banking</p>
                        </div>
                    <?php else: ?>
                        <h4>Transfer ke Salah Satu Rekening:</h4>
                        <div class="bank-detail">
                            <span><i class="fas fa-university"></i> BCA</span>
                            <strong>123-456-7890 a/n DistroZone</strong>
                        </div>
                        <div class="bank-detail">
                            <span><i class="fas fa-university"></i> MANDIRI</span>
                            <strong>098-765-4321 a/n DistroZone</strong>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <label class="upload-area" style="display: block;">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #94A3B8; margin-bottom: 10px;"></i>
                        <div style="font-weight: 600; color: #475569;">Upload Bukti Transfer</div>
                        <div style="font-size: 12px; color: #94A3B8; margin-top: 5px;">Format JPG, PNG (Max 5MB)</div>
                        <input type="file" name="payment_proof" accept="image/*" required style="display: none;" onchange="previewFile(this)">
                        <div id="file-name" style="margin-top: 10px; font-weight: 600; color: var(--primary);"></div>
                    </label>
                    
                    <button type="submit" class="btn-confirm">
                        Konfirmasi Pembayaran
                    </button>
                    
                     <a href="orders.php" style="display: block; margin-top: 20px; color: #64748B; text-decoration: none; font-size: 14px;">Bayar Nanti (Cek Pesanan)</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                document.getElementById('file-name').textContent = "File: " + file.name;
            }
        }
    </script>
</body>
</html>
