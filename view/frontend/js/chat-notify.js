/**
 * GaiaLumen Chat Notifications (polling fallback)
 * - Tracks last seen per défi channel in localStorage.
 * - Polls latest message timestamp to compute unread counts.
 */
(function () {
  'use strict';

  if (window.__GL_CHAT_NOTIFY_LOADED__) return;
  window.__GL_CHAT_NOTIFY_LOADED__ = true;

  function getBackendApiPath(file) {
    const isModule = window.location.pathname.includes('/modules/');
    const base = isModule ? '../../backend' : '../backend';
    return `${base}/api/${file}`;
  }

  const KEY = 'gl_chat_last_seen_v1';

  function loadSeen() {
    try { return JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (_) { return {}; }
  }
  function saveSeen(obj) {
    try { localStorage.setItem(KEY, JSON.stringify(obj || {})); } catch (_) {}
  }

  function markSeen(challengeId, ts) {
    const seen = loadSeen();
    seen[String(challengeId)] = ts || Date.now();
    saveSeen(seen);
    fetch(getBackendApiPath('chat/notifications.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify({ challenge_id: parseInt(challengeId || 0, 10) || 0 }),
    }).catch(() => {});
  }

  async function fetchUnreadCounts() {
    const r = await fetch(`${getBackendApiPath('chat/notifications.php')}?t=${Date.now()}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    if (!r.ok) return null;
    const j = await r.json();
    if (!j || !j.ok || !j.counts) return null;
    return j.counts;
  }

  async function fetchLatestTs(challengeId) {
    const cid = parseInt(challengeId || 0, 10);
    if (!cid) return 0;
    const url = `${getBackendApiPath('chat/messages.php')}?challenge_id=${cid}&limit=1&t=${Date.now()}`;
    const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
    if (!r.ok) return 0;
    const j = await r.json();
    const m = j && j.ok && j.messages && j.messages[0];
    if (!m || !m.created_at) return 0;
    const t = new Date(m.created_at).getTime();
    return Number.isFinite(t) ? t : 0;
  }

  function updateFabBadge() {
    const fab = document.querySelector('.gl-chat-fab');
    if (!fab || !window.ChatState || !window.ChatState.channels) return;
    const totalUnread = Object.values(window.ChatState.channels).reduce((s, c) => s + (parseInt(c.unread || 0, 10) || 0), 0);
    const dot = fab.querySelector('.gl-chat-fab-dot');
    if (dot) dot.style.display = totalUnread > 0 ? 'block' : 'none';
  }

  async function tick() {
    if (!window.ChatState || !window.ChatState.channels) return;
    const serverCounts = await fetchUnreadCounts().catch(() => null);
    if (serverCounts) {
      Object.values(window.ChatState.channels).forEach((ch) => {
        if (!ch || typeof ch.id !== 'string' || !ch.id.startsWith('defi_')) return;
        const cid = ch.id.replace('defi_', '');
        ch.unread = parseInt(serverCounts[cid] || 0, 10) || 0;
      });
      updateFabBadge();
      if (typeof window.refreshChatDefiTabs === 'function') window.refreshChatDefiTabs();
      return;
    }

    const seen = loadSeen();
    const channels = Object.values(window.ChatState.channels).filter((c) => c && typeof c.id === 'string' && c.id.startsWith('defi_'));
    for (const ch of channels) {
      const cid = parseInt(ch.id.replace('defi_', ''), 10);
      if (!cid) continue;
      const lastSeen = parseInt(seen[String(cid)] || 0, 10) || 0;
      const latest = await fetchLatestTs(cid);
      if (!latest) continue;
      if (latest > lastSeen) {
        // simplistic unread indicator: 1 if newer exists
        ch.unread = Math.max(1, parseInt(ch.unread || 0, 10) || 0);
      } else {
        ch.unread = 0;
      }
    }
    updateFabBadge();

    // If modal is open on a défi channel, auto-refresh history when unread detected
    try {
      const overlay = document.getElementById('chat-defi-modal');
      const active = overlay && overlay.classList.contains('active');
      const activeCh = window.__glModalActiveChannel || '';
      if (active && typeof activeCh === 'string' && activeCh.startsWith('defi_')) {
        const cid = parseInt(activeCh.replace('defi_', ''), 10);
        const ch = window.ChatState && window.ChatState.channels && window.ChatState.channels[activeCh];
        if (cid && ch && ch.unread > 0 && typeof window.__loadModalHistoryIfDefi === 'function') {
          await window.__loadModalHistoryIfDefi();
          markSeen(cid, Date.now());
          ch.unread = 0;
          updateFabBadge();
        }
      }
    } catch (_) {}
  }

  window.ChatNotify = {
    __ready: true,
    markSeen,
    tick,
  };

  // Poll every 7s (lightweight: 1 req per défi channel)
  setInterval(() => { tick().catch(() => {}); }, 7000);
})();
