/* Native dialogs share one scroll boundary; opening a panel is never page navigation. */
(() => {
  const active = new Map();
  let readingY = 0;
  let readingAnchor = null;
  const captureReadingAnchor = () => {
    const body = document.querySelector('.pagenest-article-body');
    const top =
      Math.max(
        0,
        document.querySelector('.pagenest-header')?.getBoundingClientRect().bottom || 0,
        document.getElementById('wpadminbar')?.getBoundingClientRect().bottom || 0,
      ) + 16;
    const node = [...(body?.querySelectorAll('h1,h2,h3,h4,h5,h6,p,pre,li,blockquote') || [])].find(
      (item) => {
        if (
          item.closest('[data-pagenest-generated],[data-mbb-generated],.pagenest-toc') ||
          item.checkVisibility?.() === false ||
          !item.getClientRects().length
        )
          return false;
        const bounds = item.getBoundingClientRect();
        return bounds.bottom > top && bounds.top < innerHeight;
      },
    );
    return node ? { node, top: node.getBoundingClientRect().top } : null;
  };
  const notify = () => {
    document.documentElement.classList.toggle('pagenest-overlay-open', active.size > 0);
    document.dispatchEvent(
      new CustomEvent('pagenest-overlay-change', { detail: { open: active.size > 0 } }),
    );
  };
  function open(dialog, { trigger = document.activeElement, onClose = () => {} } = {}) {
    if (active.has(dialog)) return;
    if (!active.size) {
      readingY = scrollY;
      readingAnchor = captureReadingAnchor();
    }
    const finish = () => {
      if (!active.has(dialog)) return;
      active.delete(dialog);
      dialog.removeEventListener('close', finish);
      notify();
      if (trigger?.isConnected) trigger.focus({ preventScroll: true });
      if (!active.size) {
        // Font/width preferences can reflow everything above the reader's paragraph.
        const target =
          readingAnchor?.node.isConnected && readingAnchor.node.getClientRects().length
            ? scrollY + readingAnchor.node.getBoundingClientRect().top - readingAnchor.top
            : readingY;
        if (scrollY !== target) scrollTo({ top: target, behavior: 'instant' });
        readingAnchor = null;
      }
      onClose();
    };
    active.set(dialog, finish);
    dialog.addEventListener('close', finish);
    notify();
    try {
      dialog.showModal();
    } catch (error) {
      finish();
      throw error;
    }
    dialog
      .querySelector('[data-pagenest-dialog-close], [data-pagenest-share-close], button')
      ?.focus({ preventScroll: true });
  }
  function close(dialog) {
    if (dialog.open) dialog.close();
    active.get(dialog)?.();
  }
  window.PageNestOverlays = { open, close };
  const connectShare = () => window.PageNestSocialShare?.setDialogAdapter({ open, close });
  if (document.readyState === 'loading')
    document.addEventListener('DOMContentLoaded', connectShare, { once: true });
  else connectShare();
})();
