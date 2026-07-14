(function () {
    function getLegacySidebar() {
        return document.querySelector('.dashboard-sidebar');
    }

    function getLegacyOverlay() {
        return document.querySelector('.mobile-overlay');
    }

    function legacyOpenSidebar() {
        const sidebar = getLegacySidebar();
        const overlay = getLegacyOverlay();

        if (sidebar) {
            sidebar.classList.add('show');
        }

        if (overlay) {
            overlay.classList.add('show');
        }

        document.body.classList.add('sidebar-open');
    }

    function legacyCloseSidebar() {
        const sidebar = getLegacySidebar();
        const overlay = getLegacyOverlay();

        if (sidebar) {
            sidebar.classList.remove('show');
        }

        if (overlay) {
            overlay.classList.remove('show');
        }

        document.body.classList.remove('sidebar-open');
    }

    function legacyToggleSidebar() {
        const sidebar = getLegacySidebar();

        if (!sidebar) {
            return;
        }

        if (sidebar.classList.contains('show')) {
            legacyCloseSidebar();
        } else {
            legacyOpenSidebar();
        }
    }

    /*
     * Do not replace drawer handlers created by admin-sidebar.php or
     * seller-sidebar.php. The old implementation replaced them after the
     * layout loaded, so the hamburger only dimmed the page and never opened
     * the actual drawer.
     */
    if (typeof window.openSidebar !== 'function') {
        window.openSidebar = legacyOpenSidebar;
    }

    if (typeof window.closeSidebar !== 'function') {
        window.closeSidebar = legacyCloseSidebar;
    }

    if (typeof window.toggleSidebar !== 'function') {
        window.toggleSidebar = legacyToggleSidebar;
    }

    function updateRealtimeClock() {
        const clocks = document.querySelectorAll('.realtime-clock');

        if (!clocks.length) {
            return;
        }

        const formatted = new Date().toLocaleString('id-ID', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        clocks.forEach(function (clock) {
            clock.textContent = formatted;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateRealtimeClock();
        window.setInterval(updateRealtimeClock, 30000);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && typeof window.closeSidebar === 'function') {
                window.closeSidebar();
            }
        });
    });
})();
