-- =========================================================
-- ترقية قاعدة بيانات نظام صيدلية السعادة (migration v3)
-- شغّل هذا الملف فقط إذا كانت قاعدة البيانات منشأة مسبقًا قبل إضافة
-- كشكول النواقص وتنبيهات البريد الإلكتروني التلقائية
-- =========================================================

USE pharmacy_db;

-- 1) جدول كشكول النواقص
CREATE TABLE IF NOT EXISTS shortage_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    quantity_to_order INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'ordered', 'received') NOT NULL DEFAULT 'pending',
    note VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 2) جدول الإعدادات
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('alerts_enabled', '0'),
('alert_recipient_email', ''),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('smtp_from_name', 'نظام إدارة صيدلية السعادة'),
('last_alert_sent_date', ''),
('last_alert_error', '');
