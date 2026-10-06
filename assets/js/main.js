document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) {
        e.preventDefault();
      }
    });
  });

  const toastHost = document.getElementById('nbToastHost');
  window.nbToast = function (message, type = 'success') {
    if (!toastHost) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0 show`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${message}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    toastHost.appendChild(el);
    setTimeout(() => el.remove(), 3500);
  };

  document.querySelectorAll('.nb-gallery-thumbs .nb-thumb').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const main = document.querySelector('.nb-gallery-main img');
      if (!main) return;
      main.src = thumb.dataset.full || thumb.src;
      document.querySelectorAll('.nb-gallery-thumbs .nb-thumb').forEach((t) => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });
});
