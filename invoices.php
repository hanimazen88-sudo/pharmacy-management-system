<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'سجل المبيعات';

$search = trim($_GET['q'] ?? '');
$dateFrom = trim($_GET['from'] ?? '');
$dateTo = trim($_GET['to'] ?? '');

$sql = "SELECT s.*, u.full_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= ' AND (s.invoice_number LIKE ? OR s.customer_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom !== '') {
    $sql .= ' AND DATE(s.created_at) >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $sql .= ' AND DATE(s.created_at) <= ?';
    $params[] = $dateTo;
}
$sql .= ' ORDER BY s.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

$grandTotal = array_sum(array_column($sales, 'total_amount'));

require_once 'includes/header.php';
?>

<div class="card-panel">
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="text" name="q" class="form-control" style="max-width:240px" placeholder="رقم الفاتورة أو اسم العميل" value="<?= e($search) ?>">
        <input type="date" name="from" class="form-control" style="max-width:170px" value="<?= e($dateFrom) ?>">
        <input type="date" name="to" class="form-control" style="max-width:170px" value="<?= e($dateTo) ?>">
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button>
        <?php if ($search || $dateFrom || $dateTo): ?>
            <a href="invoices.php" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> إلغاء الفلترة</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل</th>
                    <th>الموظف</th>
                    <th>الإجمالي</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد فواتير مطابقة</td></tr>
            <?php endif; ?>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td class="fw-semibold"><?= e($s['invoice_number']) ?></td>
                    <td><?= e($s['customer_name']) ?></td>
                    <td><?= e($s['full_name'] ?? '—') ?></td>
                    <td><?= formatMoney($s['total_amount']) ?></td>
                    <td><?= date('Y/m/d H:i', strtotime($s['created_at'])) ?></td>
                    <td>
                        <a href="invoice_view.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i> عرض
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if (!empty($sales)): ?>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-start fw-bold">إجمالي الفواتير المعروضة</td>
                    <td class="fw-bold"><?= formatMoney($grandTotal) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
