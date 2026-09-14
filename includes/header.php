<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?>صيدلية السعادة</title>
<link href="assets/vendor/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- الشريط الجانبي -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="assets/img/pharmacy-saadah-logo.png" alt="شعار صيدلية السعادة" class="sidebar-pharmacy-logo">
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> لوحة التحكم
            </a>
            <a href="medicines.php" class="<?= in_array($currentPage, ['medicines.php','medicine_add.php','medicine_edit.php']) ? 'active' : '' ?>">
                <i class="bi bi-box-seam"></i> إدارة المخزون
            </a>
            <a href="sales.php" class="<?= $currentPage === 'sales.php' ? 'active' : '' ?>">
                <i class="bi bi-cart-plus"></i> فاتورة بيع جديدة
            </a>
            <a href="invoices.php" class="<?= in_array($currentPage, ['invoices.php','invoice_view.php']) ? 'active' : '' ?>">
                <i class="bi bi-receipt"></i> سجل المبيعات
            </a>
            <?php if (isAdmin()): ?>
            <a href="reports.php" class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i> التقارير
            </a>
            <a href="shortage_list.php" class="<?= $currentPage === 'shortage_list.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> كشكول النواقص
            </a>
            <a href="labels.php" class="<?= $currentPage === 'labels.php' ? 'active' : '' ?>">
                <i class="bi bi-upc-scan"></i>  طباعة ملصق المنتجات(label)
            </a>
            <a href="users.php" class="<?= in_array($currentPage, ['users.php','user_add.php','user_edit.php']) ? 'active' : '' ?>">
                <i class="bi bi-people"></i> إدارة المستخدمين
            </a>
            <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-envelope-exclamation"></i> تنبيهات البريد الإلكتروني
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-role-badge">
                <span class="badge <?= isAdmin() ? 'badge-admin' : 'badge-cashier' ?>">
                    <?= isAdmin() ? 'مدير النظام' : 'كاشير / موظف' ?>
                </span>
            </div>
            <div class="sidebar-profile-hint"><i class="bi bi-person-circle"></i> استخدم قائمة الحساب أعلى الشاشة</div>
        </div>
    </aside>

    <!-- المحتوى الرئيسي -->
    <div class="main-content">
        <header class="topbar">
            <button class="btn-toggle-sidebar d-lg-none" id="toggleSidebar">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="page-title mb-0"><?= isset($pageTitle) ? e($pageTitle) : '' ?></h4>

            <div class="profile-menu dropdown">
                <button class="profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="حسابي">
                    <span class="profile-avatar"><i class="bi bi-person-fill"></i></span>
                    <span class="profile-trigger-text">
                        <strong><?= e($_SESSION['full_name'] ?? 'المستخدم') ?></strong>
                        <small><?= isAdmin() ? 'مدير النظام' : 'كاشير / موظف' ?></small>
                    </span>
                    <i class="bi bi-chevron-down profile-chevron"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-start profile-dropdown">
                    <div class="profile-dropdown-head">
                        <span class="profile-avatar profile-avatar-lg"><i class="bi bi-person-fill"></i></span>
                        <div>
                            <strong><?= e($_SESSION['full_name'] ?? 'المستخدم') ?></strong>
                            <small>@<?= e($_SESSION['username'] ?? '') ?></small>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="profile.php"><i class="bi bi-person-vcard"></i> الملف الشخصي</a>
                    <a class="dropdown-item" href="profile.php#password"><i class="bi bi-key"></i> تغيير كلمة المرور</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item profile-logout" href="logout.php"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?= e($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= e($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
