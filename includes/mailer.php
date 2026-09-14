<?php
/**
 * دوال إرسال تنبيهات البريد الإلكتروني التلقائية للمخزون المنخفض وقرب انتهاء الصلاحية.
 * مستوحاة من ميزة التنبيهات التلقائية في نظام Square (راجع تقرير المشروع، الفصل الثاني، البند 2.1.5).
 */

require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/** يقرأ جميع الإعدادات المخزَّنة في جدول settings كمصفوفة مفتاح => قيمة */
function getSettings(PDO $pdo): array {
    $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }

    // إعدادات Gmail القياسية تُستخدم تلقائيًا إذا كانت الخانات الأساسية فارغة.
    // يمكن للمدير تعديلها لاحقًا من شاشة الإعدادات دون الحاجة لتعديل الكود.
    $defaults = [
        'smtp_host'      => 'smtp.gmail.com',
        'smtp_port'      => '587',
        'smtp_from_name' => 'نظام إدارة صيدلية السعادة',
        'smtp_username'  => 'mqrinawi1990@gmail.com',
        'smtp_from_email'=> 'mqrinawi1990@gmail.com',
        'alert_recipient_email' => 'hanimazen88@gmail.com',
    ];
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key]) || trim((string)$settings[$key]) === '') {
            $settings[$key] = $value;
        }
    }
    // Force the pharmacy's requested sender/recipient so old installations do not
    // accidentally authenticate with the recipient account. These remain editable
    // from settings.php afterwards.
    if (($settings['smtp_username'] ?? '') === 'hanimazen88@gmail.com') {
        $settings['smtp_username'] = 'mqrinawi1990@gmail.com';
    }
    if (($settings['smtp_from_email'] ?? '') === 'hanimazen88@gmail.com') {
        $settings['smtp_from_email'] = 'mqrinawi1990@gmail.com';
    }
    if (($settings['alert_recipient_email'] ?? '') === '') {
        $settings['alert_recipient_email'] = 'hanimazen88@gmail.com';
    }
    if (empty($settings['smtp_from_email']) && !empty($settings['smtp_username'])) {
        $settings['smtp_from_email'] = trim((string)$settings['smtp_username']);
    }
    return $settings;
}

