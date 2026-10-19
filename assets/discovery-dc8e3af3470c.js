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
  panel?.addEventListener('keydown', (event) => {
    if (
      event.key !== 'Escape' ||
      !(event.target instanceof Element) ||
      !panel.open ||
      event.defaultPrevented ||
      event.isComposing ||
      event.keyCode === 229 ||
      event.target.closest('dialog, [role="dialog"], [aria-modal="true"]')
    )
      return;
    event.preventDefault();
    panel.open = false;
    panel.querySelector('summary').focus();
  });
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
          for (const name of ['category', 'series', 'sort'])
            url.searchParams.set(
              name,
              form.querySelector(`[name="pn_${name}"]`)?.value ||
                (name === 'category' ? '0' : name === 'sort' ? 'relevance' : ''),
            );
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
            const detail = document.createElement('div');
            detail.append(link);
            const excerpt = document.createElement('p');
            const highlight = (target, text) => {
              const query = input.value.trim();
              const position = text.toLocaleLowerCase().indexOf(query.toLocaleLowerCase());
              if (position < 0) {
                target.textContent = text;
                return;
              }
              const mark = document.createElement('mark');
              mark.textContent = text.slice(position, position + query.length);
              target.append(
                document.createTextNode(text.slice(0, position)),
                mark,
                document.createTextNode(text.slice(position + query.length)),
              );
            };
            highlight(excerpt, String(item.excerpt || ''));
            detail.append(excerpt);
            for (const section of (item.sections || []).slice(0, 2)) {
              const sectionUrl = new URL(section.url, location.href);
              if (
                sectionUrl.origin !== location.origin ||
                !['http:', 'https:'].includes(sectionUrl.protocol)
              )
                continue;
              const a = document.createElement('a');
              a.href = sectionUrl.href;
              a.className = 'pagenest-search-section';
              highlight(a, String(section.title || ''));
              detail.append(a);
            }
            li.append(detail, label);
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
    form
      .querySelectorAll('select, [name="pn_series"]')
      .forEach((control) => control.addEventListener('change', update));
    form.addEventListener('submit', () => {
      clearTimeout(timer);
      generation++;
      controller?.abort();
    });
  });
})();
