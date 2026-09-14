<?php
/**
 * دوال مساعدة عامة
 */

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


function isValidUsername(string $username): bool {
    return (bool)preg_match('/^[\p{L}\p{N}]{3,30}$/u', $username);
}

function isValidPersonName(string $name): bool {
    return (bool)preg_match('/^[\p{L}\s]{2,100}$/u', $name);
}

function formatMoney($value) {
    return number_format((float)$value, 2) . ' ₪';
}

function formatDate($date) {
    if (!$date) return '—';
    return date('Y/m/d', strtotime($date));
}

function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function isExpiringSoon($expiryDate, $days = 60) {
    if (!$expiryDate) return false;
    $diff = (strtotime($expiryDate) - time()) / 86400;
    return $diff >= 0 && $diff <= $days;
}

function isExpired($expiryDate) {
    if (!$expiryDate) return false;
    return strtotime($expiryDate) < strtotime(date('Y-m-d'));
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
