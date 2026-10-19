(() => {
  'use strict';
  const cfg = window.PageNestPresentation;
  if (!cfg) return;
  const key = 'pagenest-site-appearance-v1',
    root = document.documentElement,
    controls = [...document.querySelectorAll('[data-pagenest-site-preferences]')],
    media = matchMedia('(prefers-color-scheme: dark)');
  let choice = 'default';
  const read = () => {
    choice = 'default';
    try {
      const value = JSON.parse(localStorage.getItem(key));
      if (['light', 'dark', 'system'].includes(value?.scheme)) choice = value.scheme;
    } catch {}
  };
  const apply = () => {
    const mode = choice === 'default' ? cfg.defaultScheme : choice;
    root.dataset.pagenestColorScheme = mode;
    controls.forEach((control) => {
      control.querySelector('select').value = choice;
    });
    document.dispatchEvent(
      new CustomEvent('pagenest-color-scheme-change', {
        detail: { mode, effective: mode === 'system' ? (media.matches ? 'dark' : 'light') : mode },
      }),
    );
  };
  read();
  apply();
  controls.forEach((control) => {
    control.hidden = false;
    control.querySelector('select').addEventListener('change', (event) => {
      choice = ['light', 'dark', 'system'].includes(event.target.value)
        ? event.target.value
        : 'default';
      apply();
      const status = control.querySelector('[role="status"]');
      try {
        if (choice === 'default') localStorage.removeItem(key);
        else localStorage.setItem(key, JSON.stringify({ scheme: choice }));
        status.textContent = cfg.saved;
      } catch {
        status.textContent = cfg.unsaved;
      }
    });
    control.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !event.isComposing && control.open) {
        event.preventDefault();
        event.stopPropagation();
        control.open = false;
        control.querySelector('summary').focus();
      }
    });
  });
  media.addEventListener('change', apply);
  addEventListener('storage', (event) => {
    if (event.key === key) {
      read();
      apply();
    }
  });
  document.addEventListener('click', (event) =>
    controls.forEach((control) => {
      if (control.open && !control.contains(event.target)) control.open = false;
    }),
  );
})();
