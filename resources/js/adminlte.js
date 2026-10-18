import 'bootstrap';
import 'admin-lte';

import { OverlayScrollbars } from 'overlayscrollbars';

// Scrollbar styling for the AdminLTE sidebar (see the official theme).
document.addEventListener('DOMContentLoaded', () => {
    const sidebarWrapper = document.querySelector('.sidebar-wrapper');

    if (sidebarWrapper && window.innerWidth > 992) {
        OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
                theme: 'os-theme-light',
                autoHide: 'leave',
                clickScroll: true,
            },
        });
    }
});
