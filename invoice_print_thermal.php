<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT s.*, u.full_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('الفاتورة غير موجودة');
}

$itemsStmt = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إيصال <?= e($sale['invoice_number']) ?></title>
<style>
    @page { size: 80mm auto; margin: 2mm; }
    * { box-sizing: border-box; }
    body {
        width: 76mm;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
        font-size: 11px;
        color: #000;
    }
    .center { text-align: center; }
    .store-name { font-size: 15px; font-weight: 800; margin: 2px 0; }
    .store-sub { font-size: 10px; margin-bottom: 6px; }
    hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
    .info-row { display: flex; justify-content: space-between; font-size: 10.5px; margin: 2px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-top: 4px; }
    th { border-bottom: 1px solid #000; text-align: right; padding: 2px 0; font-size: 10px; }
    td { padding: 2px 0; vertical-align: top; }
    .col-qty { text-align: center; width: 14%; }
    .col-price { text-align: left; width: 25%; }
    .total-row { font-weight: 800; font-size: 13px; }
    .footer-note { font-size: 10px; margin-top: 8px; }
    .no-print { text-align: center; margin: 10px 0; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;font-size:14px;">🖨️ طباعة الإيصال</button>
    </div>

    <div class="center">
        <div class="store-name">صيدلية السعادة</div>
        <div class="store-sub">النصيرات - قطاع غزة</div>
    </div>
    <hr>
    <div class="info-row"><span>رقم الفاتورة:</span><span><?= e($sale['invoice_number']) ?></span></div>
    <div class="info-row"><span>التاريخ:</span><span><?= date('Y/m/d H:i', strtotime($sale['created_at'])) ?></span></div>
    <div class="info-row"><span>العميل:</span><span><?= e($sale['customer_name']) ?></span></div>
    <div class="info-row"><span>الموظف:</span><span><?= e($sale['full_name'] ?? '—') ?></span></div>
    <hr>

    <table>
        <thead>
            <tr><th>الصنف</th><th class="col-qty">كمية</th><th class="col-price">السعر</th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['medicine_name']) ?></td>
                <td class="col-qty"><?= (int)$item['quantity'] ?></td>
                <td class="col-price"><?= number_format((float)$item['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <div class="info-row total-row"><span>الإجمالي</span><span><?= number_format((float)$sale['total_amount'], 2) ?> ₪</span></div>
    <hr>
    <div class="center footer-note">
        شكرًا لتعاملكم مع صيدلية السعادة<br>
        نتمنى لكم دوام الصحة والعافية
    </div>
</body>
</html>
