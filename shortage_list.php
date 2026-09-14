<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'كشكول النواقص';

// إضافة صنف للكشكول
if (isset($_POST['add_to_list'])) {
    $medicineId = (int)$_POST['medicine_id'];
    $qty = max(1, (int)$_POST['quantity_to_order']);
    $note = trim($_POST['note'] ?? '');

    // تفادي تكرار نفس الصنف أكثر من مرة ضمن حالة "قيد الانتظار" أو "تم الطلب"
    $check = $pdo->prepare("SELECT id FROM shortage_list WHERE medicine_id = ? AND status IN ('pending','ordered')");
    $check->execute([$medicineId]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO shortage_list (medicine_id, quantity_to_order, note, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$medicineId, $qty, $note ?: null, $_SESSION['user_id']]);
        flash('success', 'تمت إضافة الصنف إلى كشكول النواقص');
    } else {
        flash('error', 'هذا الصنف موجود بالفعل ضمن الكشكول الحالي');
    }
    header('Location: shortage_list.php');
    exit;
}

// تحديث حالة عنصر (قيد الانتظار / تم الطلب / تم الاستلام)
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['item_id'];
    $status = $_POST['status'];
    if (in_array($status, ['pending', 'ordered', 'received'], true)) {
        $stmt = $pdo->prepare('UPDATE shortage_list SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);

        // عند تأكيد "تم الاستلام"، نقترح تحديث كمية المخزون تلقائيًا بالكمية المطلوبة
        if ($status === 'received') {
            $itemStmt = $pdo->prepare('SELECT * FROM shortage_list WHERE id = ?');
            $itemStmt->execute([$id]);
            $item = $itemStmt->fetch();
            if ($item) {
                $upd = $pdo->prepare('UPDATE medicines SET quantity = quantity + ? WHERE id = ?');
                $upd->execute([$item['quantity_to_order'], $item['medicine_id']]);
                flash('success', 'تم تحديث الحالة، وأُضيفت الكمية المستلمة تلقائيًا لمخزون الصنف');
            }
        } else {
            flash('success', 'تم تحديث حالة العنصر');
        }
    }
    header('Location: shortage_list.php');
    exit;
}

// حذف عنصر من الكشكول
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM shortage_list WHERE id = ?');
    $stmt->execute([(int)$_GET['delete']]);
    flash('success', 'تم حذف العنصر من الكشكول');
    header('Location: shortage_list.php');
    exit;
}

// الأصناف منخفضة المخزون غير المضافة بعد للكشكول (نشطة)
$suggested = $pdo->query("
    SELECT m.* FROM medicines m
    WHERE m.quantity <= m.min_quantity
    AND m.id NOT IN (SELECT medicine_id FROM shortage_list WHERE status IN ('pending','ordered'))
    ORDER BY m.quantity ASC
")->fetchAll();

// عناصر الكشكول الحالية
$shortageItems = $pdo->query("
    SELECT sl.*, m.name AS medicine_name, m.unit, m.quantity AS current_quantity, u.full_name AS created_by_name
    FROM shortage_list sl
    JOIN medicines m ON sl.medicine_id = m.id
    LEFT JOIN users u ON sl.created_by = u.id
    ORDER BY FIELD(sl.status,'pending','ordered','received'), sl.created_at DESC
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="card-panel mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> أصناف مقترحة للإضافة (منخفضة المخزون)</h6>
    </div>
    <?php if (empty($suggested)): ?>
        <p class="text-muted mb-0">لا توجد أصناف منخفضة المخزون غير مضافة للكشكول حاليًا.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>الدواء</th><th>الكمية الحالية</th><th>الحد الأدنى</th><th>الكمية المقترح طلبها</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($suggested as $m): ?>
                <tr>
                    <td class="fw-semibold"><?= e($m['name']) ?></td>
                    <td><span class="badge badge-low"><?= (int)$m['quantity'] ?></span></td>
                    <td><?= (int)$m['min_quantity'] ?></td>
                    <td style="width:140px">
                        <form method="POST" action="shortage_list.php" class="d-flex gap-1">
                            <input type="hidden" name="medicine_id" value="<?= (int)$m['id'] ?>">
                            <input type="number" name="quantity_to_order" class="form-control form-control-sm" value="<?= max(20, $m['min_quantity'] * 2) ?>" min="1" style="width:80px">
                            <button type="submit" name="add_to_list" value="1" class="btn btn-sm btn-primary-app"><i class="bi bi-plus-lg"></i></button>
                        </form>
                    </td>
                    <td></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card-panel">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h6 class="fw-bold mb-0"><i class="bi bi-journal-text"></i> الكشكول الحالي (<?= count($shortageItems) ?>)</h6>
        <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> طباعة الكشكول</button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>الدواء</th>
                    <th>الكمية المطلوب طلبها</th>
                    <th>ملاحظة</th>
                    <th>أضافه</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($shortageItems)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">الكشكول فارغ حاليًا</td></tr>
            <?php endif; ?>
            <?php foreach ($shortageItems as $item): ?>
                <tr>
                    <td class="fw-semibold"><?= e($item['medicine_name']) ?> <span class="text-muted small">(<?= e($item['unit']) ?>)</span></td>
                    <td><?= (int)$item['quantity_to_order'] ?></td>
                    <td><?= e($item['note'] ?: '—') ?></td>
                    <td class="small text-muted"><?= e($item['created_by_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($item['status'] === 'pending'): ?>
                            <span class="badge badge-low">قيد الانتظار</span>
                        <?php elseif ($item['status'] === 'ordered'): ?>
                            <span class="badge badge-soon">تم الطلب من المورد</span>
                        <?php else: ?>
                            <span class="badge badge-ok">تم الاستلام</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap no-print">
                        <?php if ($item['status'] === 'pending'): ?>
                            <form method="POST" action="shortage_list.php" class="d-inline">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <input type="hidden" name="status" value="ordered">
                                <button type="submit" name="update_status" value="1" class="btn btn-sm btn-outline-secondary">تم الطلب</button>
                            </form>
                        <?php elseif ($item['status'] === 'ordered'): ?>
                            <form method="POST" action="shortage_list.php" class="d-inline">
                                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                <input type="hidden" name="status" value="received">
                                <button type="submit" name="update_status" value="1" class="btn btn-sm btn-outline-success">تأكيد الاستلام</button>
                            </form>
                        <?php endif; ?>
                        <a href="shortage_list.php?delete=<?= (int)$item['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mb-0">
        <i class="bi bi-info-circle"></i>
        عند تأكيد "تم الاستلام"، تُضاف الكمية تلقائيًا إلى مخزون الصنف. هذه الميزة تمهيد أولي لوحدة إدارة الموردين المقترحة مستقبلاً (راجع تقرير المشروع، الفصل الخامس).
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>
