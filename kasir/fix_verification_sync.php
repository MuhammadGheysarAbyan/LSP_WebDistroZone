<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Checking for inconsistencies...\n";

    // 1. Find transactions where payment is verified but transaction is NOT verified/completed
    $query = "SELECT t.id, t.kode_transaksi, t.status, p.status as payment_status
              FROM transaksi t
              JOIN payment_proof p ON t.id = p.transaksi_id
              WHERE p.status = 'verified' 
              AND t.status NOT IN ('verified', 'completed')";
    
    $stmt = $conn->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "Found " . count($results) . " inconsistent transactions:\n";
        foreach ($results as $row) {
            echo "- " . $row['kode_transaksi'] . " (TRX: " . $row['status'] . ", Proof: " . $row['payment_status'] . ")\n";
        }
        
        echo "\nFixing...\n";
        
        $sql = "UPDATE transaksi t
                JOIN payment_proof p ON t.id = p.transaksi_id
                SET t.status = 'verified',
                    t.kasir_id = IFNULL(p.verified_by, t.kasir_id)
                WHERE p.status = 'verified' 
                AND t.status NOT IN ('verified', 'completed')";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        echo "Successfully updated " . $stmt->rowCount() . " transactions to 'verified'.\n";
    } else {
        echo "No inconsistent transactions found.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
