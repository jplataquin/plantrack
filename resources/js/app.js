import './bootstrap';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Ensure mobile-friendly tab switching and interaction
document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('#componentTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSelector = button.getAttribute('data-bs-target');
            if (!targetSelector) return;

            // Remove active from all tabs in the list
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            button.classList.add('active');
            button.setAttribute('aria-selected', 'true');

            // Find parent container and tab content
            const tabContent = document.getElementById('componentTabsContent');
            if (tabContent) {
                const panes = tabContent.querySelectorAll('.tab-pane');
                panes.forEach(pane => {
                    pane.classList.remove('show', 'active');
                });

                const targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            }
        });
    });
});
