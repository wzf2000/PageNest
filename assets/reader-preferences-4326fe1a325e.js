(() => {
  const l10n = {
    reset: 'Site defaults restored.',
    saved: 'Reading preferences saved.',
    resetUnsaved: 'Site defaults restored; browser storage is unavailable.',
    unsaved: 'Applied to this page; browser storage is unavailable.',
    ...window.pagenestReaderL10n,
  };
  const controls = document.querySelector('[data-pagenest-reader-preferences]');
  const article = document.querySelector('.pagenest-reading-grid .pagenest-article');
  if (!controls || !article) return;
  const key = 'pagenest-reader-preferences-v1';
  const choices = {
    size: ['default', '18', '20', '22'],
    width: ['default', 'compact', 'wide'],
    palette: ['default', 'paper', 'dark'],
  };
  const defaults = () => ({ size: 'default', width: 'default', palette: 'default' });
  const validate = (value) => {
    const result = defaults();
    if (!value || value.version !== 1 || typeof value.preferences !== 'object') return result;
    for (const name of Object.keys(choices)) {
      const selected = value.preferences?.[name];
      if (choices[name].includes(selected)) result[name] = selected;
    }
    return result;
  };
  let preferences = defaults();
  try {
    preferences = validate(JSON.parse(localStorage.getItem(key)));
  } catch {
    // Storage can be denied; the controls still work for the current page.
  }
  let remote = null;
  const displayed = () => ({ ...preferences, ...(remote || {}) });
  const emit = (kind, source, changed = [], requestId = '') => {
    document.dispatchEvent(
      new CustomEvent('pagenest-reader-preferences-' + kind, {
        detail: { version: 1, source, changed: [...changed], preferences: displayed(), requestId },
      }),
    );
  };
  const status = controls.querySelector('[role="status"]');
  const apply = () => {
    const shown = displayed();
    for (const name of Object.keys(choices)) {
      if (shown[name] === 'default')
        delete article.dataset[`reader${name[0].toUpperCase()}${name.slice(1)}`];
      else article.dataset[`reader${name[0].toUpperCase()}${name.slice(1)}`] = shown[name];
      controls.querySelectorAll(`[data-reader-preference="${name}"]`).forEach((input) => {
        input.checked = input.value === shown[name];
      });
    }
  };
  const save = (reset = false) => {
    try {
      if (reset) localStorage.removeItem(key);
      else localStorage.setItem(key, JSON.stringify({ version: 1, preferences }));
      status.textContent = reset ? l10n.reset : l10n.saved;
    } catch {
      status.textContent = reset ? l10n.resetUnsaved : l10n.unsaved;
    }
  };
  controls.addEventListener('change', (event) => {
    const name = event.target.dataset.readerPreference;
    if (!Object.hasOwn(choices, name) || !choices[name].includes(event.target.value)) return;
    preferences[name] = event.target.value;
    if (remote) remote[name] = event.target.value;
    apply();
    save();
    emit('change', 'user', [name]);
  });
  controls.querySelector('[data-reader-reset]').addEventListener('click', () => {
    preferences = defaults();
    remote = null;
    apply();
    save(true);
    emit('change', 'user', Object.keys(choices));
  });
  document.addEventListener('pagenest-reader-preferences-request', (event) => {
    if (
      event.detail?.version === 1 &&
      (event.detail.requestId === undefined ||
        (typeof event.detail.requestId === 'string' && event.detail.requestId.length <= 100))
    )
      emit('ready', remote ? 'remote' : 'local', [], event.detail.requestId || '');
  });
  document.addEventListener('pagenest-reader-preferences-apply', (event) => {
    const value = event.detail;
    if (
      !value ||
      value.version !== 1 ||
      !['remote', 'local'].includes(value.source) ||
      !Array.isArray(value.changed) ||
      value.changed.length > 3 ||
      new Set(value.changed).size !== value.changed.length ||
      value.changed.some((name) => !Object.hasOwn(choices, name)) ||
      typeof value.requestId !== 'string' ||
      value.requestId.length > 100
    )
      return;
    if (value.source === 'local') remote = null;
    else {
      if (
        !value.preferences ||
        typeof value.preferences !== 'object' ||
        value.changed.some(
          (name) =>
            !Object.hasOwn(choices, name) || !choices[name].includes(value.preferences[name]),
        )
      )
        return;
      remote = Object.fromEntries(value.changed.map((name) => [name, value.preferences[name]]));
    }
    apply();
    emit('applied', value.source, value.changed, value.requestId);
  });
  // Move only our controls; extension panels and article anchors retain their nodes.
  const home = controls.parentElement;
  const marker = document.createComment('reader-preferences');
  home.insertBefore(marker, controls);
  const mobile = matchMedia('(max-width: 1023px)');
  const place = () => {
    if (mobile.matches) article.before(controls);
    else marker.after(controls);
  };
  mobile.addEventListener('change', place);
  apply();
  place();
  controls.hidden = false;
  emit('ready', 'local');
})();
