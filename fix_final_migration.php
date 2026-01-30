<?php
/**
 * PROFESSIONAL MIGRATION SCRIPT
 * Fixing transaction statuses to standard 'completed', 'pending', 'cancelled'
 * NOT TOUCHING PRODUCT DATA
 */
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1>🚀 Starting Transaction Migration...</h1>";

try {
    $conn->beginTransaction();

    // 1. Fix mixed "Success" statuses to 'completed'
    // This unifies verified, paid, selesai, sent -> completed
    $sql_success = "UPDATE transaksi 
                    SET status = 'completed' 
                    WHERE status IN ('verified', 'paid', 'selesai', 'sent')";
    $stmt = $conn->prepare($sql_success);
    $stmt->execute();
    echo "<p>✅ Updated {$stmt->rowCount()} mixed statuses to 'completed'</p>";

    // 2. Fix NULL/Empty/Pending statuses that HAVE verified proof -> 'completed'
    $sql_fix_null = "UPDATE transaksi t
                     INNER JOIN payment_proof p ON t.id = p.transaksi_id
                     SET t.status = 'completed',
                         t.kasir_id = IFNULL(t.kasir_id, p.verified_by),
                         t.cancelled_by = NULL,
                         t.cancel_reason = NULL
                     WHERE p.status = 'verified' 
                     AND (t.status IS NULL OR t.status = '' OR t.status = 'pending' OR t.status = 'cancelled')";
    $stmt = $conn->prepare($sql_fix_null);
    $stmt->execute();
    echo "<p>✅ Fixed {$stmt->rowCount()} misaligned transactions to 'completed'</p>";
    
    // 3. Normalize remaining NULL/Empty to 'pending'
    $sql_pending = "UPDATE transaksi 
                    SET status = 'pending' 
                    WHERE status IS NULL OR status = ''";
    $stmt = $conn->prepare($sql_pending);
    $stmt->execute();
    echo "<p>✅ Defaulted {$stmt->rowCount()} NULL/Empty statuses to 'pending'</p>";

    $conn->commit();
    echo "<h2>🎉 MIGRATION SUCCESSFUL!</h2>";
    echo "<p>All transaction statuses are now standardized.</p>";

} catch (Exception $e) {
    $conn->rollBack();
    echo "<h2 style='color:red'>❌ ERROR: " . $e->getMessage() . "</h2>";
}
?>
