document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.nb-qty-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.querySelector(btn.dataset.target);
      if (!input) return;
      const min = parseInt(input.min || '1', 10);
      const max = parseInt(input.max || '999', 10);
      let val = parseInt(input.value || '1', 10);
      val += btn.dataset.dir === 'up' ? 1 : -1;
      input.value = Math.min(max, Math.max(min, val));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  // Auto-save quantity when changed (no Update cart button)
  document.querySelectorAll('.nb-cart-qty').forEach((input) => {
    input.addEventListener('change', () => {
      const form = input.closest('form');
      if (form) form.submit();
    });
  });
});
