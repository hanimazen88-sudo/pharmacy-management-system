<?php
/**
 * دوال مشتركة لبناء استعلامات التقارير (تُستخدم من reports.php وreport_export_excel.php وreport_print.php)
 * لضمان أن يعرض التصدير والطباعة نفس البيانات المعروضة على الشاشة بالضبط.
 */

function getSalesReport(PDO $pdo, array $f) {
    $sql = "SELECT s.*, u.full_name AS employee_name
            FROM sales s
            LEFT JOIN users u ON s.user_id = u.id
            WHERE 1=1";
    $params = [];

    if (!empty($f['date_from'])) {
        $sql .= ' AND DATE(s.created_at) >= ?';
        $params[] = $f['date_from'];
    }
    if (!empty($f['date_to'])) {
        $sql .= ' AND DATE(s.created_at) <= ?';
        $params[] = $f['date_to'];
    }
    if (!empty($f['user_id'])) {
        $sql .= ' AND s.user_id = ?';
        $params[] = $f['user_id'];
    }
    if (!empty($f['search'])) {
        $sql .= ' AND (s.invoice_number LIKE ? OR s.customer_name LIKE ?)';
        $params[] = '%' . $f['search'] . '%';
        $params[] = '%' . $f['search'] . '%';
    }
    $sql .= ' ORDER BY s.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $totalAmount = 0;
    foreach ($rows as $r) $totalAmount += (float)$r['total_amount'];

    return ['rows' => $rows, 'total_amount' => $totalAmount, 'count' => count($rows)];
}

function getInventoryReport(PDO $pdo, array $f) {
    $sql = 'SELECT * FROM medicines WHERE 1=1';
    $params = [];

    if (!empty($f['category'])) {
        $sql .= ' AND category = ?';
        $params[] = $f['category'];
    }
    if (!empty($f['status'])) {
        if ($f['status'] === 'low') {
            $sql .= ' AND quantity <= min_quantity';
        } elseif ($f['status'] === 'expired') {
            $sql .= ' AND expiry_date IS NOT NULL AND expiry_date < CURDATE()';
        } elseif ($f['status'] === 'expiring_soon') {
            $sql .= ' AND expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)';
        }
    }
    $sql .= ' ORDER BY name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $totalStockValue = 0;
    $totalQuantity = 0;
    foreach ($rows as $r) {
        $totalStockValue += (float)$r['selling_price'] * (int)$r['quantity'];
        $totalQuantity += (int)$r['quantity'];
    }

    return ['rows' => $rows, 'total_stock_value' => $totalStockValue, 'total_quantity' => $totalQuantity, 'count' => count($rows)];
}

function getTopSellingReport(PDO $pdo, array $f) {
    $sql = "SELECT si.medicine_id, si.medicine_name,
                   SUM(si.quantity) AS total_qty,
                   SUM(si.subtotal) AS total_revenue
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            WHERE 1=1";
    $params = [];

    if (!empty($f['date_from'])) {
        $sql .= ' AND DATE(s.created_at) >= ?';
        $params[] = $f['date_from'];
    }
    if (!empty($f['date_to'])) {
        $sql .= ' AND DATE(s.created_at) <= ?';
        $params[] = $f['date_to'];
    }
    $sql .= ' GROUP BY si.medicine_id, si.medicine_name ORDER BY total_qty DESC LIMIT 20';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    return ['rows' => $rows, 'count' => count($rows)];
}

/** يقرأ فلاتر الطلب الحالي (GET) بصورة موحدة لجميع نقاط الدخول الثلاث */
function readReportFilters(): array {
    return [
        'type'      => $_GET['type'] ?? 'sales',
        'date_from' => trim($_GET['date_from'] ?? ''),
        'date_to'   => trim($_GET['date_to'] ?? ''),
        'user_id'   => trim($_GET['user_id'] ?? ''),
        'search'    => trim($_GET['search'] ?? ''),
        'category'  => trim($_GET['category'] ?? ''),
        'status'    => trim($_GET['status'] ?? ''),
    ];
}

/** يبني نفس رابط الفلاتر الحالية لنقاط دخول أخرى (تصدير/طباعة) */
function buildReportQueryString(array $f): string {
    return http_build_query(array_filter($f, fn($v) => $v !== ''));
}
