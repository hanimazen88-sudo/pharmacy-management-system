<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/reports_query.php';

$pageTitle = 'التقارير';

$f = readReportFilters();
$users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM medicines ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

$salesData = null; $inventoryData = null; $topData = null;
if ($f['type'] === 'sales') {
    $salesData = getSalesReport($pdo, $f);
} elseif ($f['type'] === 'inventory') {
    $inventoryData = getInventoryReport($pdo, $f);
} elseif ($f['type'] === 'top_selling') {
    $topData = getTopSellingReport($pdo, $f);
}

$qs = buildReportQueryString($f);

require_once 'includes/header.php';
?>

<div class="card-panel mb-3">
    <ul class="nav nav-pills report-type-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $f['type'] === 'sales' ? 'active' : '' ?>" href="reports.php?type=sales">
                <i class="bi bi-receipt"></i> تقرير المبيعات
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $f['type'] === 'inventory' ? 'active' : '' ?>" href="reports.php?type=inventory">
                <i class="bi bi-box-seam"></i> تقرير المخزون
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $f['type'] === 'top_selling' ? 'active' : '' ?>" href="reports.php?type=top_selling">
                <i class="bi bi-graph-up-arrow"></i> الأصناف الأكثر مبيعًا
            </a>
        </li>
    </ul>

    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="type" value="<?= e($f['type']) ?>">

        <?php if ($f['type'] === 'sales'): ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">من تاريخ</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($f['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">إلى تاريخ</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($f['date_to']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">الموظف</label>
                <select name="user_id" class="form-select">
                    <option value="">كل الموظفين</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (string)$f['user_id'] === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">بحث (رقم فاتورة / عميل)</label>
                <input type="text" name="search" class="form-control" value="<?= e($f['search']) ?>">
            </div>
        <?php elseif ($f['type'] === 'inventory'): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">الفئة</label>
                <select name="category" class="form-select">
                    <option value="">كل الفئات</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $f['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">حالة المخزون</label>
                <select name="status" class="form-select">
                    <option value="">كل الحالات</option>
                    <option value="low" <?= $f['status'] === 'low' ? 'selected' : '' ?>>منخفض المخزون</option>
                    <option value="expiring_soon" <?= $f['status'] === 'expiring_soon' ? 'selected' : '' ?>>قريب من الانتهاء</option>
                    <option value="expired" <?= $f['status'] === 'expired' ? 'selected' : '' ?>>منتهي الصلاحية</option>
                </select>
            </div>
        <?php else: ?>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">من تاريخ</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($f['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">إلى تاريخ</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($f['date_to']) ?>">
            </div>
        <?php endif; ?>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-outline-secondary flex-fill"><i class="bi bi-funnel"></i> تصفية</button>
        </div>

        <div class="col-12 d-flex gap-2 mt-2">
            <a href="report_export_excel.php?<?= $qs ?>" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> تصدير Excel
            </a>
            <a href="report_print.php?<?= $qs ?>" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer"></i> طباعة / PDF
            </a>
        </div>
    </form>
</div>

<div class="card-panel">
<?php if ($f['type'] === 'sales'): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">نتائج تقرير المبيعات (<?= (int)$salesData['count'] ?> فاتورة)</h6>
        <h6 class="fw-bold mb-0 text-success">الإجمالي: <?= formatMoney($salesData['total_amount']) ?></h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>الموظف</th><th>الإجمالي</th><th>التاريخ</th></tr></thead>
            <tbody>
            <?php if (empty($salesData['rows'])): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
            <?php foreach ($salesData['rows'] as $r): ?>
                <tr>
                    <td class="fw-semibold"><a href="invoice_view.php?id=<?= (int)$r['id'] ?>"><?= e($r['invoice_number']) ?></a></td>
                    <td><?= e($r['customer_name']) ?></td>
                    <td><?= e($r['employee_name'] ?? '—') ?></td>
                    <td><?= formatMoney($r['total_amount']) ?></td>
                    <td><?= date('Y/m/d H:i', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($f['type'] === 'inventory'): ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0">نتائج تقرير المخزون (<?= (int)$inventoryData['count'] ?> صنف)</h6>
        <h6 class="fw-bold mb-0 text-success">قيمة المخزون التقديرية: <?= formatMoney($inventoryData['total_stock_value']) ?></h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>اسم الدواء</th><th>الفئة</th><th>الكمية</th><th>سعر البيع</th><th>قيمة المخزون</th><th>تاريخ الانتهاء</th></tr></thead>
            <tbody>
            <?php if (empty($inventoryData['rows'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
            <?php foreach ($inventoryData['rows'] as $r): ?>
                <tr>
                    <td class="fw-semibold"><?= e($r['name']) ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= (int)$r['quantity'] ?></td>
                    <td><?= formatMoney($r['selling_price']) ?></td>
                    <td><?= formatMoney($r['selling_price'] * $r['quantity']) ?></td>
                    <td><?= formatDate($r['expiry_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <h6 class="fw-bold mb-3">الأصناف الأكثر مبيعًا (أعلى 20 صنف)</h6>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>#</th><th>اسم الدواء</th><th>إجمالي الكمية المباعة</th><th>إجمالي الإيراد</th></tr></thead>
            <tbody>
            <?php if (empty($topData['rows'])): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات مبيعات ضمن هذه الفترة</td></tr>
            <?php endif; ?>
            <?php foreach ($topData['rows'] as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= e($r['medicine_name']) ?></td>
                    <td><span class="badge badge-ok"><?= (int)$r['total_qty'] ?></span></td>
                    <td><?= formatMoney($r['total_revenue']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
