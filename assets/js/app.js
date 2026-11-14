document.addEventListener('DOMContentLoaded', () => {
  const rows = document.getElementById('wasteRows');
  const total = document.getElementById('totalWeight');
  const add = document.getElementById('addWasteRow');
  const form = document.getElementById('auditForm');

  function calculateTotal() {
    if (!rows || !total) return;
    const sum = [...rows.querySelectorAll('.weight-input')].reduce((acc, input) => acc + (parseFloat(input.value) || 0), 0);
    total.textContent = sum.toLocaleString('id-ID', {minimumFractionDigits: 3, maximumFractionDigits: 3});
  }
  function bindRow(row) {
    row.querySelector('.weight-input')?.addEventListener('input', calculateTotal);
    row.querySelector('.remove-row')?.addEventListener('click', () => {
      if (rows.querySelectorAll('.waste-row').length > 1) row.remove();
      else row.querySelectorAll('input').forEach(input => input.value = '');
      calculateTotal();
    });
  }
  if (rows) {
    rows.querySelectorAll('.waste-row').forEach(bindRow);
    add?.addEventListener('click', () => {
      const row = document.createElement('tr');
      row.className = 'waste-row';
      row.innerHTML = `<td><input name="waste_type[]" required placeholder="Mis. kulit buah"></td><td><input name="weight[]" type="number" min="0.001" step="0.001" required class="weight-input" placeholder="0.000"></td><td><input name="waste_note[]" placeholder="Opsional"></td><td><button type="button" class="icon-button remove-row" aria-label="Hapus baris">×</button></td>`;
      rows.appendChild(row); bindRow(row);
    });
  }
  calculateTotal();

  const photoInput = document.getElementById('photos');
  const preview = document.getElementById('photoPreview');
  const projectInput = document.querySelector('[name="project_code"]');
  const sessionInput = document.querySelector('[name="session_code"]');
  const dateInput = document.querySelector('[name="audit_date"]');

  function expectedName(index, file) {
    const project = (projectInput?.value || 'P01').toUpperCase().replace(/[^A-Z0-9]/g, '');
    const session = (sessionInput?.value || 'S01').toUpperCase().replace(/[^A-Z0-9]/g, '');
    const date = (dateInput?.value || new Date().toISOString().slice(0,10)).replaceAll('-', '');
    const ext = (file.name.split('.').pop() || 'jpg').toLowerCase();
    return `${project}_FOTO_${session}_${date}_${String(index + 1).padStart(3, '0')}.${ext}`;
  }
  function renderPhotos() {
    if (!photoInput || !preview) return;
    preview.innerHTML = '';
    [...photoInput.files].forEach((file, index) => {
      const card = document.createElement('div'); card.className = 'photo-card';
      const image = document.createElement('img'); image.alt = 'Preview dokumentasi';
      const meta = document.createElement('div'); meta.className = 'photo-meta';
      const name = document.createElement('b'); name.textContent = expectedName(index, file);
      const original = document.createElement('small'); original.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
      const status = document.createElement('small');
      const validType = ['image/jpeg','image/png','image/webp'].includes(file.type);
      const validSize = file.size <= 5 * 1024 * 1024;
      status.className = validType && validSize ? 'valid-text' : 'invalid-text';
      status.textContent = !validType ? 'Format tidak didukung' : (!validSize ? 'Melebihi batas 5 MB' : 'Format valid · nama akan dibuat otomatis');
      meta.append(name, original, status); card.append(image, meta); preview.append(card);
      if (validType) image.src = URL.createObjectURL(file);
    });
  }
  photoInput?.addEventListener('change', renderPhotos);
  [projectInput, sessionInput, dateInput].forEach(el => el?.addEventListener('change', renderPhotos));
  form?.addEventListener('submit', (event) => {
    if (photoInput && [...photoInput.files].some(file => !['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
      event.preventDefault(); alert('Periksa foto: hanya JPG, PNG, WEBP dan maksimal 5 MB per foto.');
    }
  });
});
