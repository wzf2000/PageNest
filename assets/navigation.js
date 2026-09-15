(() => {
  'use strict';
  const nav = document.querySelector('#pagenest-nav'),
    toggle = document.querySelector('.pagenest-menu-toggle');
  if (!nav || !toggle) return;
  function close() {
    nav.querySelectorAll('details[open]').forEach((d) => (d.open = false));
    nav.classList.remove('pagenest-nav-open');
    toggle.setAttribute('aria-expanded', 'false');
  }
  toggle.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('pagenest-nav-open', open);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      close();
      toggle.focus();
    }
  });
  document.addEventListener('click', (e) => {
    if (!nav.contains(e.target) && !toggle.contains(e.target)) close();
  });
  nav.querySelectorAll('details').forEach((d) =>
    d.addEventListener('toggle', () => {
      if (d.open)
        nav.querySelectorAll('details').forEach((other) => {
          if (other !== d && !other.contains(d) && !d.contains(other)) other.open = false;
        });
    }),
  );
  document.querySelectorAll('.pagenest-author-card img,.pagenest-logo').forEach((img) => {
    const fail = () => (img.hidden = true);
    img.addEventListener('error', fail);
    if (img.complete && !img.naturalWidth) fail();
  });
})();
