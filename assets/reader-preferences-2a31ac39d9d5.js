(() => {
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
  const status = controls.querySelector('[role="status"]');
  const apply = () => {
    for (const name of Object.keys(choices)) {
      if (preferences[name] === 'default')
        delete article.dataset[`reader${name[0].toUpperCase()}${name.slice(1)}`];
      else article.dataset[`reader${name[0].toUpperCase()}${name.slice(1)}`] = preferences[name];
      controls.querySelector(`[data-reader-preference="${name}"]`).value = preferences[name];
    }
  };
  const save = (reset = false) => {
    try {
      if (reset) localStorage.removeItem(key);
      else localStorage.setItem(key, JSON.stringify({ version: 1, preferences }));
      status.textContent = reset ? '已恢复站点默认。' : '阅读偏好已保存。';
    } catch {
      status.textContent = reset
        ? '已恢复站点默认；浏览器不允许保存。'
        : '已应用；浏览器不允许保存，仅本页生效。';
    }
  };
  controls.addEventListener('change', (event) => {
    const name = event.target.dataset.readerPreference;
    if (!Object.hasOwn(choices, name) || !choices[name].includes(event.target.value)) return;
    preferences[name] = event.target.value;
    apply();
    save();
  });
  controls.querySelector('[data-reader-reset]').addEventListener('click', () => {
    preferences = defaults();
    apply();
    save(true);
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
})();
