export function initials(name) {
  if (!name) return '?';
  return name.trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
}

export function avatarColor(id) {
  const hue = ((id | 0) * 47) % 360;
  return `oklch(0.68 0.14 ${hue})`;
}
