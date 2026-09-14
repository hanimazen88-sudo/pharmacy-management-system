<?php
/**
 * سكربت مستقل لفحص وإرسال تنبيه المخزون اليومي، مخصص للتشغيل عبر مهمة مجدولة (Cron Job)
 * بدلاً من الاعتماد فقط على زيارة المدير للوحة التحكم.
 *
 * طريقة الاستخدام على استضافة حقيقية تدعم Cron Jobs (مثل cPanel):
 *   php /path/to/pharmacy-system/cron_check_alerts.php
 *
 * لا يحتاج هذا الملف لتسجيل دخول لأنه يعمل من سطر الأوامر أو من مجدول المهام مباشرة،
 * لذا لا يُستدعى منه includes/auth_check.php عن قصد.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

checkAndSendDailyAlert($pdo);

echo "تم فحص تنبيهات المخزون بنجاح - " . date('Y-m-d H:i:s') . "\n";
