<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

$pageTitle = 'فاتورة بيع جديدة';
require_once 'includes/header.php';
?>

<div class="card-panel">
    <form method="POST" action="sales_process.php" id="saleForm">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">اسم العميل (اختياري)</label>
                <input type="text" name="customer_name" class="form-control" placeholder="عميل نقدي">
            </div>
            <div class="col-md-8 position-relative">
                <label class="form-label fw-semibold">ابحث عن دواء لإضافته للفاتورة</label>
                <input type="text" id="medicineSearch" class="form-control" placeholder="اكتب اسم الدواء أو الباركود..." autocomplete="off">
                <div id="searchResults" class="medicine-search-results d-none"></div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold"><i class="bi bi-upc-scan"></i> بيع سريع عبر الباركود / QR</label>
                <input type="text" id="barcodeInput" class="form-control" placeholder="وجّه المؤشر هنا ثم امسح الباركود أو QR بجهاز القارئ..." autocomplete="off">
                <div class="form-text">يعمل مباشرة مع أي قارئ باركود/QR متصل بالجهاز (USB أو بلوتوث) — يضيف الصنف تلقائيًا فور المسح.</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-outline-dark w-100" id="cameraScanBtn" data-bs-toggle="modal" data-bs-target="#cameraScanModal">
                    <i class="bi bi-camera"></i> مسح عبر كاميرا الجهاز
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="cartTable">
                <thead>
                    <tr>
                        <th>الدواء</th>
                        <th style="width:120px">سعر الوحدة</th>
                        <th style="width:130px">الكمية</th>
                        <th style="width:130px">الإجمالي الفرعي</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <tr id="emptyCartRow">
                        <td colspan="5" class="text-center text-muted py-4">لم يتم إضافة أي أدوية بعد</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-start fw-bold fs-5">الإجمالي الكلي</td>
                        <td colspan="2" class="fw-bold fs-5" id="grandTotal">0.00 ₪</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div id="hiddenInputs"></div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary-app px-4" id="submitSaleBtn" disabled>
                <i class="bi bi-check-circle"></i> إتمام عملية البيع
            </button>
            <a href="dashboard.php" class="btn btn-outline-secondary px-4">إلغاء</a>
        </div>
    </form>
</div>

<!-- نافذة المسح عبر الكاميرا -->
<div class="modal fade" id="cameraScanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-camera"></i> امسح الباركود أو رمز QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cameraReader" style="width:100%"></div>
                <div id="cameraScanStatus" class="text-muted small mt-2 text-center"></div>
            </div>
        </div>
    </div>
</div>

<script>
const searchInput   = document.getElementById('medicineSearch');
const searchResults = document.getElementById('searchResults');
const cartBody       = document.getElementById('cartBody');
const emptyCartRow   = document.getElementById('emptyCartRow');
const grandTotalEl   = document.getElementById('grandTotal');
const hiddenInputs   = document.getElementById('hiddenInputs');
const submitBtn      = document.getElementById('submitSaleBtn');

let cart = {}; // medicine_id => {name, price, qty, maxQty, unit}
let searchTimeout;

searchInput.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 1) {
        searchResults.classList.add('d-none');
        return;
    }
    searchTimeout = setTimeout(() => {
        fetch('api/search_medicines.php?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => renderResults(data))
            .catch(() => { searchResults.classList.add('d-none'); });
    }, 250);
});

document.addEventListener('click', function (e) {
    if (!searchResults.contains(e.target) && e.target !== searchInput) {
        searchResults.classList.add('d-none');
    }
});

