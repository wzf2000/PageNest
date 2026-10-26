(() => {
  'use strict';
  const cfg = window.PageNestPresentation;
  if (!cfg) return;
  const key = 'pagenest-site-appearance-v1',
    root = document.documentElement,
    controls = [...document.querySelectorAll('.pagenest-appearance-controls')],
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
      const cycle = control.querySelector('[data-pagenest-cycle-mode]');
      if (cycle) {
        const sequence = ['light', 'dark', 'system'];
        const next = sequence[(sequence.indexOf(mode) + 1) % sequence.length];
        cycle.dataset.mode = mode;
        cycle.setAttribute(
          'aria-label',
          `${cfg.appearance || 'Appearance'}: ${cfg.modes?.[mode] || mode}. ${(cfg.switchTo || 'Switch to %s').replace('%s', cfg.modes?.[next] || next)}`,
        );
        cycle.title = cycle.getAttribute('aria-label');
      }
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
    const saveChoice = (value) => {
      choice = value;
      apply();
      const status = control.querySelector('[role="status"]');
      try {
        localStorage.setItem(key, JSON.stringify({ scheme: choice }));
        status.textContent = cfg.saved;
      } catch {
        status.textContent = cfg.unsaved;
      }
    };
    const cycle = control.querySelector('[data-pagenest-cycle-mode]');
    if (cycle) {
      cycle.hidden = false;
      cycle.addEventListener('click', () => {
        const modes = ['light', 'dark', 'system'];
        const current = choice === 'default' ? cfg.defaultScheme : choice;
        saveChoice(modes[(modes.indexOf(current) + 1) % modes.length]);
      });
    }
  });
  media.addEventListener('change', apply);
  addEventListener('storage', (event) => {
    if (event.key === key) {
      read();
      apply();
    }
  });
})();
