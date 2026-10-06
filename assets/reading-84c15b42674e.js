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
      const entries = [];
      const stack = [];
      const branches = [];
      const nav = toc.querySelector('nav');
      const tree = document.createElement('ul');
      tree.className = 'pagenest-toc-tree';
      nav.append(tree);
      const setExpanded = (entry, expanded) => {
        entry.children.hidden = !expanded;
        entry.toggle.setAttribute('aria-expanded', String(expanded));
      };
      headings.forEach((h, i) => {
        if (!h.id) {
          let id = 'pagenest-section-' + (i + 1);
          while (document.getElementById(id)) id += '-x';
          h.id = id;
        }
        const level = Number(h.tagName.slice(1)) || 2;
        while (stack.length && stack.at(-1).level >= level) stack.pop();
        const parent = stack.at(-1) || null;
        const item = document.createElement('li');
        const row = document.createElement('div');
        row.className = 'pagenest-toc-row';
        const a = document.createElement('a');
        a.href = '#' + encodeURIComponent(h.id);
        if (h === body) a.textContent = '正文';
        else appendHeadingLabel(a, h);
        row.append(a);
        item.append(row);
        const children = document.createElement('ul');
        children.className = 'pagenest-toc-children';
        let groupId = 'pagenest-toc-group-' + (i + 1);
        while (document.getElementById(groupId)) groupId += '-x';
        children.id = groupId;
        const entry = { heading: h, level, parent, item, row, link: a, children };
        (parent ? parent.children : tree).append(item);
        entries.push(entry);
        links.push(a);
        stack.push(entry);
      });
      entries.forEach((entry) => {
        if (!entry.children.childElementCount) {
          entry.row.classList.add('pagenest-toc-leaf');
          return;
        }
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'pagenest-toc-toggle';
        toggle.setAttribute('aria-label', '展开或收起：' + entry.heading.textContent.trim());
        toggle.setAttribute('aria-controls', entry.children.id);
        entry.toggle = toggle;
        entry.row.prepend(toggle);
        entry.item.append(entry.children);
        setExpanded(entry, headings.length <= 12 || !entry.parent);
        toggle.addEventListener('click', () => {
          setExpanded(entry, entry.children.hidden);
          update();
        });
        branches.push(entry);
      });
      if (branches.length) {
        const controls = document.createElement('div');
        controls.className = 'pagenest-toc-controls';
        [
          ['展开全部', true],
          ['收起全部', false],
        ].forEach(([label, expanded]) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.textContent = label;
          button.addEventListener('click', () => {
            branches.forEach((entry) => setExpanded(entry, expanded));
            update();
          });
          controls.append(button);
        });
        nav.before(controls);
      }
      const revealHash = () => {
        let id;
        try {
          id = decodeURIComponent(location.hash.slice(1));
        } catch {
          return;
        }
        const target = document.getElementById(id);
        // Legacy empty anchors immediately preceding a heading share its directory branch.
        const entry = entries.find(
          (e) =>
            e.heading === target ||
            (target?.matches('a[id]:empty,span[id]:empty') &&
              target.nextElementSibling === e.heading),
        );
        if (!entry) return;
        for (let parent = entry.parent; parent; parent = parent.parent) setExpanded(parent, true);
        update();
      };
      addEventListener('hashchange', revealHash);
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
          if (h.getBoundingClientRect().top <= getReadingTop() + 120) active = i;
        });
        links.forEach((a, i) => {
          if (i === active) a.setAttribute('aria-current', 'location');
          else a.removeAttribute('aria-current');
        });
        entries.forEach((entry) => {
          entry.row.classList.remove('pagenest-toc-contains-current');
          entry.toggle?.removeAttribute('title');
        });
        let visible = entries[active];
        for (let parent = visible.parent; parent; parent = parent.parent) {
          if (parent.children.hidden) visible = parent;
        }
        if (visible !== entries[active]) {
          visible.row.classList.add('pagenest-toc-contains-current');
          visible.toggle.title = '包含当前阅读位置';
        }
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
      revealHash();
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

  function getReadingTop() {
    const admin = document.getElementById('wpadminbar');
    const header = document.querySelector('.pagenest-header');
    const adminBottom = admin ? Math.max(0, admin.getBoundingClientRect().bottom) : 0;
    const headerBottom = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
    return Math.max(adminBottom, headerBottom, 0) + 16;
  }
  const rail = document.querySelector('.pagenest-reading-rail');
  if (rail) {
    let queued = false;
    const size = () => {
      queued = false;
      const top = getReadingTop();
      document.documentElement.style.setProperty('--pagenest-reading-top', `${top}px`);
      document.documentElement.style.setProperty('--pagenest-anchor-top', `${top + 8}px`);
      rail.classList.toggle('pagenest-reading-short', innerHeight - top < 380);
      document.dispatchEvent(new Event('pagenest-reading-layout'));
    };
    const schedule = () => {
      if (!queued) {
        queued = true;
        requestAnimationFrame(size);
      }
    };
    size();
    addEventListener('resize', schedule);
    window.visualViewport?.addEventListener('resize', schedule);
    new ResizeObserver(schedule).observe(document.querySelector('.pagenest-header') || rail);
    const admin = document.getElementById('wpadminbar');
    if (admin) new ResizeObserver(schedule).observe(admin);
  }
})();
