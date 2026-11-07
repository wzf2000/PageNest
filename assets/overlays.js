/* Native dialogs share one scroll boundary; opening a panel is never page navigation. */
(() => {
  const active = new Map();
  let readingY = 0;
  const notify = () => {
    document.documentElement.classList.toggle('pagenest-overlay-open', active.size > 0);
    document.dispatchEvent(
      new CustomEvent('pagenest-overlay-change', { detail: { open: active.size > 0 } }),
    );
  };
  function open(dialog, { trigger = document.activeElement, onClose = () => {} } = {}) {
    if (active.has(dialog)) return;
    if (!active.size) readingY = scrollY;
    const finish = () => {
      if (!active.has(dialog)) return;
      active.delete(dialog);
      dialog.removeEventListener('close', finish);
      notify();
      if (trigger?.isConnected) trigger.focus({ preventScroll: true });
      if (!active.size && scrollY !== readingY) scrollTo({ top: readingY, behavior: 'instant' });
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
