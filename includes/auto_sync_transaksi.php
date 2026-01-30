<?php
/**
 * AUTO-FIX: Sinkronisasi status transaksi dengan payment_proof
 * Include file ini di awal setiap halaman kasir untuk auto-sync
 */

// Pastikan koneksi database sudah ada
if (!isset($conn)) {
    return;
}

// Cek setiap 10 detik saja untuk performa
$last_sync_key = 'last_trx_sync_' . session_id();
$last_sync = $_SESSION[$last_sync_key] ?? 0;

if (time() - $last_sync < 10) {
    return; // Skip jika kurang dari 10 detik
}

$_SESSION[$last_sync_key] = time();

try {
    // Auto-sync: Jika payment_proof sudah verified, pastikan transaksi juga verified
    // FIX: Handle cases where status is empty string, NULL, or 'cancelled'
    $sql = "UPDATE transaksi t
            INNER JOIN payment_proof p ON t.id = p.transaksi_id
            SET t.status = 'verified', 
                t.kasir_id = IFNULL(t.kasir_id, p.verified_by),
                t.cancelled_by = NULL, 
                t.cancel_reason = NULL
            WHERE p.status = 'verified' 
            AND (t.status != 'verified' OR t.status IS NULL OR t.status = '')";
    
    $conn->exec($sql);
    
    // Safety check: Fix any transactions with NULL status but existing payment proof (pending)
    $sql_pending = "UPDATE transaksi t
                    INNER JOIN payment_proof p ON t.id = p.transaksi_id
                    SET t.status = 'pending'
                    WHERE p.status = 'pending' 
                    AND (t.status IS NULL OR t.status = '')";
    $conn->exec($sql_pending);
    
} catch (Exception $e) {
    // Silent fail - jangan ganggu user
    error_log('Auto-sync error: ' . $e->getMessage());
}
?>
