<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/mailer.php';

// إعدادات البريد الافتراضية للصيدلية: المرسل هو mqrinawi1990@gmail.com والمستقبل mutazyaser1@gmail.com.
// لا يتم حفظ كلمة مرور SMTP داخل ملفات المشروع؛ يجب إدخال App Password مرة واحدة من شاشة الإعدادات.
$pharmacyMailDefaults = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_username' => 'mqrinawi1990@gmail.com',
    'smtp_from_email' => 'mqrinawi1990@gmail.com',
    'smtp_from_name' => 'نظام إدارة صيدلية السعادة',
    'alert_recipient_email' => 'mutazyaser1@gmail.com',
];
$currentMailSettings = getSettings($pdo);
// تصحيح الإعدادات القديمة التي كانت تستخدم البريد المستقبل كحساب SMTP.
if (($currentMailSettings['smtp_username'] ?? '') !== 'mqrinawi1990@gmail.com' || ($currentMailSettings['alert_recipient_email'] ?? '') !== 'mutazyaser1@gmail.com') {
    // The previous installation could have stored credentials for the recipient account.
    // Clear that password when switching the SMTP account so an old password can never
    // be reused accidentally with mqrinawi1990@gmail.com.
    updateSettings($pdo, $pharmacyMailDefaults + ['smtp_password' => '']);
}

