    </main>
</div><!-- /.main-wrap -->

<script>
    // Responsive sidebar toggle visibility
    function checkViewport() {
        const btn = document.getElementById('sidebarToggle');
        if (btn) btn.style.display = window.innerWidth <= 1024 ? 'flex' : 'none';
    }
    window.addEventListener('resize', checkViewport);
    checkViewport();

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('sidebar');
        const toggle  = document.getElementById('sidebarToggle');
        if (sidebar && toggle && window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
</script>
</body>
</html>
