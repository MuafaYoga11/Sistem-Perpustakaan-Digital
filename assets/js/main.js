// assets/js/main.js

// Auto-dismiss alert setelah 4 detik
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transition = 'opacity .5s';
    setTimeout(() => el.remove(), 500);
  }, 4000);
});

// Hamburger sidebar (mobile)
const toggleBtn = document.getElementById('sidebar-toggle');
const sidebar   = document.querySelector('.sidebar');
if (toggleBtn && sidebar) {
  toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
}

// Konfirmasi hapus
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm)) e.preventDefault();
  });
});
