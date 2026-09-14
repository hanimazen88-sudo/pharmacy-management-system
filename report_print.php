<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/reports_query.php';

$f = readReportFilters();
$titles = ['sales' => 'تقرير المبيعات', 'inventory' => 'تقرير المخزون', 'top_selling' => 'تقرير الأصناف الأكثر مبيعًا'];
$reportTitle = $titles[$f['type']] ?? 'تقرير';

if ($f['type'] === 'sales') {
    $data = getSalesReport($pdo, $f);
} elseif ($f['type'] === 'inventory') {
    $data = getInventoryReport($pdo, $f);
} else {
    $data = getTopSellingReport($pdo, $f);
}

$filterSummary = [];
if (!empty($f['date_from'])) $filterSummary[] = 'من: ' . $f['date_from'];
if (!empty($f['date_to'])) $filterSummary[] = 'إلى: ' . $f['date_to'];
if (!empty($f['category'])) $filterSummary[] = 'الفئة: ' . $f['category'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title><?= e($reportTitle) ?> - صيدلية السعادة</title>
<link href="assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
<style>
    body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; padding: 24px; }
    .report-header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #12876b; padding-bottom: 14px; }
    .report-header h3 { color: #0d6350; font-weight: 800; margin-bottom: 2px; }
    table { width: 100%; }
    th { background: #e6f5f1 !important; color: #0d6350; }
    .no-print { margin-bottom: 16px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <div class="no-print d-flex gap-2">
        <button class="btn btn-primary" onclick="window.print()"><i class="bi"></i> طباعة / حفظ PDF</button>
    </div>

    <div class="report-header">
        <h3>صيدلية السعادة — النصيرات</h3>
        <div class="fw-bold fs-5"><?= e($reportTitle) ?></div>
        <?php if (!empty($filterSummary)): ?>
            <div class="text-muted small"><?= e(implode(' | ', $filterSummary)) ?></div>
        <?php endif; ?>
        <div class="text-muted small">تاريخ الطباعة: <?= date('Y/m/d H:i') ?></div>
    </div>

    <table class="table table-bordered table-sm">
    <?php if ($f['type'] === 'sales'): ?>
        <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>الموظف</th><th>الإجمالي</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= e($r['invoice_number']) ?></td>
                <td><?= e($r['customer_name']) ?></td>
                <td><?= e($r['employee_name'] ?? '—') ?></td>
                <td><?= formatMoney($r['total_amount']) ?></td>
                <td><?= date('Y/m/d H:i', strtotime($r['created_at'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="3">الإجمالي (<?= (int)$data['count'] ?> فاتورة)</td><td colspan="2"><?= formatMoney($data['total_amount']) ?></td></tr></tfoot>

    <?php elseif ($f['type'] === 'inventory'): ?>
        <thead><tr><th>اسم الدواء</th><th>الفئة</th><th>الكمية</th><th>سعر البيع</th><th>قيمة المخزون</th><th>تاريخ الانتهاء</th></tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $r): ?>
            <tr>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= (int)$r['quantity'] ?></td>
                <td><?= formatMoney($r['selling_price']) ?></td>
                <td><?= formatMoney($r['selling_price'] * $r['quantity']) ?></td>
                <td><?= formatDate($r['expiry_date']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr class="fw-bold"><td colspan="4">الإجمالي (<?= (int)$data['count'] ?> صنف)</td><td colspan="2"><?= formatMoney($data['total_stock_value']) ?></td></tr></tfoot>

    <?php else: ?>
        <thead><tr><th>#</th><th>اسم الدواء</th><th>إجمالي الكمية المباعة</th><th>إجمالي الإيراد</th></tr></thead>
        <tbody>
        <?php foreach ($data['rows'] as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= e($r['medicine_name']) ?></td>
                <td><?= (int)$r['total_qty'] ?></td>
                <td><?= formatMoney($r['total_revenue']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php endif; ?>
    </table>
</body>
</html>
