<script>
(function () {
    var el = document.querySelector('.hub-app .sidebar-scroll');
    if (!el) return;
    var key = 'solqam.sidebarScroll.' + (document.body.classList.contains('admin-hub') ? 'admin' : 'seller');
    try {
        var saved = sessionStorage.getItem(key);
        if (saved !== null) el.scrollTop = parseInt(saved, 10) || 0;
    } catch (e) {}
})();
</script>
