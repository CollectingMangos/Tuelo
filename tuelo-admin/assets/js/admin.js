'use strict';

/* Mobile sidebar drawer */
const sidebarToggle  = document.querySelector('[data-sidebar-toggle]');
const adminSidebar   = document.querySelector('[data-admin-nav]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');

function openSidebar() {
  adminSidebar.classList.add('open');
  sidebarOverlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  adminSidebar.classList.remove('open');
  sidebarOverlay.classList.remove('active');
  document.body.style.overflow = '';
}

if (sidebarToggle)  sidebarToggle.addEventListener('click', openSidebar);
if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

/* Confirm before any dangerous action */
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});