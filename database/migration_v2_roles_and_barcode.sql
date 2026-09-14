-- =========================================================
-- ترقية قاعدة بيانات نظام صيدلية السعادة (migration v2)
-- شغّل هذا الملف فقط إذا كانت قاعدة البيانات منشأة مسبقًا بالإصدار الأول
-- (قبل إضافة الصلاحيات والبيع عبر QR وطباعة الليبل)
-- =========================================================

USE pharmacy_db;

-- 1) إضافة عمود الصلاحية وحالة التفعيل لجدول المستخدمين
ALTER TABLE users
    ADD COLUMN role ENUM('admin', 'cashier') NOT NULL DEFAULT 'cashier' AFTER full_name,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;

-- 2) رفع أول مستخدم موجود (الأقدم) إلى صلاحية مدير تلقائيًا حتى لا يفقد أحد الوصول للنظام
UPDATE users SET role = 'admin' WHERE id = (SELECT MIN(id) FROM (SELECT id FROM users) AS t);

-- 3) ضمان عدم تكرار الباركود بين صنفين مختلفين (تجاهل هذا السطر إذا كانت لديك بيانات مكررة بالفعل)
ALTER TABLE medicines
    ADD UNIQUE KEY uniq_barcode (barcode);

-- ملاحظة: إذا فشلت الخطوة الثالثة بسبب وجود باركود مكرر في بياناتك الحالية،
-- نفّذ أولاً: SELECT barcode, COUNT(*) c FROM medicines GROUP BY barcode HAVING c > 1;
-- وصحّح القيم المكررة يدويًا، ثم أعد تنفيذ الأمر أعلاه.
