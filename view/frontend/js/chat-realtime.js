/**
 * GaiaLumen Chat Realtime (WebSocket client)
 * Requires Ratchet server running (default ws://127.0.0.1:8081)
 */

(function () {
  'use strict';

  if (window.__GL_CHAT_REALTIME_LOADED__) return;
  window.__GL_CHAT_REALTIME_LOADED__ = true;

  const DEFAULT_URL = 'ws://127.0.0.1:8081';

  const state = {
    ws: null,
    url: DEFAULT_URL,
    connected: false,
    rooms: new Set(), // room ids: challenge:<id> or channel:<id>
    listeners: new Set(),
    reconnectTimer: null,
    typingDebounce: null,
    lastTypingSentAt: 0,
  };

  function emit(evt) {
    state.listeners.forEach((fn) => {
      try { fn(evt); } catch (_) {}
    });
  }

  function connect(url) {
    state.url = url || state.url || DEFAULT_URL;
    if (state.ws && (state.ws.readyState === WebSocket.OPEN || state.ws.readyState === WebSocket.CONNECTING)) {
      return;
    }

    try {
      state.ws = new WebSocket(state.url);
    } catch (e) {
      scheduleReconnect();
      return;
    }

    state.ws.addEventListener('open', () => {
      state.connected = true;
      emit({ type: 'ws:open' });

      const u = window.__USER__ || {};
      const name = (u.nom || u.pseudo || 'Invité').toString();
      try {
        state.ws.send(JSON.stringify({ type: 'hello', name }));
      } catch (_) {}

      // re-join rooms
      state.rooms.forEach((roomId) => {
        try {
          state.ws.send(buildRoomPayload('join', roomId));
        } catch (_) {}
      });
    });

    state.ws.addEventListener('message', (e) => {
      let data = null;
      try { data = JSON.parse(e.data); } catch (_) {}
      if (!data || typeof data !== 'object') return;
      emit(data);
    });

    state.ws.addEventListener('close', () => {
      state.connected = false;
      emit({ type: 'ws:close' });
      scheduleReconnect();
    });

    state.ws.addEventListener('error', () => {
      // close triggers reconnect
      try { state.ws.close(); } catch (_) {}
    });
  }

  function scheduleReconnect() {
    if (state.reconnectTimer) return;
    state.reconnectTimer = setTimeout(() => {
      state.reconnectTimer = null;
      connect(state.url);
    }, 1500);
  }

  function normalizeRoom(room) {
    if (typeof room === 'number' || /^\d+$/.test((room || '').toString())) {
      const cid = parseInt(room || 0, 10);
      return cid > 0 ? `challenge:${cid}` : '';
    }
    const channel = (room || '').toString().trim();
    if (!/^[a-zA-Z0-9_-]{1,60}$/.test(channel)) return '';
    return `channel:${channel}`;
  }

  function buildRoomPayload(type, roomId, extra) {
    const payload = { ...(extra || {}), type };
    if (roomId.indexOf('challenge:') === 0) {
      payload.challenge_id = parseInt(roomId.replace('challenge:', ''), 10);
    } else if (roomId.indexOf('channel:') === 0) {
      payload.channel_id = roomId.replace('channel:', '');
    }
    return payload;
  }

  function join(room) {
    const roomId = normalizeRoom(room);
    if (!roomId) return;
    state.rooms.add(roomId);
    if (!state.ws || state.ws.readyState !== WebSocket.OPEN) {
      connect(state.url);
      return;
    }
    try {
      state.ws.send(JSON.stringify(buildRoomPayload('join', roomId)));
    } catch (_) {}
  }

  function sendTyping(room, isTyping) {
    const roomId = normalizeRoom(room);
    if (!roomId) return;
    if (!state.ws || state.ws.readyState !== WebSocket.OPEN) return;

    const now = Date.now();
    if (isTyping && now - state.lastTypingSentAt < 900) return;
    state.lastTypingSentAt = now;

    try {
      state.ws.send(JSON.stringify(buildRoomPayload('typing', roomId, { is_typing: !!isTyping })));
    } catch (_) {}
  }

  function sendSignal(room, payload) {
    const roomId = normalizeRoom(room);
    if (!roomId || !payload || typeof payload !== 'object') return false;
    if (!state.ws || state.ws.readyState !== WebSocket.OPEN) {
      connect(state.url);
      return false;
    }
    const type = (payload.type || '').toString();
    if (!type || type.indexOf('webrtc:') !== 0) return false;
    const out = buildRoomPayload(type, roomId, payload);
    try {
      state.ws.send(JSON.stringify(out));
      return true;
    } catch (_) {
      return false;
    }
  }

  function sendChatEvent(room, type, payload) {
    const roomId = normalizeRoom(room);
    const eventType = (type || '').toString();
    if (!roomId || !eventType || eventType.indexOf('message:') !== 0) return false;
    if (!state.ws || state.ws.readyState !== WebSocket.OPEN) {
      connect(state.url);
      return false;
    }
    try {
      state.ws.send(JSON.stringify(buildRoomPayload(eventType, roomId, payload || {})));
      return true;
    } catch (_) {
      return false;
    }
  }

  function on(fn) {
    state.listeners.add(fn);
    return () => state.listeners.delete(fn);
  }

  window.ChatRealtime = {
    __ready: true,
    connect,
    join,
    sendTyping,
    sendSignal,
    sendChatEvent,
    on,
  };

  // auto-connect best effort
  if (typeof WebSocket !== 'undefined') {
    connect(DEFAULT_URL);
  }
})();

