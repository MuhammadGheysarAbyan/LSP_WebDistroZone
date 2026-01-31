<?php
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT id, warna, warna_hex FROM kaos_varian LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
?>
