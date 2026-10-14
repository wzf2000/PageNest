(() => {
  'use strict';
  const l10n = {
    tableLabel: 'Horizontally scrollable table',
    ...window.pagenestContentL10n,
  };
  const wrap = () => {
    const aliases = window.PageNestContentAliases;
    if (aliases && typeof aliases === 'object' && !Array.isArray(aliases)) {
      const valid = Object.entries(aliases).filter(
        ([from, to]) =>
          typeof to === 'string' && /^[a-zA-Z0-9_-]+$/.test(from) && /^[a-zA-Z0-9_-]+$/.test(to),
      );
      document.querySelectorAll('.editormd-preview-container [class]').forEach((node) => {
        valid.forEach(([from, to]) => {
          if (node.classList.contains(from)) node.classList.add(to);
        });
      });
    }
    document
      .querySelectorAll('.pagenest-article-body table, .editormd-preview-container table')
      .forEach((table) => {
        if (table.parentElement.classList.contains('pagenest-table-scroll')) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'pagenest-table-scroll';
        wrapper.tabIndex = 0;
        wrapper.setAttribute('role', 'region');
        wrapper.setAttribute('aria-label', l10n.tableLabel);
        table.before(wrapper);
        wrapper.append(table);
      });
  };
  wrap();
  if (document.querySelector('.editormd'))
    new MutationObserver(wrap).observe(document.body, { childList: true, subtree: true });
})();

// Move trusted host roots, preserving the existing nodes and their listeners.
document.querySelectorAll('.pagenest-tool-page .pagenest-article-body').forEach((body) => {
  const hosts = [...body.querySelectorAll('.pagenest-tool-host')];
  if (!hosts.length) return;
  const fragment = document.createDocumentFragment();
  hosts.forEach((host) => fragment.append(host));
  const details = document.createElement('details');
  details.className = 'pagenest-tool-guide';
  const summary = document.createElement('summary');
  summary.textContent = '使用说明与规则';
  details.append(summary);
  [...body.childNodes].forEach((node) => {
    // Shortcode resources belong to the tool, even when there is no guide text.
    if (node.nodeType === Node.ELEMENT_NODE && ['LINK', 'STYLE', 'SCRIPT'].includes(node.tagName))
      fragment.append(node);
    else details.append(node);
  });
  body.append(fragment);
  if (
    details.textContent.replace(summary.textContent, '').trim() ||
    details.querySelector('img,video,iframe')
  )
    body.append(details);
});