$pageTitle = 'إعدادات تنبيهات البريد الإلكتروني';
$errors = [];
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // زر الحفظ وزر الاختبار يستقبلان نفس الحقول حتى لا يختبر النظام
    // إعدادات لم تُحفظ بعد (وهي كانت سبب ظهور خطأ SMTP Host في بعض الحالات).
    $postedValues = [
        'alerts_enabled'        => isset($_POST['alerts_enabled']) ? '1' : '0',
        'alert_recipient_email' => trim($_POST['alert_recipient_email'] ?? 'mutazyaser1@gmail.com'),
        'smtp_host'             => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
        'smtp_port'             => trim($_POST['smtp_port'] ?? '587'),
        'smtp_username'         => trim($_POST['smtp_username'] ?? 'mqrinawi1990@gmail.com'),
        'smtp_from_email'       => trim($_POST['smtp_from_email'] ?? 'mqrinawi1990@gmail.com'),
        'smtp_from_name'        => trim($_POST['smtp_from_name'] ?? 'نظام إدارة صيدلية السعادة'),
    ];
    if (trim($_POST['smtp_password'] ?? '') !== '') {
        $postedValues['smtp_password'] = preg_replace('/\s+/', '', trim($_POST['smtp_password']));
    } elseif (isset($_POST['clear_smtp_password'])) {
        $postedValues['smtp_password'] = '';
    }
    if ($postedValues['smtp_host'] === '') {
        $postedValues['smtp_host'] = 'smtp.gmail.com';
    }
    if ($postedValues['smtp_port'] === '') {
        $postedValues['smtp_port'] = '587';
    }
    if ($postedValues['smtp_from_email'] === '' && $postedValues['smtp_username'] !== '' && filter_var($postedValues['smtp_username'], FILTER_VALIDATE_EMAIL)) {
        $postedValues['smtp_from_email'] = $postedValues['smtp_username'];
    }

    if (isset($_POST['save_settings']) || isset($_POST['send_test'])) {
        if ($postedValues['alerts_enabled'] === '1' && $postedValues['alert_recipient_email'] === '') {
            $errors[] = 'الرجاء إدخال البريد الإلكتروني المستقبِل للتنبيهات قبل تفعيل الخاصية';
        } elseif ($postedValues['alert_recipient_email'] !== '' && !filter_var($postedValues['alert_recipient_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد الإلكتروني المستقبِل للتنبيهات غير صالح';
        }
        if (!ctype_digit($postedValues['smtp_port']) || (int)$postedValues['smtp_port'] < 1 || (int)$postedValues['smtp_port'] > 65535) {
            $errors[] = 'منفذ SMTP يجب أن يكون رقمًا بين 1 و65535';
        }
        if ($postedValues['smtp_username'] !== '' && !filter_var($postedValues['smtp_username'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'اسم مستخدم SMTP يجب أن يكون بريدًا إلكترونيًا صحيحًا';
        }
        if ($postedValues['smtp_from_email'] !== '' && !filter_var($postedValues['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'البريد المُرسِل (From) غير صالح';
        }
        if ($postedValues['alerts_enabled'] === '1' && $postedValues['smtp_host'] === '') {
            $errors[] = 'أدخل خادم SMTP قبل تفعيل التنبيهات';
        }
    }

    if (isset($_POST['save_settings']) && empty($errors)) {
        updateSettings($pdo, $postedValues);
        flash('success', 'تم حفظ إعدادات التنبيهات بنجاح');
        header('Location: settings.php');
        exit;
    }

    if (isset($_POST['send_test']) && empty($errors)) {
        // احفظ إعدادات الاختبار أولًا، مع الاحتفاظ بكلمة المرور القديمة إذا لم تُدخل كلمة مرور جديدة.
        updateSettings($pdo, $postedValues);
        $settingsForTest = getSettings($pdo);
        $result = sendLowStockAlertEmail($pdo, $settingsForTest, true);
        $testResult = $result === true ? 'success' : $result;
        if ($result !== true) {
            updateSettings($pdo, ['last_alert_error' => (string)$result]);
        } else {
            updateSettings($pdo, ['last_alert_error' => '']);
        }
    }
}

$settings = array_merge($pharmacyMailDefaults, getSettings($pdo));

require_once 'includes/header.php';
?>

<div class="card-panel" style="max-width:720px;margin:auto">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <?php if ($testResult === 'success'): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> تم إرسال رسالة تجريبية بنجاح، تحقق من صندوق الوارد.</div>
    <?php elseif ($testResult !== null): ?>
        <div class="alert alert-danger"><i class="bi bi-x-circle"></i> <?= e($testResult) ?></div>
    <?php endif; ?>
    <?php if (!empty($settings['last_alert_error'])): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> آخر خطأ في التنبيه التلقائي: <?= e($settings['last_alert_error']) ?></div>
    <?php endif; ?>

    <p class="text-muted">عند التفعيل، يرسل النظام تلقائيًا رسالة بريد إلكتروني واحدة يوميًا (كحد أقصى) بملخص الأصناف منخفضة المخزون والقريبة من انتهاء الصلاحية، فور دخول المدير للوحة التحكم إذا لم يُرسَل تنبيه بعد لهذا اليوم.</p>

    <form method="POST" action="settings.php" id="emailSettingsForm">
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" name="alerts_enabled" id="alertsEnabled" <?= ($settings['alerts_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <label class="form-check-label fw-bold" for="alertsEnabled">تفعيل تنبيهات البريد الإلكتروني اليومية</label>
        </div>

        <div class="alert alert-info border-0" style="background:#eef8f4;color:#28584b">
            <i class="bi bi-magic"></i> تم تجهيز إعدادات Gmail تلقائيًا. <b>المرسل:</b> mqrinawi1990@gmail.com &nbsp; | &nbsp; <b>المستقبل:</b> mutazyaser1@gmail.com.
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">البريد الإلكتروني المستقبِل للتنبيهات *</label>
                <input type="email" name="alert_recipient_email" class="form-control" value="<?= e($settings['alert_recipient_email'] ?? 'mutazyaser1@gmail.com') ?>" placeholder="البريد المستقبِل">
            </div>

            <div class="col-md-8">
                <label class="form-label fw-semibold">البريد المرسل / اسم المستخدم SMTP *</label>
                <input type="email" name="smtp_username" class="form-control" value="<?= e($settings['smtp_username'] ?? 'mqrinawi1990@gmail.com') ?>" placeholder="البريد المرسل">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">كلمة مرور التطبيق *</label>
                <input type="password" name="smtp_password" class="form-control" placeholder="App Password من Google" autocomplete="new-password">
                <div class="form-text">ألصق App Password حتى لو كانت بها مسافات؛ النظام يحذف المسافات تلقائيًا.</div>
                <label class="small text-danger mt-1 d-flex align-items-center gap-1"><input type="checkbox" name="clear_smtp_password" value="1"> مسح كلمة المرور المحفوظة</label>
            </div>

            <div class="col-12">
                <details class="smtp-advanced-box">
                    <summary><i class="bi bi-gear"></i> الإعدادات المتقدمة (يمكن تعديلها)</summary>
                    <div class="row g-3 mt-1">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">خادم SMTP (Host)</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= e($settings['smtp_host'] ?? 'smtp.gmail.com') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">المنفذ (Port)</label>
                            <input type="number" name="smtp_port" class="form-control" value="<?= e($settings['smtp_port'] ?? '587') ?>">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">البريد المُرسِل (From)</label>
                            <input type="email" name="smtp_from_email" class="form-control" value="<?= e(($settings['smtp_from_email'] ?? '') ?: ($settings['smtp_username'] ?? '')) ?>" placeholder="سيُستخدم بريد SMTP تلقائيًا">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">اسم المُرسِل</label>
                            <input type="text" name="smtp_from_name" class="form-control" value="<?= e($settings['smtp_from_name'] ?? 'نظام إدارة صيدلية السعادة') ?>">
                        </div>
                    </div>
                </details>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <button type="submit" name="save_settings" value="1" class="btn btn-primary-app px-4"><i class="bi bi-check-circle"></i> حفظ الإعدادات</button>
            <button type="submit" name="send_test" value="1" class="btn btn-outline-dark px-4"><i class="bi bi-envelope-check"></i> حفظ وإرسال رسالة تجريبية</button>
        </div>
    </form>
    <?php if (empty($settings['smtp_password'])): ?>
        <div class="alert alert-warning mt-3"><i class="bi bi-key"></i> لم يتم حفظ App Password لحساب <b>mqrinawi1990@gmail.com</b>. لن يستطيع Gmail إرسال الرسائل قبل إدخال App Password صحيحة.</div>
    <?php endif; ?>
    <p class="text-muted small mt-3">
        <i class="bi bi-info-circle"></i>
        لاستخدام Gmail: خادم SMTP هو <b>smtp.gmail.com</b> والمنفذ <b>587</b> مع STARTTLS. استخدم <b>App Password</b> بدل كلمة مرور حساب Google العادية. زر الاختبار يحفظ القيم المدخلة أولًا ثم يختبر الإرسال مباشرة.
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>
