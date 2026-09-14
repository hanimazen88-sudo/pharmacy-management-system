<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'إضافة دواء جديد';
$errors = [];
$values = [
    'name' => '', 'category' => '', 'unit' => 'حبة', 'quantity' => 0,
    'purchase_price' => '', 'selling_price' => '', 'expiry_date' => '',
    'barcode' => '', 'min_quantity' => 10,
];

$commonCategories = ['مسكنات', 'مضادات حيوية', 'فيتامينات', 'أدوية الأطفال', 'مضادات الحموضة', 'مستلزمات طبية', 'أخرى'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['name']           = trim($_POST['name'] ?? '');
    $values['category']       = trim($_POST['category'] ?? '') ?: 'أخرى';
    $values['unit']            = trim($_POST['unit'] ?? '') ?: 'حبة';
    $values['quantity']       = (int)($_POST['quantity'] ?? 0);
    $values['purchase_price'] = (float)($_POST['purchase_price'] ?? 0);
    $values['selling_price']  = (float)($_POST['selling_price'] ?? 0);
    $values['expiry_date']    = trim($_POST['expiry_date'] ?? '') ?: null;
    $values['barcode']        = trim($_POST['barcode'] ?? '') ?: null;
    $values['min_quantity']   = (int)($_POST['min_quantity'] ?? 10);

    if ($values['name'] === '') $errors[] = 'اسم الدواء مطلوب';
    if ($values['quantity'] < 0) $errors[] = 'الكمية لا يمكن أن تكون سالبة';
    if ($values['selling_price'] <= 0) $errors[] = 'سعر البيع يجب أن يكون أكبر من صفر';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO medicines (name, category, unit, quantity, purchase_price, selling_price, expiry_date, barcode, min_quantity)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $values['name'], $values['category'], $values['unit'], $values['quantity'],
            $values['purchase_price'], $values['selling_price'], $values['expiry_date'],
            $values['barcode'], $values['min_quantity'],
        ]);
        flash('success', 'تمت إضافة الدواء بنجاح');
        header('Location: medicines.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="card-panel" style="max-width:720px;margin:auto">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="medicine_add.php">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">اسم الدواء *</label>
                <input type="text" name="name" class="form-control" value="<?= e($values['name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">الفئة</label>
                <input type="text" name="category" class="form-control" list="categoryList" value="<?= e($values['category']) ?>" placeholder="مسكنات">
                <datalist id="categoryList">
                    <?php foreach ($commonCategories as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?>
                </datalist>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">الوحدة</label>
                <input type="text" name="unit" class="form-control" value="<?= e($values['unit']) ?>" placeholder="علبة / حبة / زجاجة">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">الكمية الحالية</label>
                <input type="number" name="quantity" class="form-control" value="<?= e($values['quantity']) ?>" min="0" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">الحد الأدنى للتنبيه</label>
                <input type="number" name="min_quantity" class="form-control" value="<?= e($values['min_quantity']) ?>" min="0">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">سعر الشراء</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="<?= e($values['purchase_price']) ?>" min="0">
                    <span class="input-group-text">₪</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">سعر البيع *</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="selling_price" class="form-control" value="<?= e($values['selling_price']) ?>" min="0" required>
                    <span class="input-group-text">₪</span>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">تاريخ انتهاء الصلاحية</label>
                <input type="date" name="expiry_date" class="form-control" value="<?= e($values['expiry_date']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الباركود (اختياري)</label>
                <input type="text" name="barcode" class="form-control" value="<?= e($values['barcode']) ?>">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary-app px-4"><i class="bi bi-check-circle"></i> حفظ</button>
            <a href="medicines.php" class="btn btn-outline-secondary px-4">إلغاء</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
