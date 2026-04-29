import { h, icon } from './dom.js';

function addMinutes(hhmm, mins) {
  const [h0, m0] = hhmm.split(':').map(Number);
  let total = h0 * 60 + m0 + mins;
  total = ((total % 1440) + 1440) % 1440;
  return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

export function renderScheduleEditor({ userId, summary, endpoint }) {
  const isSpecial = !!(summary && summary.special_schedule);
  const dispStart = (summary && summary.start_time) || '08:00';
  const dispEnd = (summary && summary.end_time) || '17:00';
  const inputStart = isSpecial ? dispStart : '08:00';
  const inputEnd = isSpecial ? dispEnd : '17:00';

  const badge = h('span', {
    class: 'ar-badge ar-badge--special',
    style: { display: isSpecial ? 'inline-flex' : 'none' }
  }, 'Khusus');
  const timesEl = h('span', { class: 'ar-schedule__times ar-mono' }, `${dispStart} – ${dispEnd}`);
  const editBtn = h('button', { class: 'ar-schedule__edit', title: 'Ubah Jadwal' }, icon('pencil', 12));

  const startInput = h('input', { type: 'time', value: inputStart });
  const endInput = h('input', { type: 'time', value: inputEnd });
  const thresh = h('div', { class: 'ar-schedule__thresh' });
  const errorEl = h('div', { class: 'ar-schedule__error' });
  const saveBtn = h('button', { class: 'ar-btn-sm ar-btn-sm--primary' }, 'Simpan');
  const resetBtn = h('button', { class: 'ar-btn-sm' }, 'Atur Ulang');
  const cancelBtn = h('button', { class: 'ar-btn-sm' }, 'Batal');
  const form = h('div', { class: 'ar-schedule__form' },
    h('div', { class: 'ar-schedule__row' },
      h('span', { class: 'ar-control__label' }, 'Masuk'), startInput,
      h('span', { class: 'ar-control__label' }, 'Keluar'), endInput,
    ),
    thresh,
    h('div', { class: 'ar-schedule__row' }, saveBtn, resetBtn, cancelBtn),
    errorEl,
  );

  const display = h('div', { class: 'ar-schedule__display ar-schedule__row' }, badge, timesEl, editBtn);
  const root = h('div', { class: 'ar-schedule', dataset: { userId: String(userId) } }, display, form);

  function updateThresh() {
    const parts = [];
    if (startInput.value) parts.push('Terlambat setelah ' + addMinutes(startInput.value, 15));
    if (endInput.value)   parts.push('Pulang cepat sebelum ' + addMinutes(endInput.value, -15));
    thresh.textContent = parts.join(' · ');
  }
  updateThresh();
  startInput.addEventListener('input', updateThresh);
  endInput.addEventListener('input', updateThresh);

  function openForm(e) {
    e && e.stopPropagation();
    display.style.display = 'none';
    form.classList.add('is-open');
    errorEl.textContent = '';
  }
  function closeForm() {
    display.style.display = '';
    form.classList.remove('is-open');
    errorEl.textContent = '';
  }
  editBtn.addEventListener('click', openForm);
  cancelBtn.addEventListener('click', closeForm);

  function applyResult(data) {
    const special = !!data.is_special;
    badge.style.display = special ? 'inline-flex' : 'none';
    timesEl.textContent = `${data.start_time} – ${data.end_time}`;
    startInput.value = data.start_time;
    endInput.value = data.end_time;
    updateThresh();
    closeForm();
  }

  async function postSchedule(startTime, endTime) {
    saveBtn.disabled = true;
    resetBtn.disabled = true;
    errorEl.textContent = '';
    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ user_id: userId, start_time: startTime, end_time: endTime }),
      });
      const data = await res.json();
      if (data.status === 'ok') applyResult(data);
      else errorEl.textContent = data.message || 'Gagal menyimpan.';
    } catch (e) {
      errorEl.textContent = 'Kesalahan jaringan. Silakan coba lagi.';
    } finally {
      saveBtn.disabled = false;
      resetBtn.disabled = false;
    }
  }

  saveBtn.addEventListener('click', () => postSchedule(startInput.value, endInput.value));
  resetBtn.addEventListener('click', () => postSchedule('', ''));

  return root;
}
