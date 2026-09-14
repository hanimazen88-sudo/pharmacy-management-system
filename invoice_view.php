<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'عرض الفاتورة';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT s.*, u.full_name FROM sales s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    flash('error', 'الفاتورة غير موجودة');
    header('Location: invoices.php');
    exit;
}

$itemsStmt = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
    <a href="invoices.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-right"></i> رجوع لسجل المبيعات</a>
    <div class="d-flex gap-2">
        <a href="invoice_print_thermal.php?id=<?= (int)$sale['id'] ?>" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-receipt-cutoff"></i> طباعة إيصال حراري (80mm)
        </a>
        <button onclick="window.print()" class="btn btn-primary-app"><i class="bi bi-printer"></i> طباعة A4</button>
    </div>
</div>

<div class="card-panel" style="max-width:750px;margin:auto">
    <div class="text-center mb-4">
        <i class="bi bi-capsule" style="font-size:2.4rem;color:var(--primary)"></i>
        <h4 class="fw-bold mt-2 mb-0">صيدلية السعادة</h4>
        <p class="text-muted mb-0">النصيرات، قطاع غزة</p>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <p class="mb-1"><strong>رقم الفاتورة:</strong> <?= e($sale['invoice_number']) ?></p>
            <p class="mb-1"><strong>العميل:</strong> <?= e($sale['customer_name']) ?></p>
        </div>
        <div class="col-6 text-end">
            <p class="mb-1"><strong>التاريخ:</strong> <?= date('Y/m/d H:i', strtotime($sale['created_at'])) ?></p>
            <p class="mb-1"><strong>الموظف:</strong> <?= e($sale['full_name'] ?? '—') ?></p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>الدواء</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>الإجمالي الفرعي</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['medicine_name']) ?></td>
                <td><?= (int)$item['quantity'] ?></td>
                <td><?= formatMoney($item['unit_price']) ?></td>
                <td><?= formatMoney($item['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-start fw-bold fs-5">الإجمالي الكلي</td>
                <td class="fw-bold fs-5"><?= formatMoney($sale['total_amount']) ?></td>
            </tr>
        </tfoot>
    </table>

    <p class="text-center text-muted mt-4 mb-0" style="font-size:.85rem">شكراً لتعاملكم مع صيدلية السعادة — نتمنى لكم دوام الصحة والعافية</p>
</div>

<?php require_once 'includes/footer.php'; ?>
