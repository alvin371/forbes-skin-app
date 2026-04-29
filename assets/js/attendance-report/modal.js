import { h, icon } from './dom.js';
import { DAY_NAMES_FULL, MONTH_NAMES, dow } from './calendar.js';
import { deriveCode, STATUS, statusBg, statusColor } from './status.js';
import { initials, avatarColor } from './avatar.js';

let activeModal = null;

function close() {
  if (activeModal) {
    activeModal.remove();
    activeModal = null;
  }
}

function timeOnly(ts) {
  if (!ts) return '—';
  // ts shape: 'YYYY-MM-DD HH:MM:SS'
  return ts.length >= 16 ? ts.slice(11, 16) : ts;
}

function infoBox(label, value, color) {
  return h('div', { class: 'ar-info-box' },
    h('div', { class: 'ar-info-box__label' }, label),
    h('div', { class: 'ar-info-box__value', style: color ? { color } : null }, value || '—'),
  );
}

export function openCellDetail({ user, day, year, monthIdx, row, schedule }) {
  close();
  const code = deriveCode(row);
  const dowIdx = dow(year, monthIdx, day);
  const status = STATUS[code] || STATUS.A;
  const dateLine = `${DAY_NAMES_FULL[dowIdx]}, ${MONTH_NAMES[monthIdx]} ${day}, ${year}`;

  const closeBtn = h('button', { class: 'ar-modal__close', title: 'Tutup', onClick: close }, icon('x', 14));

  const dialog = h('div', { class: 'ar-modal__dialog', onClick: e => e.stopPropagation() },
    h('div', { class: 'ar-modal__header' },
      h('div', { class: 'ar-avatar', style: { width: '36px', height: '36px', borderRadius: '9px', fontSize: '12px', background: avatarColor(user.id) } }, initials(user.full_name)),
      h('div', null,
        h('div', { class: 'ar-modal__title-name' }, user.full_name),
        h('div', { class: 'ar-modal__title-sub' }, dateLine),
      ),
      closeBtn,
    ),
    h('div', { class: 'ar-modal__body' },
      h('div', { class: 'ar-modal__status-row' },
        h('span', { class: 'ar-info-box__label' }, 'Status'),
        h('span', {
          class: 'ar-status-badge',
          style: { background: statusBg(code), color: statusColor(code) }
        }, status.label),
        row && row.holiday_name ? h('span', { class: 'ar-status-badge', style: { background: 'var(--ar-blue-bg)', color: 'var(--ar-blue)' } }, row.holiday_name) : null,
        row && row.out_of_town ? h('span', { class: 'ar-status-badge', style: { background: 'var(--ar-orange-bg)', color: 'var(--ar-orange)' } }, 'Dinas Luar Kota') : null,
      ),
      h('div', { class: 'ar-modal__grid' },
        infoBox('Masuk', timeOnly(row && row.first_in)),
        infoBox('Keluar', timeOnly(row && row.last_out)),
        infoBox('Jadwal', schedule || '—'),
        infoBox('Terlambat', row && row.late ? 'Ya' : 'Tidak', row && row.late ? 'var(--ar-amber)' : null),
      ),
      row && row.late_reason ? h('div', { class: 'ar-modal__notes' }, '⏰ ', row.late_reason) : null,
      row && row.early_checkout_reason ? h('div', { class: 'ar-modal__notes' }, '⏱ ', row.early_checkout_reason) : null,
      row && Array.isArray(row.notes) && row.notes.length
        ? h('div', { class: 'ar-modal__notes' }, '📝 ', row.notes.join(' '))
        : null,
      row && (row.late_attachment_path || row.early_checkout_attachment_path || row.first_in_proof_path || row.last_out_proof_path)
        ? h('div', { class: 'ar-modal__notes' },
            row.late_attachment_path ? h('a', { href: row.late_attachment_path, target: '_blank', rel: 'noopener', style: { marginRight: '12px' } }, 'Lampiran Terlambat') : null,
            row.early_checkout_attachment_path ? h('a', { href: row.early_checkout_attachment_path, target: '_blank', rel: 'noopener', style: { marginRight: '12px' } }, 'Lampiran Pulang Cepat') : null,
            row.first_in_proof_path ? h('a', { href: row.first_in_proof_path, target: '_blank', rel: 'noopener', style: { marginRight: '12px' } }, 'Bukti Masuk') : null,
            row.last_out_proof_path ? h('a', { href: row.last_out_proof_path, target: '_blank', rel: 'noopener' }, 'Bukti Keluar') : null,
          )
        : null,
    ),
  );

  activeModal = h('div', { class: 'ar-modal', onClick: close }, dialog);
  document.body.appendChild(activeModal);
  closeBtn.focus();
}

export function closeCellDetail() { close(); }
