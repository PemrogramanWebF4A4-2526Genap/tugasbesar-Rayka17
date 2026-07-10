function toggleSidebar() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (sidebar) {
        sidebar.classList.toggle('show');
    }

    if (overlay) {
        overlay.classList.toggle('show');
    }
}

function closeSidebar() {
    const sidebar = document.querySelector('.dashboard-sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (sidebar) {
        sidebar.classList.remove('show');
    }

    if (overlay) {
        overlay.classList.remove('show');
    }
}

function updateRealtimeClock() {
    const clocks = document.querySelectorAll('.realtime-clock');

    if (!clocks.length) return;

    const now = new Date();

    const formatted = now.toLocaleString('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    clocks.forEach(clock => {
        clock.textContent = formatted;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    updateRealtimeClock();
    setInterval(updateRealtimeClock, 1000 * 30);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
});