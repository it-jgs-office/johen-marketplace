(() => {
  const track = document.getElementById('paymentTrack');
  if (!track) return;

  const section = track.closest('.payment-marquee-section');
  const logos = window.STATIC_PAYMENT_LOGOS || {};
  const fallback = ['QRIS', 'DANA', 'OVO', 'LinkAja', 'BCA', 'BNI', 'Mandiri', 'Permata', 'Alfamart', 'Indomaret'];

  function render(methods) {
    const items = methods
      .filter(Boolean)
      .map(method => typeof method === 'string' ? { code: method, name: method } : method)
      .map(method => ({ ...method, logo: logos[String(method.code || method.name || '').trim().toLowerCase().replace(/[\s-]+/g, '_')] || method.photo_url || '' }))
      .filter(method => method.logo);

    track.replaceChildren();
    section.hidden = items.length === 0;
    if (!items.length) return;

    [...items, ...items].forEach((method, index) => {
      const badge = document.createElement('div');
      badge.className = 'pay-badge';
      if (index >= items.length) badge.setAttribute('aria-hidden', 'true');

      const image = document.createElement('img');
      image.className = 'pay-badge-img';
      image.src = method.logo;
      image.alt = index < items.length ? String(method.name || method.code) : '';
      image.addEventListener('error', () => {
        track.querySelectorAll('.pay-badge-img').forEach(img => {
          if (img.src === image.src) img.closest('.pay-badge')?.remove();
        });
        if (!track.querySelector('.pay-badge')) section.hidden = true;
      });
      badge.appendChild(image);
      track.appendChild(badge);
    });
  }

  fetch('/api/payment-methods', { headers: { Accept: 'application/json' } })
    .then(response => response.ok ? response.json() : [])
    .catch(() => [])
    .then(methods => {
      const available = Array.isArray(methods) && methods.length ? methods : fallback;
      render(available);
      document.addEventListener('themeChanged', () => render(available));
    });
})();
