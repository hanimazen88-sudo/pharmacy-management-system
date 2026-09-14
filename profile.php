<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'الملف الشخصي';
$userId = (int)$_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare('SELECT id, username, full_name, role, is_active, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($fullName === '' || !isValidPersonName($fullName)) {
        $errors[] = 'الاسم الكامل يجب أن يحتوي على أحرف ومسافات فقط.';
    }
    if ($password !== '') {
        if (strlen($password) < 6) $errors[] = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.';
        if ($password !== $passwordConfirm) $errors[] = 'كلمة المرور الجديدة وتأكيدها غير متطابقين.';
    }

    if (!$errors) {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE users SET full_name = ?, password = ? WHERE id = ?');
            $stmt->execute([$fullName, $hash, $userId]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET full_name = ? WHERE id = ?');
            $stmt->execute([$fullName, $userId]);
        }
        $_SESSION['full_name'] = $fullName;
        flash('success', 'تم تحديث بيانات الملف الشخصي بنجاح');
        header('Location: profile.php');
        exit;
    }
    $user['full_name'] = $fullName;
}

require_once 'includes/header.php';
?>
<div class="profile-page-grid">
    <div class="card-panel profile-card">
        <div class="profile-card-title">
            <span class="profile-avatar profile-avatar-xl"><i class="bi bi-person-fill"></i></span>
            <div><h5 class="mb-1">الملف الشخصي</h5><small class="text-muted">بيانات الحساب المستخدم حاليًا</small></div>
        </div>
        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="profile.php">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">الاسم الكامل *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">اسم المستخدم</label>
                    <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">الصلاحية</label>
                    <input type="text" class="form-control" value="<?= $user['role'] === 'admin' ? 'مدير النظام' : 'كاشير / موظف' ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">تاريخ إنشاء الحساب</label>
                    <input type="text" class="form-control" value="<?= e(date('Y/m/d', strtotime($user['created_at']))) ?>" disabled>
                </div>
            </div>

            <div id="password" class="profile-password-section">
                <h6><i class="bi bi-shield-lock"></i> تغيير كلمة المرور</h6>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">كلمة المرور الجديدة</label><input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" placeholder="اتركها فارغة لعدم التغيير"></div>
                    <div class="col-md-6"><label class="form-label">تأكيد كلمة المرور</label><input type="password" name="password_confirm" class="form-control" minlength="6" autocomplete="new-password"></div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary-app px-4 mt-4"><i class="bi bi-check-circle"></i> حفظ التعديلات</button>
        </form>
    </div>
</div>
<script>
(function(){
 const name=document.querySelector('input[name="full_name"]');
 if(name) name.addEventListener('input',function(){this.value=this.value.replace(/[^A-Za-z\u0600-\u06FF\s]/g,'');});
})();
</script>
<?php require_once 'includes/footer.php'; ?>
