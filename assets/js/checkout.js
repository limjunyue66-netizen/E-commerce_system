document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('checkoutForm');
  if (!form) return;

  const bankBox = document.getElementById('bankDetails');
  const accountName = document.getElementById('bank_account_name');
  const accountNumber = document.getElementById('bank_account_number');
  const radios = form.querySelectorAll('input[name="payment_method"]');

  const syncBankBox = () => {
    if (!bankBox) return;
    const selected = form.querySelector('input[name="payment_method"]:checked');
    const isBank = !!(selected && selected.value === 'bank');
    bankBox.classList.toggle('d-none', !isBank);
    if (accountName) accountName.required = isBank;
    if (accountNumber) accountNumber.required = isBank;
  };

  radios.forEach((radio) => radio.addEventListener('change', syncBankBox));
  syncBankBox();

  if (accountNumber) {
    accountNumber.addEventListener('input', () => {
      accountNumber.value = accountNumber.value.replace(/[^\d\s]/g, '');
    });
  }

  form.addEventListener('submit', () => {
    const btn = form.querySelector('[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }
  });
});
