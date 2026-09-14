<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$code = trim($_GET['code'] ?? '');

if ($code === '') {
    echo json_encode(['found' => false, 'message' => 'لم يتم إرسال أي كود']);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, unit, quantity, selling_price, barcode, expiry_date
                        FROM medicines
                        WHERE barcode = ?
                        LIMIT 1');
$stmt->execute([$code]);
$medicine = $stmt->fetch();

if (!$medicine) {
    echo json_encode(['found' => false, 'message' => 'لا يوجد دواء مطابق لهذا الباركود/QR: ' . $code], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int)$medicine['quantity'] <= 0) {
    echo json_encode(['found' => false, 'message' => 'الدواء "' . $medicine['name'] . '" غير متوفر بالمخزون حاليًا'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($medicine['expiry_date']) && strtotime($medicine['expiry_date']) < strtotime(date('Y-m-d'))) {
    echo json_encode(['found' => false, 'expired' => true, 'message' => 'تنبيه: الدواء "' . $medicine['name'] . '" منتهي الصلاحية بتاريخ ' . $medicine['expiry_date'] . '، ولا يمكن بيعه.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['found' => true, 'medicine' => $medicine], JSON_UNESCAPED_UNICODE);
