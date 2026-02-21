/**
 * Webspires — Google Ads Command Center
 * Global JS: Sidebar toggle, mobile menu
 */

document.addEventListener('DOMContentLoaded', function () {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (
                window.innerWidth <= 768 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !mobileMenuBtn.contains(e.target)
            ) {
                sidebar.classList.remove('open');
            }
        });
    }

    // Animate KPI values on page load
    document.querySelectorAll('.kpi-value').forEach(function (el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 100 + Math.random() * 300);
    });

    // Animate table rows on page load
    document.querySelectorAll('.data-table tbody tr').forEach(function (row, i) {
        row.style.opacity = '0';
        row.style.transform = 'translateY(8px)';
        setTimeout(function () {
            row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 150 + i * 50);
    });

    // Animate budget cards
    document.querySelectorAll('.budget-card').forEach(function (card, i) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(12px)';
        setTimeout(function () {
            card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 + i * 100);
    });

    // Animate alert cards
    document.querySelectorAll('.alert-card').forEach(function (card, i) {
        card.style.opacity = '0';
        card.style.transform = 'translateX(-10px)';
        setTimeout(function () {
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateX(0)';
        }, 100 + i * 80);
    });
});
