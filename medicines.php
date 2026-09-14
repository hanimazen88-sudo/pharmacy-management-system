<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'إدارة المخزون';

// حذف دواء (للمدير فقط)
if (isset($_GET['delete'])) {
    requireAdmin();
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM medicines WHERE id = ?');
    $stmt->execute([$id]);
    flash('success', 'تم حذف الدواء بنجاح');
    header('Location: medicines.php');
    exit;
}

// البحث والفلترة
$search = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$sql = 'SELECT * FROM medicines WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR barcode LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categoryFilter !== '') {
    $sql .= ' AND category = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM medicines ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require_once 'includes/header.php';
?>

<div class="card-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <form method="GET" class="d-flex flex-wrap gap-2">
            <input type="text" name="q" class="form-control" style="max-width:260px" placeholder="ابحث بالاسم أو الباركود..." value="<?= e($search) ?>">
            <select name="category" class="form-select" style="max-width:200px" onchange="this.form.submit()">
                <option value="">كل الفئات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button>
            <?php if ($search || $categoryFilter): ?>
                <a href="medicines.php" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> إلغاء</a>
            <?php endif; ?>
        </form>
        <?php if (isAdmin()): ?>
        <a href="medicine_add.php" class="btn btn-primary-app"><i class="bi bi-plus-circle"></i> إضافة دواء جديد</a>
        <?php endif; ?>
    </div>

    <?php if (isAdmin()): ?>
    <form method="GET" action="labels.php" id="labelsForm">
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <?php if (isAdmin()): ?><th style="width:34px"><input type="checkbox" id="selectAll" class="form-check-input"></th><?php endif; ?>
                    <th>#</th>
                    <th>اسم الدواء</th>
                    <th>الفئة</th>
                    <th>الوحدة</th>
                    <th>الكمية</th>
                    <th>سعر الشراء</th>
                    <th>سعر البيع</th>
                    <th>تاريخ الإضافة</th>
                    <th>تاريخ الانتهاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($medicines)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">لا توجد نتائج مطابقة</td></tr>
            <?php endif; ?>
            <?php foreach ($medicines as $i => $m): ?>
                <tr>
                    <?php if (isAdmin()): ?>
                        <td><input type="checkbox" name="ids[]" value="<?= (int)$m['id'] ?>" class="form-check-input row-check"></td>
                    <?php endif; ?>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= e($m['name']) ?></td>
                    <td><span class="badge text-bg-light border"><?= e($m['category']) ?></span></td>
                    <td><?= e($m['unit']) ?></td>
                    <td>
                        <?php if ($m['quantity'] <= $m['min_quantity']): ?>
                            <span class="badge badge-low"><?= (int)$m['quantity'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-ok"><?= (int)$m['quantity'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= formatMoney($m['purchase_price']) ?></td>
                    <td><?= formatMoney($m['selling_price']) ?></td>
                    <td>
                        <?= date('Y/m/d H:i', strtotime($m['created_at'])) ?>
                    </td>
                    <td>
                        <?= formatDate($m['expiry_date']) ?>
                        <?php if (isExpired($m['expiry_date'])): ?>
                            <span class="badge badge-low">منتهي</span>
                        <?php elseif (isExpiringSoon($m['expiry_date'])): ?>
                            <span class="badge badge-soon">قريب</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <?php if (isAdmin()): ?>
                            <a href="medicine_edit.php?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-secondary" title="تعديل"><i class="bi bi-pencil"></i></a>
                            <a href="medicines.php?delete=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" title="حذف"><i class="bi bi-trash"></i></a>
                        <?php else: ?>
                            <span class="text-muted small">عرض فقط</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isAdmin() && !empty($medicines)): ?>
        <button type="submit" class="btn btn-outline-secondary">
            <i class="bi bi-upc-scan"></i> طباعة بطاقة المنتج / QR للأصناف المحددة
        </button>
    </form>
    <?php endif; ?>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once 'includes/footer.php'; ?>
