<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'الرجاء إدخال اسم المستخدم وكلمة المرور';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['is_active'] === 0) {
                $error = 'تم إيقاف هذا الحساب. يرجى مراجعة مدير النظام';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['role']      = $user['role'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول - صيدلية السعادة</title>
<link href="assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
<div class="login-wrapper">
    <div class="login-card login-card-split">
        <section class="login-brand-panel">
            <div class="brand-watermark"><i class="bi bi-plus-lg"></i></div>
            <div class="login-brand-content">
                <div class="login-brand-logo-login capsule-only-logo">
                    <img src="assets/img/logo.png" alt="شعار صيدلية السعادة">
                </div>
                <div class="login-brand-name">صيدلية السعادة</div>
                <h1>مرحبًا بك في نظام إدارة الصيدلية</h1>
                <h2>نهتم بصحتك... لأن سعادتك هي أولويتنا</h2>
                <p>نظام متكامل لإدارة الأدوية والمخزون والمبيعات والفواتير بسهولة وأمان، لتجعل إدارة صيدليتك أكثر دقة وسهولة.</p>
                <div class="login-features">
                    <div><i class="bi bi-shield-check"></i><span>أمان البيانات</span></div>
                    <div><i class="bi bi-graph-up-arrow"></i><span>تقارير دقيقة</span></div>
                    <div><i class="bi bi-box-seam"></i><span>إدارة المخزون</span></div>
                </div>
            </div>
            <div class="pharmacy-illustration" aria-hidden="true">
                <div class="pharmacy-shelf shelf-one"><span></span><span></span><span></span><span></span><span></span></div>
                <div class="pharmacy-shelf shelf-two"><span></span><span></span><span></span><span></span><span></span></div>
                <div class="pharmacy-counter"><b>الصيدلية</b><i class="bi bi-plus-lg"></i></div>
            </div>
        </section>

        <section class="login-form-panel">
            <div class="login-form-inner">
                <div class="login-heading">
                    <span>أهلًا وسهلًا</span>
                    <h2>تسجيل الدخول</h2>
                    <p>أدخل بياناتك للوصول إلى حسابك</p>
                    <div class="heading-line"></div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger login-alert" role="alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="login-form" autocomplete="on">
                    <div class="login-input-group">
                        <label for="username">اسم المستخدم</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="login-input-group">
                        <label for="password">كلمة المرور</label>
                        <div class="login-input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="إظهار كلمة المرور"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>

                    <div class="login-options">
                        <label class="remember-me"><input type="checkbox" name="remember" value="1"> <span>تذكرني</span></label>
                        <span class="login-help"><i class="bi bi-shield-lock"></i> دخول آمن ومشفر</span>
                    </div>

                    <button type="submit" class="login-submit">
                        <span>تسجيل الدخول</span>
                        <i class="bi bi-arrow-left"></i>
                    </button>
                </form>

                <div class="login-info-box">
                    <i class="bi bi-info-circle"></i>
                    <div><strong>نظام إدارة الصيدلية</strong><br><span>إدارة المبيعات والمخزون والصلاحيات من مكان واحد.</span></div>
                </div>

                <div class="login-footer">
                    <span>© <?= date('Y') ?> صيدلية السعادة</span>
                    <span>النصيرات</span>
                </div>
            </div>
        </section>
    </div>
</div>
<script>
(function () {
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', function () {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            this.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            this.setAttribute('aria-label', visible ? 'إظهار كلمة المرور' : 'إخفاء كلمة المرور');
        });
    }
})();
</script>
</body>
</html>