/** يحدّث إعدادًا واحدًا أو أكثر في جدول settings */
function updateSettings(PDO $pdo, array $values): void {
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    foreach ($values as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

/**
 * يبني ويرسل بريدًا إلكترونيًا بملخص الأصناف منخفضة المخزون والقريبة من انتهاء الصلاحية.
 * يعيد true عند نجاح الإرسال، أو نص رسالة الخطأ عند الفشل.
 */
function sendLowStockAlertEmail(PDO $pdo, array $settings, bool $isTest = false) {
    $lowStock = $pdo->query('SELECT * FROM medicines WHERE quantity <= min_quantity ORDER BY quantity ASC')->fetchAll();
    $expiring = $pdo->query("SELECT * FROM medicines WHERE expiry_date IS NOT NULL
                              AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                              ORDER BY expiry_date ASC")->fetchAll();

    if (!$isTest && empty($lowStock) && empty($expiring)) {
        return 'لا توجد أصناف منخفضة المخزون أو قريبة من الانتهاء حاليًا، لم يُرسَل أي تنبيه';
    }

    $rowsHtml = '';
    foreach ($lowStock as $m) {
        $rowsHtml .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($m['name']) . '</td>'
                   . '<td style="padding:6px;border:1px solid #ddd;color:#c0392b;font-weight:bold;">' . (int)$m['quantity'] . '</td>'
                   . '<td style="padding:6px;border:1px solid #ddd;">' . (int)$m['min_quantity'] . '</td></tr>';
    }
    $expHtml = '';
    foreach ($expiring as $m) {
        $expHtml .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($m['name']) . '</td>'
                  . '<td style="padding:6px;border:1px solid #ddd;color:#d35400;font-weight:bold;">' . htmlspecialchars($m['expiry_date']) . '</td></tr>';
    }

    $body = '<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;">'
          . '<h2 style="color:#0d6350;">تنبيه يومي — صيدلية السعادة</h2>';

    if ($isTest) {
        $body .= '<p style="color:#888;">(هذه رسالة تجريبية أرسلها المدير يدويًا للتأكد من صحة إعدادات البريد)</p>';
    }

    if (!empty($lowStock)) {
        $body .= '<h3>أصناف منخفضة المخزون (' . count($lowStock) . ')</h3>'
                . '<table style="border-collapse:collapse;width:100%;"><tr style="background:#e6f5f1;">'
                . '<th style="padding:6px;border:1px solid #ddd;">الدواء</th><th style="padding:6px;border:1px solid #ddd;">الكمية الحالية</th><th style="padding:6px;border:1px solid #ddd;">الحد الأدنى</th></tr>'
                . $rowsHtml . '</table>';
    }
    if (!empty($expiring)) {
        $body .= '<h3 style="margin-top:20px;">أصناف قريبة من انتهاء الصلاحية (' . count($expiring) . ')</h3>'
                . '<table style="border-collapse:collapse;width:100%;"><tr style="background:#fdf3e3;">'
                . '<th style="padding:6px;border:1px solid #ddd;">الدواء</th><th style="padding:6px;border:1px solid #ddd;">تاريخ الانتهاء</th></tr>'
                . $expHtml . '</table>';
    }
    $body .= '<p style="margin-top:20px;color:#888;font-size:12px;">رسالة مرسلة تلقائيًا من نظام إدارة صيدلية السعادة.</p></div>';

    $mail = new PHPMailer(true);
    try {
        $host = trim((string)($settings['smtp_host'] ?? ''));
        $username = trim((string)($settings['smtp_username'] ?? ''));
        $password = preg_replace('/\s+/', '', (string)($settings['smtp_password'] ?? ''));
        // Gmail App Passwords are sometimes pasted with spaces (xxxx xxxx xxxx xxxx).
        // PHPMailer must receive the 16-character value without spaces.
        if ($username === 'mqrinawi1990@gmail.com' && $password !== '' && strlen($password) !== 16) {
            return 'كلمة مرور التطبيق لحساب mqrinawi1990@gmail.com يجب أن تكون App Password مكونة من 16 حرفًا/رقمًا بعد إزالة المسافات.';
        }
        $recipient = trim((string)($settings['alert_recipient_email'] ?? ''));
        $fromEmail = trim((string)($settings['smtp_from_email'] ?? ''));
        $fromName = trim((string)($settings['smtp_from_name'] ?? 'صيدلية السعادة')) ?: 'صيدلية السعادة';
        $port = (int)($settings['smtp_port'] ?? 587);

        if ($host === '') return 'إعدادات البريد غير مكتملة: أدخل خادم SMTP (Host).';
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) return 'البريد المستقبِل للتنبيهات غير صالح.';
        if ($username === 'mqrinawi1990@gmail.com' && $password === '') {
            return 'لم يتم إدخال App Password لحساب الإرسال mqrinawi1990@gmail.com. أنشئ App Password من Google (مع تفعيل التحقق بخطوتين) ثم أدخلها في إعدادات البريد.';
        }
        // إذا كان حقل From فارغًا، نستخدم حساب SMTP نفسه لتجنب خطأ PHPMailer: Invalid address: (From).
        if ($fromEmail === '') $fromEmail = $username;
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return 'البريد المُرسِل (From) غير صالح. أدخل بريدًا صحيحًا، ويفضل أن يطابق اسم مستخدم SMTP.';
        }
        if ($port < 1 || $port > 65535) return 'منفذ SMTP غير صالح.';

        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = $username !== '';
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';
        if ($port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
            $mail->SMTPAutoTLS = true;
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipient);

        $mail->isHTML(true);
        $mail->Subject = ($isTest ? '[تجريبي] ' : '') . 'تنبيه المخزون - صيدلية السعادة - ' . date('Y-m-d');
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        
        $smtpError = trim((string)$mail->ErrorInfo);
        if (stripos($smtpError, 'Could not authenticate') !== false || stripos($smtpError, 'authentication') !== false) {
            return 'فشل المصادقة مع Gmail: تأكد أن البريد المرسل هو mqrinawi1990@gmail.com وأن كلمة المرور هي App Password المكونة من 16 حرفًا، مع تفعيل التحقق بخطوتين في Google.';
        }
        return 'فشل إرسال البريد: ' . $smtpError;
    }
}

/**
 * يُستدعى من لوحة التحكم مرة واحدة يوميًا على الأكثر (لتفادي إغراق البريد الوارد)،
 * ويرسل تنبيهًا تلقائيًا فقط إذا كانت الخاصية مفعّلة ولم يُرسَل تنبيه اليوم بعد.
 */
function checkAndSendDailyAlert(PDO $pdo): void {
    $settings = getSettings($pdo);

    if (($settings['alerts_enabled'] ?? '0') !== '1') {
        return;
    }
    if (empty($settings['alert_recipient_email']) || empty($settings['smtp_host'])) {
        return;
    }
    if (($settings['last_alert_sent_date'] ?? '') === date('Y-m-d')) {
        return; // أُرسل تنبيه اليوم بالفعل
    }

    $result = sendLowStockAlertEmail($pdo, $settings, false);
    if ($result === true) {
        updateSettings($pdo, ['last_alert_sent_date' => date('Y-m-d'), 'last_alert_error' => '']);
    } else {
        updateSettings($pdo, ['last_alert_error' => (string)$result]);
    }
}
