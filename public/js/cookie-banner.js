document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('eduBanner');
  const btn = document.getElementById('eduBannerBtn');
  if (!modal || !btn) return;

  const STORAGE_KEY = 'edu_notice_accepted_v1';

  const hide = () => {
    modal.hidden = true;
    document.removeEventListener('keydown', onKeyDown);
  };

  const show = () => {
    modal.hidden = false;
    document.addEventListener('keydown', onKeyDown);
    btn.focus({ preventScroll: true });
  };

  const onKeyDown = (e) => {
    if (e.key === 'Escape') hide();
  };

  // close on backdrop or X
  modal.addEventListener('click', (e) => {
    if (e.target && e.target.matches('[data-edu-close]')) hide();
  });

  // If already accepted, don’t show
  try {
    if (localStorage.getItem(STORAGE_KEY) === '1') {
      hide();
      return;
    }
  } catch {
    // If localStorage is blocked, still allow closing for this visit.
  }

  show();

  btn.addEventListener('click', async () => {
    // Always close immediately
    hide();

    // Persist locally (best effort)
    try { localStorage.setItem(STORAGE_KEY, '1'); } catch {}

    // Optional: tell server (best effort)
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      await fetch('/accept-edu-notice', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': token,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ accepted: true }),
        keepalive: true,
      });
    } catch {}
  });
});