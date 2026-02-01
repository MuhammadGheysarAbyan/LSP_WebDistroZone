<?php
/**
 * Auto-Complete Orders Script (Estimasi Based)
 * 
 * Logic:
 * 1. Fetch active orders (verified/sent).
 * 2. Join with shipping_rates to get 'estimasi' (e.g. "1-2 Hari").
 * 3. Parse max days from estimasi.
 * 4. If (Now > VerifiedDate + MaxDays), complete order.
 */

require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

$updated_count = 0;
$log = [];

// Fetch candidates
$sql = "SELECT t.id, t.kode_transaksi, t.status, pp.verified_at, sr.estimasi
        FROM transaksi t
        LEFT JOIN payment_proof pp ON t.id = pp.transaksi_id
        LEFT JOIN shipping_rates sr ON t.shipping_city = sr.wilayah
        WHERE t.status IN ('verified', 'sent')
        AND pp.verified_at IS NOT NULL";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $estimasi_text = $order['estimasi']; // e.g., "1-2 Hari" or "2-3 Hari"
        $max_days = 2; // Default fallback if no valid estimation

        // Parse max days from string
        if (!empty($estimasi_text) && preg_match_all('/\d+/', $estimasi_text, $matches)) {
            $max_days = (int) max($matches[0]);
        }

        // Calculate deadline
        $verified_time = strtotime($order['verified_at']);
        $deadline_time = $verified_time + ($max_days * 24 * 3600); // Add days in seconds
        
        // Check if exceeded
        if (time() > $deadline_time) {
            // Update to completed
            $upd = "UPDATE transaksi SET status = 'completed' WHERE id = :id";
            $updStmt = $conn->prepare($upd);
            $updStmt->execute(['id' => $order['id']]);
            
            $updated_count++;
            $log[] = "Order {$order['kode_transaksi']} completed (Est: $max_days days, Verified: {$order['verified_at']})";
        }
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') echo "Error: " . $e->getMessage() . "\n";
}

// Fallback logic for orders without shipping info (e.g. unknown manual orders)
// Auto-complete older than 5 days safely
try {
    $sqlFallback = "UPDATE transaksi t 
                    LEFT JOIN payment_proof pp ON t.id = pp.transaksi_id
                    SET t.status = 'completed' 
                    WHERE t.status IN ('verified', 'sent')
                    AND t.shipping_city IS NULL 
                    AND DATEDIFF(NOW(), IFNULL(pp.verified_at, t.waktu)) >= 5";
    $conn->query($sqlFallback);
} catch(Exception $e) {
    // Ignore
}


// Output logic
$is_cli = php_sapi_name() === 'cli';
$is_direct = isset($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']);

if ($is_cli && $is_direct) {
    // Running directly from CLI
    echo "=== Auto-Complete Logic Run ===\n";
    echo "Updated: $updated_count orders\n";
    foreach($log as $l) echo "- $l\n";
} elseif (!$is_cli && $is_direct) {
    // Running directly from browser
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'updated_count' => $updated_count,
        'details' => $log
    ]);
}
// When included from other files, remain silent
?>
