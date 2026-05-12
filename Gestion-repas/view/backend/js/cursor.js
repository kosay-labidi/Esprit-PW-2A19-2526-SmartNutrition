/* cursor.js — Curseur personnalisé GaiaLumen */
(function() {
  function initCursor() {
    var cur   = document.getElementById('cursor');
    var trail = document.getElementById('cursor-trail');
    if (!cur || !trail) { return; }

    var mx = 0, my = 0, tx = 0, ty = 0;

    /* Suivre la souris */
    document.addEventListener('mousemove', function(e) {
      mx = e.clientX;
      my = e.clientY;
      cur.style.left = mx + 'px';
      cur.style.top  = my + 'px';
    });

    /* Animation fluide de la traîne */
    (function loop() {
      tx += (mx - tx) * 0.12;
      ty += (my - ty) * 0.12;
      trail.style.left = tx + 'px';
      trail.style.top  = ty + 'px';
      requestAnimationFrame(loop);
    })();

    /* Effet hover sur les éléments interactifs */
    function bindHover() {
      document.querySelectorAll('a, button, input, select, .module-card, .mod-card, .nav-item, .nav-links button, .tb-btn, .lang-pill').forEach(function(el) {
        if (el.dataset.cursorBound) return;
        el.dataset.cursorBound = '1';
        el.addEventListener('mouseenter', function() { cur.classList.add('hover'); });
        el.addEventListener('mouseleave', function() { cur.classList.remove('hover'); });
      });
    }

    bindHover();

    /* Rebind après chaque changement DOM (sections dynamiques) */
    var obs = new MutationObserver(function() { bindHover(); });
    obs.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCursor);
  } else {
    initCursor();
  }
})();
