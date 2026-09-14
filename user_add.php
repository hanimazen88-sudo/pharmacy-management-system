<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'إضافة مستخدم جديد';
$errors = [];
$values = ['username' => '', 'full_name' => '', 'role' => 'cashier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username']  = trim($_POST['username'] ?? '');
    $values['full_name'] = trim($_POST['full_name'] ?? '');
    $values['role']      = ($_POST['role'] ?? 'cashier') === 'admin' ? 'admin' : 'cashier';
    $password             = $_POST['password'] ?? '';
    $passwordConfirm      = $_POST['password_confirm'] ?? '';

    if ($values['username'] === '') {
        $errors[] = 'اسم المستخدم مطلوب';
    } elseif (!isValidUsername($values['username'])) {
        $errors[] = 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام فقط، بدون رموز أو مسافات (3 إلى 30 خانة)';
    }
    if ($values['full_name'] === '') {
        $errors[] = 'الاسم الكامل مطلوب';
    } elseif (!isValidPersonName($values['full_name'])) {
        $errors[] = 'الاسم الكامل يجب أن يحتوي على أحرف ومسافات فقط، بدون أرقام أو رموز';
    }
    if (strlen($password) < 6) $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    if ($password !== $passwordConfirm) $errors[] = 'كلمة المرور وتأكيدها غير متطابقين';

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $check->execute([$values['username']]);
        if ($check->fetch()) {
            $errors[] = 'اسم المستخدم هذا مستخدم بالفعل، الرجاء اختيار اسم آخر';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$values['username'], $hash, $values['full_name'], $values['role']]);
        flash('success', 'تمت إضافة المستخدم بنجاح');
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

    <form method="POST" action="user_add.php">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">الاسم الكامل *</label>
                <input type="text" name="full_name" class="form-control" pattern="[A-Za-zأ-يء-ي\s]{2,100}" title="أحرف ومسافات فقط" value="<?= e($values['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">اسم المستخدم *</label><div class="form-text">أحرف وأرقام فقط، بدون رموز أو مسافات.</div>
                <input type="text" name="username" class="form-control" pattern="[A-Za-z0-9أ-يء-ي]{3,30}" title="أحرف وأرقام فقط، بدون رموز أو مسافات" value="<?= e($values['username']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">كلمة المرور *</label>
                <input type="password" name="password" class="form-control" minlength="6" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">تأكيد كلمة المرور *</label>
                <input type="password" name="password_confirm" class="form-control" minlength="6" required>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">الصلاحية *</label>
                <select name="role" class="form-select">
                    <option value="cashier" <?= $values['role'] === 'cashier' ? 'selected' : '' ?>>كاشير / موظف صيدلية (POS وسجل المبيعات فقط)</option>
                    <option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>مدير النظام (صلاحية كاملة)</option>
                </select>
                <div class="form-text">الكاشير يمكنه إنشاء فواتير بيع ومشاهدة سجل المبيعات فقط، ولا يمكنه إدارة المخزون أو التقارير أو المستخدمين.</div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary-app px-4"><i class="bi bi-check-circle"></i> حفظ</button>
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
