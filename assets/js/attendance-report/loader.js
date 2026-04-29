export async function fetchMonth(endpoint, month, userId = 0) {
  const url = new URL(endpoint, window.location.origin);
  url.searchParams.set('month', month);
  if (userId) url.searchParams.set('user_id', String(userId));

  const res = await fetch(url.toString(), {
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin',
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  const data = await res.json();
  if (data.status && data.status !== 'ok') throw new Error(data.message || 'Failed');
  return data;
}
