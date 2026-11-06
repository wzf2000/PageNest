(() => {
  const initialize = () => {
    'use strict';
    const article = document.querySelector('.pagenest-reading-grid .pagenest-article');
    const overlays = window.PageNestOverlays;
    if (!article || !overlays) return;
    const t = {
      contents: 'Contents',
      discussion: 'Discussion',
      more: 'Reading and more',
      settings: 'Reading settings',
      share: 'Share article',
      close: 'Close',
      ...window.pagenestReadingToolsL10n,
    };
    const element = (tag, cls, text) => {
      const node = document.createElement(tag);
      if (cls) node.className = cls;
      if (text) node.textContent = text;
      return node;
    };
    const paths = {
      contents: 'M4 5h16M4 12h16M4 19h16',
      discussion: 'M4 4h16v13H9l-5 4V4Z',
      more: 'M5 12h.01M12 12h.01M19 12h.01',
      settings: 'M4 7h16M4 17h16M8 4v6m8 4v6',
      share: 'M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8',
      close: 'm6 6 12 12M18 6 6 18',
    };
    function button(label, kind, action) {
      const node = element('button', 'pagenest-reading-tool');
      node.type = 'button';
      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('viewBox', '0 0 24 24');
      svg.setAttribute('aria-hidden', 'true');
      const path = document.createElementNS(svg.namespaceURI, 'path');
      path.setAttribute('d', paths[kind]);
      svg.append(path);
      node.append(svg, element('span', '', label));
      node.addEventListener('click', action);
      return node;
    }
    let serial = 0;
    function panel(label) {
      const dialog = element('dialog', 'pagenest-reading-dialog');
      const head = element('div', 'pagenest-reading-dialog-head');
      const title = element('h2', '', label);
      title.id = `pagenest-reading-panel-${++serial}`;
      dialog.setAttribute('aria-labelledby', title.id);
      const close = button(t.close, 'close', () => overlays.close(dialog));
      close.dataset.pagenestDialogClose = '';
      head.append(title, close);
      const body = element('div', 'pagenest-reading-dialog-body');
      dialog.append(head, body);
      document.body.append(dialog);
      dialog.addEventListener('cancel', (e) => {
        e.preventDefault();
        overlays.close(dialog);
      });
      dialog.addEventListener('click', (e) => {
        if (e.target === dialog) overlays.close(dialog);
      });
      return { dialog, body };
    }
    const toolbar = element('nav', 'pagenest-reading-tools');
    toolbar.setAttribute('aria-label', t.more);
    const more = panel(t.more);
    const moreButton = button(t.more, 'more', () =>
      overlays.open(more.dialog, { trigger: moreButton }),
    );
    const prefs = document.querySelector('[data-pagenest-reader-preferences]');
    if (prefs) {
      const settings = panel(t.settings);
      settings.body.append(prefs);
      prefs.open = true;
      const control = button(t.settings, 'settings', () =>
        overlays.open(settings.dialog, { trigger: control }),
      );
      more.body.append(control);
    }
    const recall = document.querySelector('.pagenest-header-recall');
    if (recall) more.body.append(recall);
    const later = article.querySelector('.pn-reading-article');
    if (later) more.body.append(later);
    const sharing = article.querySelector('.pagenest-social-share');
    if (sharing) {
      const share = panel(t.share);
      share.body.append(sharing);
      const control = button(t.share, 'share', () =>
        overlays.open(share.dialog, { trigger: control }),
      );
      more.body.append(control);
    }
    const toc = document.querySelector('.pagenest-toc');
    if (toc) {
      const contents = panel(t.contents);
      const mobile = matchMedia('(max-width:767px)');
      const control = button(t.contents, 'contents', () =>
        overlays.open(contents.dialog, { trigger: control }),
      );
      toolbar.append(control);
      const place = () => {
        control.hidden = !mobile.matches;
        if (mobile.matches) {
          contents.body.append(toc);
          toc.open = true;
        } else overlays.close(contents.dialog);
      };
      document.addEventListener('pagenest-toc-placed', place);
      mobile.addEventListener('change', () =>
        document.dispatchEvent(new Event('pagenest-toc-place-request')),
      );
      place();
      contents.body.addEventListener(
        'click',
        (e) => {
          if (e.target.closest('a[href]')) overlays.close(contents.dialog);
        },
        true,
      );
    }
    function attachComments() {
      const opener = document.querySelector('.pagenest-paragraph-open');
      if (!opener || toolbar.contains(opener)) return;
      opener.classList.add('pagenest-reading-tool');
      const label = opener.querySelector('span');
      if (label) label.textContent = t.discussion;
      toolbar.insertBefore(opener, moreButton);
    }
    toolbar.append(moreButton);
    const desktopActions = article.querySelector('.pagenest-reading-actions');
    const articleFooter = article.querySelector('.pagenest-article-footer');
    const narrow = matchMedia('(max-width:767px)');
    const placeToolbar = () => {
      (narrow.matches ? articleFooter : desktopActions || articleFooter)?.append(toolbar);
    };
    placeToolbar();
    narrow.addEventListener('change', placeToolbar);
    attachComments();
    document.addEventListener('pagenest-pc-ready', attachComments);
    document.body.classList.add('pagenest-has-reading-tools');
  };
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
