// Alora admin: small conveniences only; every page works without this script.
(() => {
  // Whole table rows open their record.
  document.querySelectorAll('tr[data-href]').forEach((tr) => tr.addEventListener('click', (e) => {
    if (!e.target.closest('a, button, input, select')) location.href = tr.dataset.href;
  }));

  // Order status form: only show the fields that matter for the chosen status.
  const status = document.querySelector('[data-status]');
  const sync = () => document.querySelectorAll('[data-only]').forEach((el) => { el.hidden = el.dataset.only !== status.value; });
  if (status) { status.addEventListener('change', sync); sync(); }

  // Bundle editor: add another product row.
  document.querySelector('[data-add-row]')?.addEventListener('click', () => {
    const box = document.querySelector('[data-bundle]');
    const row = box.lastElementChild.cloneNode(true);
    row.querySelector('select').value = '0';
    row.querySelector('input').value = '1';
    box.append(row);
  });
})();
