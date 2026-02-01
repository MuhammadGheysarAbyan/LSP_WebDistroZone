<?php
$conn = new PDO("mysql:host=localhost;dbname=distrozone_db", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== PAYMENT_PROOF STRUCTURE ===\n";
$stmt = $conn->query("SHOW COLUMNS FROM payment_proof");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
}
?>
