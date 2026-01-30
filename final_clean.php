<?php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

echo "STARTING_CLEANUP\n";
try {
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conn->exec("TRUNCATE TABLE payment_proof");
    $conn->exec("TRUNCATE TABLE detail_transaksi");
    $conn->exec("TRUNCATE TABLE transaksi");
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "SUCCESS: Tables truncated.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
