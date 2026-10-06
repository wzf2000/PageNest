(() => {
  'use strict';
  document.querySelectorAll('.pagenest-comment-avatar img').forEach((img) => {
    const fallback = () => {
      img.hidden = true;
    };
    img.addEventListener('error', fallback);
    if (img.complete && !img.naturalWidth) fallback();
  });
  const commentArea = document.querySelector('.pagenest-comments');
  if (commentArea) {
    const labelUploads = () =>
      commentArea.querySelectorAll('.chevereto-pup-button').forEach((b) => {
        b.type = 'button';
        const label = b.querySelector('.chevereto-pup-button-text');
        if (label && label.textContent !== '上传图片') label.textContent = '上传图片';
      });
    labelUploads();
    new MutationObserver(labelUploads).observe(commentArea, { childList: true, subtree: true });
  }

  function placeNotes() {
    const noteButton = document.querySelector('.llmn-open'),
      bar = document.querySelector('.pagenest-nav-inner');
    if (noteButton && bar) bar.insertBefore(noteButton, bar.querySelector('.pagenest-menu-toggle'));
  }
  placeNotes();
  document.addEventListener('llmn-ready', placeNotes);
  // Keep only text and explicit math source, never clone heading IDs or controls.
  function appendHeadingLabel(parent, node) {
    if (node.nodeType === Node.TEXT_NODE) {
      parent.append(document.createTextNode(node.textContent));
      return;
    }
    if (node.nodeType !== Node.ELEMENT_NODE) return;
    if (node.matches('span.mbb-math[data-mbb-tex]')) {
      const math = document.createElement('span');
      math.className = 'mbb-math';
      math.setAttribute('data-mbb-tex', node.getAttribute('data-mbb-tex'));
      math.textContent = '$' + node.getAttribute('data-mbb-tex') + '$';
      parent.append(math);
      return;
    }
    if (node.matches('script,style,button,input,[aria-hidden="true"]')) return;
    node.childNodes.forEach((child) => appendHeadingLabel(parent, child));
  }
  const body = document.querySelector('.pagenest-article-body'),
    toc = document.querySelector('.pagenest-toc');
  if (body && toc) {
    let headings = [...body.querySelectorAll('h1,h2,h3,h4,h5,h6')].filter((h) =>
      h.textContent.trim(),
    );
    if (!headings.length) headings = [body];
    if (headings.length) {
      const links = [];
      const baseLevel = Math.min(...headings.map((n) => Number(n.tagName.slice(1)) || 2));
      headings.forEach((h, i) => {
        if (!h.id) {
          let id = 'pagenest-section-' + (i + 1);
          while (document.getElementById(id)) id += '-x';
          h.id = id;
        }
        const a = document.createElement('a');
        a.href = '#' + encodeURIComponent(h.id);
        if (h === body) a.textContent = '正文';
        else appendHeadingLabel(a, h);
        if (h !== body) {
          const level = Number(h.tagName.slice(1));
          a.style.paddingInlineStart = 9 + Math.max(0, level - baseLevel) * 10 + 'px';
        }
        toc.querySelector('nav').append(a);
        links.push(a);
      });
      const renderMath = () => window.MBB_MATH?.typeset(toc);
      renderMath();
      // Also cover a renderer script loaded after the directory script.
      if (document.readyState !== 'complete') addEventListener('load', renderMath, { once: true });
      toc.hidden = false;
      const anchor = document.createComment('desktop directory');
      toc.before(anchor);
      const mq = matchMedia('(max-width:1023px)');
      const place = () => {
        if (mq.matches) {
          body.prepend(toc);
          toc.classList.add('pagenest-mobile-toc');
          toc.open = false;
        } else {
          anchor.after(toc);
          toc.classList.remove('pagenest-mobile-toc');
          toc.open = true;
        }
      };
      place();
      mq.addEventListener('change', place);
      let queued = false;
      const update = () => {
        queued = false;
        let active = 0;
        headings.forEach((h, i) => {
          if (h.getBoundingClientRect().top <= 220) active = i;
        });
        links.forEach((a, i) => {
          if (i === active) a.setAttribute('aria-current', 'location');
          else a.removeAttribute('aria-current');
        });
      };
      addEventListener(
        'scroll',
        () => {
          if (!queued) {
            queued = true;
            requestAnimationFrame(update);
          }
        },
        { passive: true },
      );
      update();
    }
  }
  document.querySelectorAll('.pagenest-page .game-container').forEach((game) => {
    const frame = document.createElement('div');
    frame.className = 'pagenest-game-fit';
    game.before(frame);
    frame.append(game);
    const fit = () => {
      const scale = Math.min(1, frame.clientWidth / game.offsetWidth);
      game.style.transform = 'scale(' + scale + ')';
      frame.style.height = game.offsetHeight * scale + 'px';
    };
    new ResizeObserver(fit).observe(frame);
    fit();
  });
  if (location.hash) {
    const reveal = () => {
      let id;
      try {
        id = decodeURIComponent(location.hash.slice(1));
      } catch {
        return;
      }
      const target = document.getElementById(id);
      if (target) target.scrollIntoView();
    };
    if (window.MathJax?.Hub) MathJax.Hub.Queue(reveal);
    else addEventListener('load', reveal, { once: true });
  }
})();