function renderResults(items) {
    if (!items.length) {
        searchResults.innerHTML = '<div class="result-item text-muted">لا توجد نتائج</div>';
        searchResults.classList.remove('d-none');
        return;
    }
    searchResults.innerHTML = items.map(m => `
        <div class="result-item" data-id="${m.id}" data-name="${escapeHtml(m.name)}"
             data-price="${m.selling_price}" data-qty="${m.quantity}" data-unit="${escapeHtml(m.unit)}" data-expiry="${m.expiry_date || ''}">
            <div class="fw-semibold">${escapeHtml(m.name)}</div>
            <small class="${m.expiry_date && m.expiry_date < new Date().toISOString().slice(0,10) ? 'text-danger fw-bold' : 'text-muted'}">
                متوفر: ${m.quantity} ${escapeHtml(m.unit)} — ${parseFloat(m.selling_price).toFixed(2)} ₪
                ${m.expiry_date && m.expiry_date < new Date().toISOString().slice(0,10) ? ' — ⚠️ منتهي الصلاحية' : ''}
            </small>
        </div>
    `).join('');
    searchResults.classList.remove('d-none');

    searchResults.querySelectorAll('.result-item[data-id]').forEach(el => {
        el.addEventListener('click', function () {
            const expiryDate = this.dataset.expiry || '';
            if (expiryDate && expiryDate < new Date().toISOString().slice(0, 10)) {
                showSaleWarning('⚠️ تنبيه: الدواء «' + this.dataset.name + '» منتهي الصلاحية بتاريخ ' + expiryDate + '، ولا يمكن بيعه.');
                return;
            }
            addToCart({
                id: this.dataset.id,
                name: this.dataset.name,
                price: parseFloat(this.dataset.price),
                maxQty: parseInt(this.dataset.qty),
                unit: this.dataset.unit,
                expiryDate: expiryDate,
            });
            searchInput.value = '';
            searchResults.classList.add('d-none');
        });
    });
}

function addToCart(item) {
    const today = new Date().toISOString().slice(0, 10);
    if (item.expiryDate && item.expiryDate < today) {
        showSaleWarning('⚠️ تنبيه: الدواء «' + item.name + '» منتهي الصلاحية بتاريخ ' + item.expiryDate + '، ولا يمكن بيعه.');
        return;
    }
    if (item.maxQty <= 0) {
        showSaleWarning('⚠️ الصنف «' + item.name + '» غير متوفر في المخزون.');
        return;
    }
    if (cart[item.id]) {
        if (cart[item.id].qty < cart[item.id].maxQty) {
            cart[item.id].qty += 1;
        } else {
            showSaleWarning('⚠️ الكمية المطلوبة من «' + item.name + '» تجاوزت المتوفر. المتوفر فقط: ' + item.maxQty + ' ' + item.unit);
        }
    } else {
        cart[item.id] = { name: item.name, price: item.price, qty: 1, maxQty: item.maxQty, unit: item.unit, expiryDate: item.expiryDate || '' };
    }
    renderCart();
}

function renderCart() {
    const ids = Object.keys(cart);
    if (!ids.length) {
        cartBody.innerHTML = '';
        cartBody.appendChild(emptyCartRow);
        submitBtn.disabled = true;
        grandTotalEl.textContent = '0.00 ₪';
        hiddenInputs.innerHTML = '';
        return;
    }

    let total = 0;
    let rows = '';
    let inputs = '';

    ids.forEach(id => {
        const item = cart[id];
        const subtotal = item.price * item.qty;
        total += subtotal;
        rows += `
            <tr>
                <td class="fw-semibold">${escapeHtml(item.name)} <span class="text-muted small">(${escapeHtml(item.unit)})</span></td>
                <td>${item.price.toFixed(2)} ₪</td>
                <td>
                    <input type="number" min="1" max="${item.maxQty}" value="${item.qty}"
                           class="form-control form-control-sm qty-input" data-id="${id}" style="width:90px">
                </td>
                <td>${subtotal.toFixed(2)} ₪</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-item" data-id="${id}"><i class="bi bi-x-lg"></i></button></td>
            </tr>`;
        inputs += `
            <input type="hidden" name="items[${id}][medicine_id]" value="${id}">
            <input type="hidden" name="items[${id}][quantity]" value="${item.qty}">
        `;
    });

    cartBody.innerHTML = rows;
    hiddenInputs.innerHTML = inputs;
    grandTotalEl.textContent = total.toFixed(2) + ' ₪';
    submitBtn.disabled = false;

    cartBody.querySelectorAll('.qty-input').forEach(inp => {
        inp.addEventListener('change', function () {
            const id = this.dataset.id;
            let val = parseInt(this.value) || 1;
            if (val < 1) val = 1;
            if (val > cart[id].maxQty) {
                showSaleWarning('⚠️ الكمية المطلوبة من «' + cart[id].name + '» هي ' + val + ' بينما المتوفر فقط ' + cart[id].maxQty + '.');
                val = cart[id].maxQty;
            }
            cart[id].qty = val;
            renderCart();
        });
    });
    cartBody.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function () {
            delete cart[this.dataset.id];
            renderCart();
        });
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function showSaleWarning(message) {
    let box = document.getElementById('saleWarning');
    if (!box) {
        box = document.createElement('div');
        box.id = 'saleWarning';
        box.className = 'alert alert-warning mt-3 mb-0 fw-semibold';
        box.setAttribute('role', 'alert');
        const form = document.getElementById('saleForm');
        form.insertBefore(box, form.querySelector('.table-responsive'));
    }
    box.textContent = message;
    box.classList.remove('d-none');
    clearTimeout(window.saleWarningTimer);
    window.saleWarningTimer = setTimeout(() => box.classList.add('d-none'), 6000);
}

