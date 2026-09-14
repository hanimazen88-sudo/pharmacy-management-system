<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['items'])) {
    flash('error', 'الرجاء إضافة دواء واحد على الأقل قبل إتمام عملية البيع');
    header('Location: sales.php');
    exit;
}

$customerName = trim($_POST['customer_name'] ?? '') ?: 'عميل نقدي';
$items = $_POST['items'];

try {
    $pdo->beginTransaction();

    // التحقق من توفر الكمية لكل صنف وقفل الصفوف
    $saleItems = [];
    $total = 0;

    foreach ($items as $row) {
        $medicineId = (int)$row['medicine_id'];
        $qty = (int)$row['quantity'];
        if ($qty < 1) continue;

        $stmt = $pdo->prepare('SELECT * FROM medicines WHERE id = ? FOR UPDATE');
        $stmt->execute([$medicineId]);
        $medicine = $stmt->fetch();

        if (!$medicine) {
            throw new Exception('أحد الأدوية المختارة لم يعد موجوداً');
        }
        if ($medicine['quantity'] < $qty) {
            throw new Exception('تنبيه: الكمية المطلوبة من "' . $medicine['name'] . '" هي ' . $qty . ' بينما المتوفر فقط ' . $medicine['quantity'] . '. لم يتم إتمام البيع.');
        }

        if (!empty($medicine['expiry_date']) && strtotime($medicine['expiry_date']) < strtotime(date('Y-m-d'))) {
            throw new Exception('تنبيه: الدواء "' . $medicine['name'] . '" منتهي الصلاحية بتاريخ ' . $medicine['expiry_date'] . '، ولا يمكن بيعه.');
        }

        $subtotal = $medicine['selling_price'] * $qty;
        $total += $subtotal;

        $saleItems[] = [
            'medicine_id'   => $medicineId,
            'medicine_name' => $medicine['name'],
            'quantity'      => $qty,
            'unit_price'    => $medicine['selling_price'],
            'subtotal'      => $subtotal,
        ];
    }

    if (empty($saleItems)) {
        throw new Exception('لم يتم اختيار أي أدوية صالحة لإتمام البيع');
    }

    $invoiceNumber = generateInvoiceNumber();

    $stmt = $pdo->prepare('INSERT INTO sales (invoice_number, customer_name, total_amount, user_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$invoiceNumber, $customerName, $total, $_SESSION['user_id']]);
    $saleId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, medicine_id, medicine_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
    $updateStmt = $pdo->prepare('UPDATE medicines SET quantity = quantity - ? WHERE id = ?');

    foreach ($saleItems as $item) {
        $itemStmt->execute([$saleId, $item['medicine_id'], $item['medicine_name'], $item['quantity'], $item['unit_price'], $item['subtotal']]);
        $updateStmt->execute([$item['quantity'], $item['medicine_id']]);
    }

    $pdo->commit();

    flash('success', 'تم إنشاء الفاتورة رقم ' . $invoiceNumber . ' بنجاح');
    header('Location: invoice_view.php?id=' . $saleId);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', $e->getMessage());
    header('Location: sales.php');
    exit;
}
