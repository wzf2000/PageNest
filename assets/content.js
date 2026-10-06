(() => {
  'use strict';
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
        wrapper.setAttribute('aria-label', '可横向滚动的表格');
        table.before(wrapper);
        wrapper.append(table);
      });
  };
  wrap();
  if (document.querySelector('.editormd'))
    new MutationObserver(wrap).observe(document.body, { childList: true, subtree: true });
})();
