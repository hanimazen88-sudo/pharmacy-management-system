<?php
require_once 'includes/auth_check.php';
requireAdmin();
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'طباعة بطاقات المنتجات';

$selectedIds = isset($_GET['ids']) ? array_map('intval', (array)$_GET['ids']) : [];
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM medicines WHERE 1=1';
$params = [];
if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $sql .= " AND id IN ($placeholders)";
    $params = $selectedIds;
} elseif ($search !== '') {
    $sql .= ' AND (name LIKE ? OR barcode LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY name ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

// أي دواء بدون باركود مسجَّل يُنشأ له كود فريد ويُحفظ في قاعدة البيانات فورًا،
// حتى يعمل رمز QR المطبوع فعليًا عند مسحه لاحقًا في شاشة نقطة البيع.
$genStmt = $pdo->prepare('UPDATE medicines SET barcode = ? WHERE id = ?');
foreach ($medicines as &$m) {
    if (empty($m['barcode'])) {
        $newCode = 'PH' . str_pad((string)$m['id'], 6, '0', STR_PAD_LEFT) . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $genStmt->execute([$newCode, $m['id']]);
        $m['barcode'] = $newCode;
    }
}
unset($m);

require_once 'includes/header.php';
?>

<div class="card-panel mb-3 no-print">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small fw-semibold">ابحث عن دواء لإضافته لصفحة الطباعة</label>
            <input type="text" name="q" class="form-control" style="min-width:260px" placeholder="اسم الدواء أو الباركود" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i> بحث</button>
        <a href="labels.php" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> عرض الكل</a>
        <div class="ms-auto d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small fw-semibold">عدد الأعمدة</label>
                <select id="colsSelect" class="form-select">
                    <option value="2">عمودان (بطاقة كبيرة)</option>
                    <option value="3" selected>ثلاثة أعمدة (متوسطة)</option>
                    <option value="4">أربعة أعمدة (صغيرة)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary-app" onclick="window.print()"><i class="bi bi-printer"></i> طباعة البطاقات</button>
        </div>
    </form>
    <?php if (empty($medicines)): ?>
        <p class="text-muted mt-3 mb-0">لا توجد أدوية لعرضها. ابحث أعلاه، أو ارجع لشاشة "إدارة المخزون" وحدد الأصناف المطلوبة ثم اضغط "طباعة بطاقة المنتج / QR للأصناف المحددة".</p>
    <?php else: ?>
        <p class="text-muted small mt-3 mb-0"><i class="bi bi-info-circle"></i> أي دواء لم يكن له باركود مسجَّل مسبقًا حصل تلقائيًا على كود فريد جديد يعمل مباشرة مع شاشة نقطة البيع بمجرد طباعته ولصقه على المنتج.</p>
    <?php endif; ?>
</div>

<div id="labelsSheet" class="labels-sheet cols-3">
    <?php foreach ($medicines as $m): ?>
        <div class="label-card">
            <div class="label-name"><?= e($m['name']) ?></div>
            <div class="label-qr" data-code="<?= e($m['barcode']) ?>"></div>
            <div class="label-price"><?= formatMoney($m['selling_price']) ?></div>
            <div class="label-code"><?= e($m['barcode']) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.labels-sheet {
    display: grid;
    gap: 10px;
    margin-top: 10px;
}
.labels-sheet.cols-2 { grid-template-columns: repeat(2, 1fr); }
.labels-sheet.cols-3 { grid-template-columns: repeat(3, 1fr); }
.labels-sheet.cols-4 { grid-template-columns: repeat(4, 1fr); }
.label-card {
    border: 1px dashed #9db8b3;
    border-radius: 8px;
    padding: 10px 6px;
    text-align: center;
    background: #fff;
}
.label-name { font-size: .78rem; font-weight: 700; margin-bottom: 4px; min-height: 2.6em; line-height: 1.3; overflow-wrap: anywhere; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.label-qr { display: flex; justify-content: center; margin: 4px 0; }
.label-qr canvas, .label-qr img { max-width: 100%; height: auto; }
.label-price { font-weight: 800; color: var(--primary-dark); font-size: .95rem; }
.label-code { font-size: .65rem; color: #667; letter-spacing: .5px; }

@media print {
    .no-print { display: none !important; }
    .label-card { break-inside: avoid; border: 1px solid #333; }
    body { background: #fff; }
}
</style>

<script src="assets/vendor/qrcode/qrcode.min.js"></script>
<script>
document.querySelectorAll('.label-qr').forEach(el => {
    new QRCode(el, {
        text: el.dataset.code,
        width: 90,
        height: 90,
        correctLevel: QRCode.CorrectLevel.M,
    });
});

document.getElementById('colsSelect')?.addEventListener('change', function () {
    const sheet = document.getElementById('labelsSheet');
    sheet.className = 'labels-sheet cols-' + this.value;
});
</script>

<?php require_once 'includes/footer.php'; ?>
