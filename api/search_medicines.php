<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, unit, quantity, selling_price, expiry_date
                        FROM medicines
                        WHERE (name LIKE ? OR barcode LIKE ?) AND quantity > 0
                        ORDER BY name ASC LIMIT 10");
$stmt->execute(["%$q%", "%$q%"]);
$results = $stmt->fetchAll();

echo json_encode($results, JSON_UNESCAPED_UNICODE);
