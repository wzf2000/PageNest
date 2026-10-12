(() => {
  'use strict';
  const l10n = {
    loading: 'Searching…',
    results: 'Suggestions: %s. Submit your search for all results.',
    empty: 'No suggestions found. Try another keyword or submit your search.',
    unavailable: 'Suggestions are unavailable. Press Enter to continue searching.',
    ...window.pagenestDiscoveryL10n,
  };
  const panel = document.querySelector('.pagenest-header-search');
  panel?.addEventListener('toggle', () => {
    if (panel.open) panel.querySelector('input')?.focus();
  });
  document.addEventListener(
    'keydown',
    (event) => {
      if (event.key === 'Escape' && panel?.open) {
        event.stopImmediatePropagation();
        panel.open = false;
        panel.querySelector('summary').focus();
      }
    },
    true,
  );
  document.addEventListener('click', (event) => {
    if (panel?.open && !panel.contains(event.target)) panel.open = false;
  });
  document.querySelectorAll('form[data-pagenest-search-endpoint]').forEach((form) => {
    const input = form.querySelector('[name="s"]');
    const kind = form.querySelector('[name="pn_kind"]');
    const results = form.querySelector('.pagenest-search-suggestions');
    const status = form.querySelector('.pagenest-search-status');
    let timer,
      controller,
      generation = 0;
    const update = () => {
      clearTimeout(timer);
      controller?.abort();
      const turn = ++generation;
      results.replaceChildren();
      results.hidden = true;
      status.textContent = '';
      if (input.value.trim().length < 2) return;
      timer = setTimeout(async () => {
        controller = new AbortController();
        const requestController = controller;
        const timeout = setTimeout(() => requestController.abort(), 8000);
        status.textContent = l10n.loading;
        try {
          const url = new URL(form.dataset.pagenestSearchEndpoint, location.href);
          if (url.origin !== location.origin) throw new Error('Invalid search origin');
          url.searchParams.set('q', input.value.trim().slice(0, 100));
          url.searchParams.set('kind', kind.value);
          const response = await fetch(url, {
            signal: controller.signal,
            credentials: 'omit',
            cache: 'no-store',
          });
          if (!response.ok) throw new Error('Search unavailable');
          const data = await response.json();
          if (turn !== generation) return;
          if (!Array.isArray(data.items)) throw new Error('Invalid results');
          data.items.slice(0, 8).forEach((item) => {
            const url = new URL(item.url, location.href);
            if (url.origin !== location.origin || !['http:', 'https:'].includes(url.protocol))
              return;
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = url.href;
            link.textContent = item.title;
            const label = document.createElement('small');
            label.textContent = item.kind_label;
            li.append(link, label);
            results.append(li);
          });
          results.hidden = !results.childElementCount;
          status.textContent = results.childElementCount
            ? l10n.results.replace('%s', () => String(results.childElementCount))
            : l10n.empty;
        } catch (error) {
          if (turn === generation) status.textContent = l10n.unavailable;
        } finally {
          clearTimeout(timeout);
        }
      }, 300);
    };
    input.addEventListener('input', (event) => {
      if (!event.isComposing) update();
    });
    input.addEventListener('compositionend', update);
    kind.addEventListener('change', update);
    form.addEventListener('submit', () => {
      clearTimeout(timer);
      generation++;
      controller?.abort();
    });
  });
})();
