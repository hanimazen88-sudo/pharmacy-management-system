<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/**
 * يستدعى في بداية أي صفحة يجب أن تقتصر على المدير فقط (مثل إدارة المستخدمين).
 * الكاشير/موظف الصيدلية يُعاد توجيهه للوحة التحكم مع رسالة توضيحية.
 */
function requireAdmin() {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        $_SESSION['flash']['error'] = 'هذه الصفحة متاحة لمدير النظام فقط';
        header('Location: dashboard.php');
        exit;
    }
}

function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}
