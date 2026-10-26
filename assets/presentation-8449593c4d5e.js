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
      const cycle = control.parentElement.querySelector('[data-pagenest-cycle-mode]');
      if (cycle) {
        cycle.dataset.mode = mode;
        cycle.setAttribute(
          'aria-label',
          `${cfg.appearance || 'Appearance'}: ${cfg.modes?.[mode] || mode}`,
        );
        cycle.title = cycle.getAttribute('aria-label');
      }
      control.querySelectorAll('[data-pagenest-color-mode]').forEach((input) => {
        input.checked = input.value === choice;
      });
    });
    document.dispatchEvent(
      new CustomEvent('pagenest-color-scheme-change', {
        detail: { mode, effective: mode === 'system' ? (media.matches ? 'dark' : 'light') : mode },
      }),
    );
  };
  read();
  apply();
  const fitPopover = (control) => {
    if (!control.open) return;
    const panel = control.querySelector(':scope > div');
    const top = panel.getBoundingClientRect().top;
    panel.style.maxHeight = `${Math.max(44, innerHeight - Math.max(0, top) - 16)}px`;
  };
  controls.forEach((control) => {
    control.hidden = false;
    const saveChoice = (value) => {
      choice = ['light', 'dark', 'system'].includes(value) ? value : 'default';
      apply();
      const status = control.querySelector('[role="status"]');
      try {
        if (choice === 'default') localStorage.removeItem(key);
        else localStorage.setItem(key, JSON.stringify({ scheme: choice }));
        status.textContent = cfg.saved;
      } catch {
        status.textContent = cfg.unsaved;
      }
    };
    const cycle = control.parentElement.querySelector('[data-pagenest-cycle-mode]');
    if (cycle) {
      cycle.hidden = false;
      cycle.addEventListener('click', () => {
        const modes = ['light', 'dark', 'system'];
        const current = choice === 'default' ? cfg.defaultScheme : choice;
        saveChoice(modes[(modes.indexOf(current) + 1) % modes.length]);
      });
    }
    control.addEventListener('toggle', () => fitPopover(control));
    control.addEventListener('change', (event) => {
      if (!event.target.matches('[data-pagenest-color-mode]')) return;
      saveChoice(event.target.value);
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
  addEventListener('resize', () => controls.forEach(fitPopover));
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
