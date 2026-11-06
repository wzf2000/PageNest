(() => {
  const initialize = () => {
    'use strict';
    const header = document.querySelector('.pagenest-header');
    if (!header) return;
    const t = {
      account: 'My space',
      close: 'Close',
      show: 'Show navigation',
      siteTools: 'Site tools',
      ...window.pagenestHeaderL10n,
    };
    const mobile = matchMedia('(max-width: 767px)');
    const bar = document.querySelector('#wpadminbar');
    const overlays = window.PageNestOverlays;
    let toolbarDialog = null;
    const makeButton = (label, path) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'pagenest-header-icon';
      button.setAttribute('aria-label', label);
      button.title = label;
      button.innerHTML =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="' +
        path +
        '"/></svg>';
      return button;
    };
    if (bar && overlays) {
      const home = document.createComment('native toolbar home');
      bar.before(home);
      const button = makeButton(
        t.account,
        'M16 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 22v-3a8 8 0 0 1 16 0v3',
      );
      header.querySelector('.pagenest-nav-inner').append(button);
      toolbarDialog = document.createElement('dialog');
      toolbarDialog.className = 'pagenest-reading-dialog pagenest-toolbar-dialog';
      toolbarDialog.setAttribute('aria-label', t.account);
      const head = document.createElement('div');
      head.className = 'pagenest-reading-dialog-head';
      const title = document.createElement('h2');
      title.textContent = t.account;
      const close = makeButton(t.close, 'm6 6 12 12M18 6 6 18');
      close.dataset.pagenestDialogClose = '';
      close.addEventListener('click', () => overlays.close(toolbarDialog));
      head.append(title, close);
      toolbarDialog.append(head);
      document.body.append(toolbarDialog);
      button.addEventListener('click', () => overlays.open(toolbarDialog, { trigger: button }));
      toolbarDialog.addEventListener('cancel', (e) => {
        e.preventDefault();
        overlays.close(toolbarDialog);
      });
      toolbarDialog.addEventListener('click', (e) => {
        if (e.target === toolbarDialog) overlays.close(toolbarDialog);
      });
      const tools = bar.querySelector('#wp-admin-bar-root-default');
      const toolsHome = document.createComment('native site tools home');
      const toolsGroup = document.createElement('details');
      toolsGroup.className = 'pagenest-toolbar-site-tools';
      const toolsSummary = document.createElement('summary');
      toolsSummary.textContent = t.siteTools;
      toolsGroup.append(toolsSummary);
      tools?.before(toolsHome);
      const place = () => {
        if (mobile.matches) {
          toolbarDialog.append(bar);
          if (tools) {
            bar.querySelector('#wp-toolbar').append(toolsGroup);
            toolsGroup.append(tools);
          }
          document.documentElement.classList.add('pagenest-toolbar-contained');
          button.hidden = false;
        } else {
          overlays.close(toolbarDialog);
          if (tools) toolsHome.after(tools);
          toolsGroup.remove();
          home.after(bar);
          document.documentElement.classList.remove('pagenest-toolbar-contained');
          button.hidden = true;
        }
      };
      place();
      mobile.addEventListener('change', place);
    }
    if (!document.querySelector('.pagenest-reading-grid')) return;
    const recall = makeButton(t.show, 'm6 9 6 6 6-6');
    recall.classList.add('pagenest-header-recall');
    const recallLabel = document.createElement('span');
    recallLabel.textContent = t.show;
    recall.append(recallLabel);
    recall.hidden = true;
    document.body.append(recall);
    let lastY = scrollY,
      distance = 0,
      frame = 0;
    // Main remeasures opted-in obstructions on resize. Also notify without a CSS
    // transition (reduced motion), when no transitionend event will be emitted.
    const notifyHeaderGeometry = () =>
      requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
    const show = () => {
      const wasHidden = header.classList.contains('pagenest-header-away');
      header.classList.remove('pagenest-header-away');
      bar?.classList.remove('pagenest-header-away');
      recall.hidden = true;
      distance = 0;
      if (wasHidden) notifyHeaderGeometry();
    };
    recall.addEventListener('click', () => {
      const parent = recall.closest('dialog');
      if (parent) overlays.close(parent);
      show();
      header.querySelector('.pagenest-menu-toggle')?.focus({ preventScroll: true });
    });
    header.addEventListener('focusin', show);
    header.addEventListener('transitionend', (event) => {
      if (event.target === header && event.propertyName === 'transform') notifyHeaderGeometry();
    });
    mobile.addEventListener('change', show);
    addEventListener(
      'scroll',
      () => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
          frame = 0;
          const delta = scrollY - lastY;
          lastY = scrollY;
          if (
            !mobile.matches ||
            document.querySelector('dialog[open]') ||
            (header.contains(document.activeElement) &&
              document.activeElement.matches(':focus-visible')) ||
            header.querySelector('.pagenest-header-search[open],.pagenest-nav-open')
          ) {
            distance = 0;
            return;
          }
          if (scrollY < 120) {
            show();
            return;
          }
          distance = Math.sign(delta) === Math.sign(distance) ? distance + delta : delta;
          if (distance < -24) show();
          else if (distance > 64 && !header.classList.contains('pagenest-header-away')) {
            header.classList.add('pagenest-header-away');
            if (!toolbarDialog?.contains(bar)) bar?.classList.add('pagenest-header-away');
            recall.hidden = false;
            notifyHeaderGeometry();
          }
        });
      },
      { passive: true },
    );
  };
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
