document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // تأكيد الحذف
    document.querySelectorAll('.confirm-delete').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm('هل أنت متأكد من الحذف؟ لا يمكن التراجع عن هذا الإجراء.')) {
                e.preventDefault();
            }
        });
    });
});
