<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'إدارة المستخدمين';

// حذف مستخدم
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id === (int)$_SESSION['user_id']) {
        flash('error', 'لا يمكنك حذف حسابك الخاص أثناء استخدامه');
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'تم حذف المستخدم بنجاح');
    }
    header('Location: users.php');
    exit;
}

// تفعيل / تعطيل مستخدم
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id === (int)$_SESSION['user_id']) {
        flash('error', 'لا يمكنك تعطيل حسابك الخاص أثناء استخدامه');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'تم تحديث حالة المستخدم');
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query('SELECT * FROM users ORDER BY id ASC')->fetchAll();

require_once 'includes/header.php';
?>

<div class="card-panel">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <p class="text-muted mb-0">يمكن لمدير النظام فقط إضافة مستخدمين جدد أو تعديل صلاحياتهم.</p>
        <a href="user_add.php" class="btn btn-primary-app"><i class="bi bi-person-plus"></i> إضافة مستخدم جديد</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم الكامل</th>
                    <th>اسم المستخدم</th>
                    <th>الصلاحية</th>
                    <th>الحالة</th>
                    <th>تاريخ الإنشاء</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge text-bg-dark">مدير النظام</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">كاشير / موظف</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$u['is_active'] === 1): ?>
                            <span class="badge badge-ok">مفعّل</span>
                        <?php else: ?>
                            <span class="badge badge-low">معطّل</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y/m/d', strtotime($u['created_at'])) ?></td>
                    <td class="text-nowrap">
                        <a href="user_edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary" title="تعديل"><i class="bi bi-pencil"></i></a>
                        <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <a href="users.php?toggle=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-warning" title="تفعيل/تعطيل"><i class="bi bi-power"></i></a>
                            <a href="users.php?delete=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete" title="حذف"><i class="bi bi-trash"></i></a>
                        <?php else: ?>
                            <span class="badge text-bg-light border">حسابك الحالي</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
