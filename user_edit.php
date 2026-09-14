<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'تعديل بيانات مستخدم';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'المستخدم المطلوب غير موجود');
    header('Location: users.php');
    exit;
}

$errors = [];
$values = $user;
$isSelf = ((int)$id === (int)$_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['full_name'] = trim($_POST['full_name'] ?? '');
    $values['role']      = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
    $newPassword          = $_POST['password'] ?? '';
    $newPasswordConfirm   = $_POST['password_confirm'] ?? '';

    if ($isSelf && $values['role'] !== 'admin') {
        $errors[] = 'لا يمكنك سحب صلاحية المدير من حسابك الخاص';
    }
    if ($values['full_name'] === '') {
        $errors[] = 'الاسم الكامل مطلوب';
    } elseif (!isValidPersonName($values['full_name'])) {
        $errors[] = 'الاسم الكامل يجب أن يحتوي على أحرف ومسافات فقط، بدون أرقام أو رموز';
    }
    if ($newPassword !== '' && strlen($newPassword) < 6) $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
    if ($newPassword !== '' && $newPassword !== $newPasswordConfirm) $errors[] = 'كلمة المرور الجديدة وتأكيدها غير متطابقين';

    if (empty($errors)) {
        if ($newPassword !== '') {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, role = ?, password = ? WHERE id = ?');
            $stmt->execute([$values['full_name'], $values['role'], $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, role = ? WHERE id = ?');
            $stmt->execute([$values['full_name'], $values['role'], $id]);
        }

        if ($isSelf) {
            $_SESSION['full_name'] = $values['full_name'];
            $_SESSION['role'] = $values['role'];
        }

        flash('success', 'تم تحديث بيانات المستخدم بنجاح');
        header('Location: users.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="card-panel" style="max-width:620px;margin:auto">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="user_edit.php?id=<?= (int)$id ?>">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">الاسم الكامل *</label>
                <input type="text" name="full_name" class="form-control" pattern="[A-Za-zأ-يء-ي\s]{2,100}" title="أحرف ومسافات فقط" value="<?= e($values['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">اسم المستخدم</label>
                <input type="text" class="form-control" value="<?= e($values['username']) ?>" disabled>
                <div class="form-text">لا يمكن تغيير اسم المستخدم بعد الإنشاء.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">كلمة مرور جديدة (اختياري)</label>
                <input type="password" name="password" class="form-control" minlength="6" placeholder="اتركه فارغًا لعدم التغيير">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="password_confirm" class="form-control" minlength="6">
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">الصلاحية *</label>
                <select name="role" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="cashier" <?= $values['role'] === 'cashier' ? 'selected' : '' ?>>كاشير / موظف صيدلية</option>
                    <option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>مدير النظام</option>
                </select>
                <?php if ($isSelf): ?>
                    <input type="hidden" name="role" value="admin">
                    <div class="form-text">لا يمكنك تعديل صلاحية حسابك الخاص.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary-app px-4"><i class="bi bi-check-circle"></i> حفظ التعديلات</button>
            <a href="users.php" class="btn btn-outline-secondary px-4">إلغاء</a>
        </div>
    </form>
</div>

<script>
(function () {
    const username = document.querySelector('input[name="username"]');
    const fullName = document.querySelector('input[name="full_name"]');
    if (username) username.addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z0-9\u0600-\u06FF]/g, '');
    });
    if (fullName) fullName.addEventListener('input', function () {
        this.value = this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g, '');
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
