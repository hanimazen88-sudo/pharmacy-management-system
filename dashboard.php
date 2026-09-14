<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'لوحة التحكم';

// فحص تنبيه البريد الإلكتروني اليومي (مرة واحدة كحد أقصى يوميًا، للمدير فقط)
if (isAdmin()) {
    require_once 'includes/mailer.php';
    checkAndSendDailyAlert($pdo);
}

// إجمالي عدد الأدوية
$totalMedicines = $pdo->query('SELECT COUNT(*) FROM medicines')->fetchColumn();

// الأدوية منخفضة المخزون
$lowStockStmt = $pdo->query('SELECT * FROM medicines WHERE quantity <= min_quantity ORDER BY quantity ASC');
$lowStockMedicines = $lowStockStmt->fetchAll();

// الأدوية قريبة/منتهية الصلاحية
$expiryStmt = $pdo->query('SELECT * FROM medicines WHERE expiry_date IS NOT NULL ORDER BY expiry_date ASC');
$allExpiryMeds = $expiryStmt->fetchAll();
$expiringSoon = array_filter($allExpiryMeds, fn($m) => isExpiringSoon($m['expiry_date']) && !isExpired($m['expiry_date']));
$expired = array_filter($allExpiryMeds, fn($m) => isExpired($m['expiry_date']));

// مبيعات اليوم
$todaySalesStmt = $pdo->prepare('SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total FROM sales WHERE DATE(created_at) = CURDATE()');
$todaySalesStmt->execute();
$todaySales = $todaySalesStmt->fetch();

// آخر 5 فواتير
$recentSales = $pdo->query('SELECT * FROM sales ORDER BY created_at DESC LIMIT 5')->fetchAll();

require_once 'includes/header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="icon-box bg-teal"><i class="bi bi-box-seam"></i></div>
            <div>
                <h3><?= (int)$totalMedicines ?></h3>
                <p>إجمالي الأصناف بالمخزون</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="icon-box bg-red"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <h3><?= count($lowStockMedicines) ?></h3>
                <p>أصناف منخفضة المخزون</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="icon-box bg-orange"><i class="bi bi-hourglass-split"></i></div>
            <div>
                <h3><?= count($expiringSoon) ?></h3>
                <p>قاربت على انتهاء الصلاحية</p>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="icon-box bg-blue"><i class="bi bi-cash-coin"></i></div>
            <div>
                <h3><?= formatMoney($todaySales['total']) ?></h3>
                <p>مبيعات اليوم (<?= (int)$todaySales['cnt'] ?> فاتورة)</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-panel h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> أصناف منخفضة المخزون</h6>
                <?php if (isAdmin() && !empty($lowStockMedicines)): ?>
                    <a href="shortage_list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text"></i> كشكول النواقص</a>
                <?php endif; ?>
            </div>
            <?php if (empty($lowStockMedicines)): ?>
                <p class="text-muted mb-0">لا توجد أصناف منخفضة المخزون حالياً.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>الدواء</th><th>الكمية المتبقية</th><th>الحد الأدنى</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($lowStockMedicines, 0, 6) as $m): ?>
                        <tr>
                            <td><?= e($m['name']) ?></td>
                            <td><span class="badge badge-low"><?= (int)$m['quantity'] ?></span></td>
                            <td><?= (int)$m['min_quantity'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-panel h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-hourglass-split text-warning"></i> قريبة الانتهاء / منتهية</h6>
            <?php if (empty($expiringSoon) && empty($expired)): ?>
                <p class="text-muted mb-0">لا توجد أدوية قريبة من الانتهاء.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>الدواء</th><th>تاريخ الانتهاء</th><th>الحالة</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($expired, 0, 3) as $m): ?>
                        <tr>
                            <td><?= e($m['name']) ?></td>
                            <td><?= formatDate($m['expiry_date']) ?></td>
                            <td><span class="badge badge-low">منتهي</span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach (array_slice($expiringSoon, 0, 3) as $m): ?>
                        <tr>
                            <td><?= e($m['name']) ?></td>
                            <td><?= formatDate($m['expiry_date']) ?></td>
                            <td><span class="badge badge-soon">قريب</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card-panel mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-receipt"></i> آخر الفواتير</h6>
        <a href="invoices.php" class="btn btn-sm btn-outline-secondary">عرض الكل</a>
    </div>
    <?php if (empty($recentSales)): ?>
        <p class="text-muted mb-0">لا توجد فواتير بعد. <a href="sales.php">أنشئ أول فاتورة بيع</a>.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentSales as $s): ?>
                <tr>
                    <td><?= e($s['invoice_number']) ?></td>
                    <td><?= e($s['customer_name']) ?></td>
                    <td><?= formatMoney($s['total_amount']) ?></td>
                    <td><?= date('Y/m/d H:i', strtotime($s['created_at'])) ?></td>
                    <td><a href="invoice_view.php?id=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
