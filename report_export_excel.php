<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/reports_query.php';

$f = readReportFilters();

$filename = 'تقرير_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// BOM لضمان ظهور الحروف العربية بشكل صحيح عند الفتح في Excel
echo "\xEF\xBB\xBF";
?>
<html>
<head><meta charset="UTF-8"></head>
<body dir="rtl">
<table border="1">
<?php if ($f['type'] === 'sales'):
    $data = getSalesReport($pdo, $f); ?>
    <tr>
        <th colspan="5" style="font-size:16px;background:#12876b;color:#fff;">تقرير المبيعات — صيدلية السعادة</th>
    </tr>
    <tr style="background:#e6f5f1;font-weight:bold;">
        <td>رقم الفاتورة</td><td>العميل</td><td>الموظف</td><td>الإجمالي</td><td>التاريخ</td>
    </tr>
    <?php foreach ($data['rows'] as $r): ?>
    <tr>
        <td><?= e($r['invoice_number']) ?></td>
        <td><?= e($r['customer_name']) ?></td>
        <td><?= e($r['employee_name'] ?? '') ?></td>
        <td><?= number_format((float)$r['total_amount'], 2) ?></td>
        <td><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="font-weight:bold;background:#f6efe6;">
        <td colspan="3">الإجمالي الكلي (<?= (int)$data['count'] ?> فاتورة)</td>
        <td colspan="2"><?= number_format($data['total_amount'], 2) ?></td>
    </tr>

<?php elseif ($f['type'] === 'inventory'):
    $data = getInventoryReport($pdo, $f); ?>
    <tr>
        <th colspan="6" style="font-size:16px;background:#12876b;color:#fff;">تقرير المخزون — صيدلية السعادة</th>
    </tr>
    <tr style="background:#e6f5f1;font-weight:bold;">
        <td>اسم الدواء</td><td>الفئة</td><td>الكمية</td><td>سعر البيع</td><td>قيمة المخزون</td><td>تاريخ الانتهاء</td>
    </tr>
    <?php foreach ($data['rows'] as $r): ?>
    <tr>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['category']) ?></td>
        <td><?= (int)$r['quantity'] ?></td>
        <td><?= number_format((float)$r['selling_price'], 2) ?></td>
        <td><?= number_format($r['selling_price'] * $r['quantity'], 2) ?></td>
        <td><?= e(formatDate($r['expiry_date'])) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="font-weight:bold;background:#f6efe6;">
        <td colspan="4">الإجمالي (<?= (int)$data['count'] ?> صنف)</td>
        <td colspan="2"><?= number_format($data['total_stock_value'], 2) ?></td>
    </tr>

<?php else:
    $data = getTopSellingReport($pdo, $f); ?>
    <tr>
        <th colspan="4" style="font-size:16px;background:#12876b;color:#fff;">تقرير الأصناف الأكثر مبيعًا — صيدلية السعادة</th>
    </tr>
    <tr style="background:#e6f5f1;font-weight:bold;">
        <td>#</td><td>اسم الدواء</td><td>إجمالي الكمية المباعة</td><td>إجمالي الإيراد</td>
    </tr>
    <?php foreach ($data['rows'] as $i => $r): ?>
    <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($r['medicine_name']) ?></td>
        <td><?= (int)$r['total_qty'] ?></td>
        <td><?= number_format((float)$r['total_revenue'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</table>
</body>
</html>