// ===================== البيع السريع عبر الباركود / QR (قارئ خارجي) =====================
const barcodeInput = document.getElementById('barcodeInput');
const cameraStatus = document.getElementById('cameraScanStatus');

function handleScannedCode(code, statusEl) {
    code = code.trim();
    if (!code) return;
    fetch('api/lookup_barcode.php?code=' + encodeURIComponent(code))
        .then(res => res.json())
        .then(data => {
            if (data.found) {
                addToCart({
                    id: data.medicine.id,
                    name: data.medicine.name,
                    price: parseFloat(data.medicine.selling_price),
                    maxQty: parseInt(data.medicine.quantity),
                    unit: data.medicine.unit,
                    expiryDate: data.medicine.expiry_date || '',
                });
                if (statusEl) {
                    statusEl.textContent = '✓ تمت إضافة: ' + data.medicine.name;
                    statusEl.className = 'text-success small mt-2 text-center fw-bold';
                }
            } else {
                showSaleWarning('⚠️ ' + data.message);
                if (statusEl) {
                    statusEl.textContent = '✗ ' + data.message;
                    statusEl.className = 'text-danger small mt-2 text-center fw-bold';
                }
            }
        })
        .catch(() => {
            if (statusEl) {
                statusEl.textContent = '✗ تعذر الاتصال بالنظام، حاول مجددًا';
                statusEl.className = 'text-danger small mt-2 text-center fw-bold';
            }
        });
}

// قارئ الباركود الخارجي (USB/بلوتوث) يعمل كلوحة مفاتيح: يكتب الكود ثم يرسل Enter تلقائيًا
barcodeInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleScannedCode(this.value, null);
        this.value = '';
    }
});
// إبقاء التركيز على حقل الباركود بشكل افتراضي لتسريع المسح المتكرر
barcodeInput.focus();

// ===================== المسح عبر كاميرا الجهاز (QR / باركود) =====================
let html5QrScanner = null;
const cameraScanModalEl = document.getElementById('cameraScanModal');

cameraScanModalEl.addEventListener('shown.bs.modal', function () {
    cameraStatus.textContent = 'وجّه الكاميرا نحو الباركود أو رمز QR...';
    cameraStatus.className = 'text-muted small mt-2 text-center';

    html5QrScanner = new Html5Qrcode('cameraReader');
    Html5Qrcode.getCameras().then(cameras => {
        if (!cameras || !cameras.length) {
            cameraStatus.textContent = 'لم يتم العثور على كاميرا متاحة على هذا الجهاز';
            cameraStatus.className = 'text-danger small mt-2 text-center';
            return;
        }
        const cameraId = cameras[cameras.length - 1].id; // الكاميرا الخلفية غالبًا آخر عنصر بالهواتف
        html5QrScanner.start(
            cameraId,
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (decodedText) => {
                handleScannedCode(decodedText, cameraStatus);
            },
            () => { /* يتجاهل أخطاء عدم القراءة المؤقتة أثناء البحث عن الرمز */ }
        ).catch(() => {
            cameraStatus.textContent = 'تعذر تشغيل الكاميرا. تأكد من منح إذن الوصول للكاميرا';
            cameraStatus.className = 'text-danger small mt-2 text-center';
        });
    }).catch(() => {
        cameraStatus.textContent = 'تعذر الوصول للكاميرا. تأكد من منح إذن الوصول من إعدادات المتصفح';
        cameraStatus.className = 'text-danger small mt-2 text-center';
    });
});

cameraScanModalEl.addEventListener('hidden.bs.modal', function () {
    if (html5QrScanner) {
        html5QrScanner.stop().then(() => html5QrScanner.clear()).catch(() => {});
        html5QrScanner = null;
    }
    barcodeInput.focus();
});
</script>
<script src="assets/vendor/html5-qrcode/html5-qrcode.min.js"></script>

<?php require_once 'includes/footer.php'; ?>
