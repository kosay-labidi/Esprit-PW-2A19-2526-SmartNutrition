;(function () {
if (window.__GL_CHAT_DEFIS_LOADED__) {
  if (typeof window.ensureChatChannelsReady === 'function') {
    window.ensureChatChannelsReady();
  }
  return;
}
window.__GL_CHAT_DEFIS_LOADED__ = true;

/**
 * chat-defis.js — GaiaLumen Chat System v1.0
 * Système de Chat pour les Défis Collaboratifs + IA Claude
 */

console.log('💬 Chat Défis chargé');

// ═══════════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════════
const CHAT_CONFIG = {
  maxMessages: 120,         // Messages max par canal
  scrollDelay: 60,          // Délai scroll auto (ms)
  typingTimeout: 2800,      // Délai avant d'effacer "typing..."
  aiName: 'GaiaLumen IA',
  aiAvatar: '🌿',
  defaultAvatar: '👤',
};

function getBackendApiPath(file) {
  const isModule = window.location.pathname.includes('/modules/');
  const base = isModule ? '../../backend' : '../backend';
  return `${base}/api/${file}`;
}

let __inlineChatPendingImage = '';

function isAllowedChatImageUrl(u) {
  if (!u || typeof u !== 'string') return false;
  return u.includes('backend/api/uploads/chat/') || u.includes('/api/uploads/chat/');
}

function clearInlineChatPendingImage() {
  __inlineChatPendingImage = '';
  const wrap = document.getElementById('chat-image-preview-wrap');
  const img = document.getElementById('chat-image-preview');
  const file = document.getElementById('chat-file-input');
  const sendBtn = document.getElementById('chat-send-btn');
  const input = document.getElementById('chat-msg-input');
  if (wrap) wrap.style.display = 'none';
  if (img) img.removeAttribute('src');
  if (file) file.value = '';
  if (sendBtn && input) {
    sendBtn.disabled = input.value.trim() === '' && !__inlineChatPendingImage;
  }
}

function setInlineChatPendingImageUrl(url) {
  if (!isAllowedChatImageUrl(url)) return;
  __inlineChatPendingImage = url;
  const wrap = document.getElementById('chat-image-preview-wrap');
  const img = document.getElementById('chat-image-preview');
  const sendBtn = document.getElementById('chat-send-btn');
  if (wrap && img) {
    img.src = url;
    wrap.style.display = 'flex';
  }
  if (sendBtn) sendBtn.disabled = false;
}

async function refreshChatSessionUser() {
  try {
    const r = await fetch(getBackendApiPath('me.php'), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    if (!r.ok) return;
    const me = await r.json();
    window.__USER__ = { ...(window.__USER__ || {}), ...me };
  } catch (e) { /* ignore */ }
}

function pickInlineAuthorFromChannel(ch) {
  const u = window.__USER__ || {};
  const list = (ch && ch.participantsList) || [];
  const email = (u.email || '').toString().trim().toLowerCase();
  if (email && list.length) {
    const m = list.find(p => (p.email || '').toString().trim().toLowerCase() === email);
    if (m) {
      return {
        nom: m.nom || u.nom || 'Participant',
        avatar: '👤',
        participantId: m.id,
        userId: parseInt(u.id, 10) || 0,
      };
    }
  }
  return {
    nom: u.nom || u.pseudo || 'Vous',
    avatar: u.avatar || '😊',
    participantId: null,
    userId: parseInt(u.id, 10) || 0,
  };
}

async function uploadInlineChatImage(file) {
  const fd = new FormData();
  fd.append('file', file);
  const r = await fetch(getBackendApiPath('chat/attachments.php'), {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });
  const j = await r.json();
  const att = j && j.attachment ? j.attachment : j;
  if (!j || !j.ok || !att || !att.url) throw new Error((j && j.error) || 'Upload échoué');
  return att.url;
}

window.deleteInlineChatMessage = function deleteInlineChatMessage(msgId) {
  const ch = ChatState.channels[ChatState.activeChannel];
  if (!ch) return;
  const msg = ch.messages.find(m => m.id === msgId);
  if (!msg || !msg.own || msg.isAi) return;
  ch.messages = ch.messages.filter(m => m.id !== msgId);
  renderMessages(ChatState.activeChannel);
};

// ═══════════════════════════════════════════════════════════
// STATE GLOBAL
// ═══════════════════════════════════════════════════════════
const ChatState = {
  activeChannel: 'global',
  channels: {},          // { channelId: { messages: [], unread: 0, ... } }
  typingTimer: null,
  isAiThinking: false,
};
// Exposer ChatState globalement pour que openChatModal (challenges.html) puisse y accéder
window.ChatState = ChatState;

// ═══════════════════════════════════════════════════════════
// DONNÉES INITIALES — Canaux par défaut
// ═══════════════════════════════════════════════════════════
function buildDefaultChannels() {
  const channels = [
    { id: 'global',    icon: '🌍', name: 'Général',        desc: 'Discussion générale',    section: 'DÉFIS' },
    { id: 'ecology',   icon: '♻️', name: 'Écologie',       desc: 'Défis environnement',    section: 'DÉFIS' },
    { id: 'sport',     icon: '🏃', name: 'Sport & Santé',  desc: 'Défis sportifs',         section: 'DÉFIS' },
    { id: 'nutrition', icon: '🥗', name: 'Nutrition',      desc: 'Défis alimentation',     section: 'DÉFIS' },
    { id: 'tips',      icon: '💡', name: 'Astuces',        desc: 'Conseils & tips',        section: 'COMMUNAUTÉ' },
    { id: 'rewards',   icon: '🏆', name: 'Récompenses',    desc: 'Steakers & podium',      section: 'COMMUNAUTÉ' },
  ];

  channels.forEach(ch => {
    ChatState.channels[ch.id] = {
      ...ch,
      messages: [],
      unread: 0,
      onlineCount: Math.floor(Math.random() * 18) + 2,
    };
  });

  // Injecter quelques messages initiaux de démo
  injectDemoMessages();
}

function injectDemoMessages() {
  const demos = {
    global: [
      { author: 'Marie Dupont', avatar: '👩', text: 'Salut tout le monde ! Quelqu\'un a déjà complété le défi Zéro Déchet ?', time: -28 },
      { author: 'Jean Martin', avatar: '👨', text: 'Oui ! J\'en suis à 85% 🎉 C\'est vraiment motivant de voir sa progression.', time: -22 },
      { author: 'Sophie B.', avatar: '🧑', text: 'Bravo Jean ! Moi j\'essaie le défi Sport 30j, quelqu\'un veut faire équipe ?', time: -15 },
    ],
    ecology: [
      { author: 'Emma Petit', avatar: '👩', text: 'Tip du jour : commpostez vos épluchures, ça réduit 30% des déchets ménagers 🌱', time: -45 },
      { author: 'Lucas M.', avatar: '👨', text: 'Super astuce ! J\'utilise aussi des sacs réutilisables depuis 3 mois maintenant.', time: -30 },
    ],
    sport: [
      { author: 'Thomas Simon', avatar: '👨', text: 'Jour 12 du défi running ! 5km ce matin malgré la pluie 💪', time: -60 },
      { author: 'Chloé L.', avatar: '👩', text: 'Incroyable Thomas ! Moi j\'ai mal aux jambes mais je continue 😅', time: -40 },
    ],
  };

  const now = Date.now();
  Object.entries(demos).forEach(([chId, msgs]) => {
    if (!ChatState.channels[chId]) return;
    msgs.forEach(m => {
      ChatState.channels[chId].messages.push({
        id: generateId(),
        author: m.author,
        avatar: m.avatar,
        text: m.text,
        time: now + m.time * 60000,
        own: false,
        isAi: false,
        reactions: [],
      });
    });
  });
}

// ═══════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════
function generateId() {
  return Math.random().toString(36).slice(2, 10);
}

function formatTime(ts) {
  const d = new Date(ts);
  return d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function formatDateSep(ts) {
  const d = new Date(ts);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);
  if (d.toDateString() === today.toDateString()) return 'Aujourd\'hui';
  if (d.toDateString() === yesterday.toDateString()) return 'Hier';
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
}

function escapeHtml(str) {
  return (str ?? '')
    .toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/\n/g, '<br>');
}

function normalizeStr(val) {
  return (val ?? '').toString().trim().toLowerCase();
}

function renderCardsBlock(cards) {
  const list = Array.isArray(cards) ? cards : [];
  if (list.length === 0) return '';

  return `
    <div class="gl-msg-cards">
      ${list.map(c => `
        <div class="gl-chat-card" data-challenge-id="${parseInt(c.id || 0, 10)}">
          <div class="gl-chat-card__top">
            <div class="gl-chat-card__title">${escapeHtml(c.titre || 'Défi')}</div>
            <div class="gl-chat-card__badge">${escapeHtml(c.badge || '✅ Actif')}</div>
          </div>
          <div class="gl-chat-card__desc">${escapeHtml(c.description || '')}</div>
          <div class="gl-chat-card__meta">
            <span>👥 ${parseInt(c.participants_count || 0, 10)}</span>
            <span>📈 ${parseInt(c.progression || 0, 10)}%</span>
            ${c.daysLeft ? `<span>⏳ ${escapeHtml(c.daysLeft)}</span>` : ''}
          </div>
          <div class="gl-chat-card__actions">
            <button class="gl-chat-card__btn gl-chat-card__btn--ghost" type="button" onclick="openChallengeFromChat(${parseInt(c.id || 0, 10)})">Détails</button>
            <button class="gl-chat-card__btn gl-chat-card__btn--primary" type="button" onclick="openChallengeFromChat(${parseInt(c.id || 0, 10)})">Participer</button>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

function scrollToBottom(container, smooth = true) {
  if (!container) return;
  setTimeout(() => {
    container.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
  }, ChatState.scrollDelay);
}

// ═══════════════════════════════════════════════════════════
// RENDU — Liste des canaux
// ═══════════════════════════════════════════════════════════
function renderChannelList() {
  const list = document.getElementById('chat-channel-list');
  if (!list) return;

  let currentSection = '';
  let html = '';

  Object.values(ChatState.channels).forEach(ch => {
    if (ch.section !== currentSection) {
      currentSection = ch.section;
      html += `<div class="gl-channels-sep">${currentSection}</div>`;
    }

    const isActive = ch.id === ChatState.activeChannel;
    const unread = ch.unread > 0
      ? `<span class="gl-channel-badge ${isActive ? '' : 'new'}">${ch.unread}</span>`
      : '';

    html += `
      <div class="gl-chat-channel-item ${isActive ? 'active' : ''}" data-channel="${ch.id}">
        <span class="gl-channel-icon">${ch.icon}</span>
        <div class="gl-channel-info">
          <div class="gl-channel-name">${ch.name}</div>
          <div class="gl-channel-meta">${ch.onlineCount} en ligne</div>
        </div>
        ${unread}
      </div>
    `;
  });

  list.innerHTML = html;

  // Listeners
  list.querySelectorAll('.gl-chat-channel-item').forEach(item => {
    item.addEventListener('click', () => {
      switchChannel(item.dataset.channel);
    });
  });
}

// ═══════════════════════════════════════════════════════════
// RENDU — Messages d'un canal
// ═══════════════════════════════════════════════════════════
function renderMessages(channelId) {
  const container = document.getElementById('chat-messages-area');
  if (!container) return;

  const ch = ChatState.channels[channelId];
  if (!ch || ch.messages.length === 0) {
    container.innerHTML = `
      <div class="gl-chat-empty">
        <div class="gl-chat-empty__icon">${ch ? ch.icon : '💬'}</div>
        <div class="gl-chat-empty__title">Démarrez la conversation !</div>
        <div class="gl-chat-empty__desc">Soyez le premier à écrire dans ce canal ou demandez un conseil à l'IA.</div>
      </div>
    `;
    return;
  }

  let html = `
    <div class="gl-chat-ai-welcome">
      <span style="font-size:1.2rem">🌿</span>
      <div class="gl-chat-ai-welcome__text">
        <strong>GaiaLumen IA</strong> est disponible pour vous aider — posez vos questions sur les défis en cliquant sur <strong>✨ IA</strong>.
      </div>
    </div>
  `;

  let lastDateStr = '';
  let lastAuthor = '';
  let groupOpen = false;

  ch.messages.forEach((msg, i) => {
    const dateStr = formatDateSep(msg.time);
    if (dateStr !== lastDateStr) {
      if (groupOpen) { html += '</div>'; groupOpen = false; }
      html += `<div class="gl-chat-date-sep"><span>${dateStr}</span></div>`;
      lastDateStr = dateStr;
      lastAuthor = '';
    }

    const isNewGroup = msg.author !== lastAuthor || msg.isAi !== (i > 0 ? ch.messages[i - 1] : null)?.isAi;

    if (isNewGroup) {
      if (groupOpen) html += '</div>';
      html += `<div class="gl-msg-group">`;
      groupOpen = true;
    }

    const ownClass = msg.own ? 'gl-msg--own' : '';
    const avatarClass = msg.isAi ? 'gl-msg-avatar--ai' : '';
    const authorClass = msg.isAi ? 'gl-msg-author--ai' : '';
    const bubbleClass = msg.isAi ? 'gl-msg-bubble--ai' : '';

    const reactions = msg.reactions && msg.reactions.length > 0
      ? `<div class="gl-msg-reactions">${msg.reactions.map(r =>
          `<button class="gl-msg-reaction ${r.active ? 'active' : ''}" onclick="toggleReaction('${msg.id}', '${r.emoji}')">
            ${r.emoji} <span>${r.count}</span>
          </button>`
        ).join('')}</div>`
      : '';

    const hasCards = msg.type === 'cards' && Array.isArray(msg.cards) && msg.cards.length > 0;
    let innerBubble = '';
    if (msg.image && isAllowedChatImageUrl(msg.image)) {
      innerBubble += `<div class="gl-msg-media"><a href="${escapeHtml(msg.image)}" target="_blank" rel="noopener noreferrer"><img class="gl-msg-img" src="${escapeHtml(msg.image)}" alt="" loading="lazy" /></a></div>`;
    }
    if (msg.text) {
      innerBubble += `<div class="gl-msg-text">${escapeHtml(msg.text)}</div>`;
    }
    const bubble = innerBubble !== ''
      ? `<div class="gl-msg-bubble ${bubbleClass}">${innerBubble}</div>`
      : '';
    const cardsBlock = hasCards ? renderCardsBlock(msg.cards) : '';
    const ownDel = (msg.own && !msg.isAi)
      ? `<div class="gl-msg-own-actions"><button type="button" class="gl-msg-del" onclick="deleteInlineChatMessage('${msg.id}')" title="Supprimer">✕</button></div>`
      : '';

    html += `
      <div class="gl-msg ${ownClass}" data-msg-id="${msg.id}">
        ${isNewGroup ? `<div class="gl-msg-avatar ${avatarClass}">${msg.avatar}</div>` : '<div style="width:34px;flex-shrink:0"></div>'}
        <div class="gl-msg-content">
          ${isNewGroup ? `
            <div class="gl-msg-meta">
              <span class="gl-msg-author ${authorClass}">${escapeHtml(msg.author)}</span>
              <span class="gl-msg-time">${formatTime(msg.time)}</span>
            </div>
          ` : ''}
          ${bubble}
          ${cardsBlock}
          ${ownDel}
          ${reactions}
        </div>
      </div>
    `;

    lastAuthor = msg.author;
  });

  if (groupOpen) html += '</div>';

  container.innerHTML = html;
  scrollToBottom(container, false);

  // Update message count stat
  const totalMsgs = Object.values(ChatState.channels).reduce((acc, c) => acc + c.messages.length, 0);
  const statEl = document.getElementById('chat-stat-msgs');
  if (statEl) statEl.textContent = totalMsgs;
}

// ═══════════════════════════════════════════════════════════
// RENDU — Topbar du canal actif
// ═══════════════════════════════════════════════════════════
function renderTopbar(channelId) {
  const ch = ChatState.channels[channelId];
  if (!ch) return;

  const icon = document.getElementById('chat-active-icon');
  const name = document.getElementById('chat-active-name');
  const desc = document.getElementById('chat-active-desc');
  const online = document.getElementById('chat-online-count');

  if (icon) icon.textContent = ch.icon;
  if (name) name.textContent = ch.name;
  if (desc) desc.textContent = ch.desc;
  if (online) online.textContent = `${ch.onlineCount} en ligne`;

  const input = document.getElementById('chat-msg-input');
  if (input) input.placeholder = `Message dans ${ch.name}…`;
}

// ═══════════════════════════════════════════════════════════
// CHANGEMENT DE CANAL
// ═══════════════════════════════════════════════════════════
function switchChannel(channelId) {
  if (!ChatState.channels[channelId]) return;
  ChatState.activeChannel = channelId;

  // Reset unread
  ChatState.channels[channelId].unread = 0;

  renderChannelList();
  renderTopbar(channelId);
  renderMessages(channelId);
  clearTypingIndicator();

  if (channelId.startsWith('defi_') && typeof window.fetchParticipantsForChallenge === 'function') {
    const cid = parseInt(channelId.replace('defi_', ''), 10);
    if (cid) {
      window.fetchParticipantsForChallenge(cid).then((list) => {
        const c = ChatState.channels[channelId];
        if (!c) return;
        c.participantsList = list || [];
        const n = Math.max(
          (list || []).length,
          parseInt(c.participants_count || 0, 10),
          1,
        );
        c.onlineCount = n;
        if (ChatState.activeChannel === channelId) {
          renderTopbar(channelId);
        }
      });
    }
  }
}

// ═══════════════════════════════════════════════════════════
// ENVOI D'UN MESSAGE
// ═══════════════════════════════════════════════════════════
function sendMessage() {
  const input = document.getElementById('chat-msg-input');
  if (!input) return;

  const text = input.value.trim();
  const img = __inlineChatPendingImage;
  if (!text && !img) return;

  const ch = ChatState.channels[ChatState.activeChannel];
  if (!ch) return;

  const who = pickInlineAuthorFromChannel(ch);
  const msg = {
    id: generateId(),
    author: who.nom,
    avatar: who.avatar,
    text,
    image: img || '',
    participantId: who.participantId,
    userId: who.userId,
    time: Date.now(),
    own: true,
    isAi: false,
    reactions: [],
  };

  ch.messages.push(msg);

  // Limiter le nombre de messages
  if (ch.messages.length > CHAT_CONFIG.maxMessages) {
    ch.messages.shift();
  }

  input.value = '';
  input.style.height = '';
  clearInlineChatPendingImage();

  renderMessages(ChatState.activeChannel);

  // Simuler réponse d'un autre participant (25% de chance)
  if (Math.random() < 0.25) {
    simulatePeerTyping();
  }
}

// ═══════════════════════════════════════════════════════════
// SIMULATION — Réponse d'un pair
// ═══════════════════════════════════════════════════════════
const peerResponses = [
  ['Marie D.', '👩', 'Super initiative ! Je vais essayer ça aussi 🌿'],
  ['Jean M.', '👨', 'Bonne idée ! On peut faire équipe si tu veux 💪'],
  ['Sophie B.', '🧑', 'J\'en suis à 67% pour ce défi, courage à tous !'],
  ['Emma P.', '👩', '✅ Validé ! Continuez comme ça'],
  ['Lucas M.', '👨', 'Quelqu\'un a une astuce pour gagner plus de points rapidement ?'],
  ['Thomas S.', '🧑', 'Bravo ! C\'est exactement ce genre de motivation dont on a besoin 🔥'],
];

function simulatePeerTyping() {
  const indicator = document.getElementById('chat-typing-indicator');
  const typingText = document.getElementById('chat-typing-text');
  let peer = peerResponses[Math.floor(Math.random() * peerResponses.length)];
  const ch = ChatState.channels[ChatState.activeChannel];
  const plist = ch?.participantsList || [];
  if (plist.length > 0) {
    const pick = plist[Math.floor(Math.random() * plist.length)];
    const replies = [
      'Super initiative ! On continue ensemble 🌿',
      'Bonne idée ! Ça motive toute l’équipe 💪',
      'Merci pour le partage 👏',
      'Bravo pour l’avancement sur ce défi ! 🔥',
    ];
    peer = [pick.nom || peer[0], '👤', replies[Math.floor(Math.random() * replies.length)]];
  }

  if (indicator) indicator.style.display = 'flex';
  if (typingText) typingText.textContent = `${peer[0]} est en train d'écrire…`;

  setTimeout(() => {
    clearTypingIndicator();
    addPeerMessage(peer[0], peer[1], peer[2]);
  }, 1800 + Math.random() * 1400);
}

function addPeerMessage(author, avatar, text) {
  const ch = ChatState.channels[ChatState.activeChannel];
  if (!ch) return;

  ch.messages.push({
    id: generateId(),
    author,
    avatar,
    text,
    time: Date.now(),
    own: false,
    isAi: false,
    reactions: [],
  });

  // Incrémenter badge si autre canal
  renderMessages(ChatState.activeChannel);
}

function clearTypingIndicator() {
  const indicator = document.getElementById('chat-typing-indicator');
  if (indicator) indicator.style.display = 'none';
}

// ═══════════════════════════════════════════════════════════
// IA CLAUDE — Demande de conseil
// ═══════════════════════════════════════════════════════════
async function askAI() {
  if (ChatState.isAiThinking) return;

  const input = document.getElementById('chat-msg-input');
  const aiBtn = document.getElementById('chat-ai-btn');
  const ch = ChatState.channels[ChatState.activeChannel];
  if (!ch) return;

  // Récupérer le texte de l'input ou générer une question contextuelle
  const userQuestion = input ? input.value.trim() : '';
  const contextPrompt = userQuestion
    ? userQuestion
    : `Donne-moi un conseil motivant et concret pour progresser dans les défis du canal "${ch.name}" sur la plateforme GaiaLumen.`;

  // Afficher le message utilisateur si non vide
  if (userQuestion && input) {
    sendMessage();
  }

  ChatState.isAiThinking = true;
  if (aiBtn) aiBtn.classList.add('loading');

  // Indicateur typing IA
  const indicator = document.getElementById('chat-typing-indicator');
  const typingText = document.getElementById('chat-typing-text');
  if (indicator) indicator.style.display = 'flex';
  if (typingText) typingText.textContent = 'GaiaLumen IA réfléchit…';

  try {
    const systemPrompt = `Tu es GaiaLumen IA, l'assistant bienveillant de la plateforme GaiaLumen dédiée aux défis collaboratifs écologiques et sportifs.
Tu aides les participants à :
- Progresser dans leurs défis (écologie, sport, nutrition)
- Trouver de la motivation et des astuces pratiques
- Comprendre comment gagner des steakers (badges 3D)
- Améliorer leur classement

Contexte canal actif : "${ch.name}" — ${ch.desc}

Réponds en français, de façon chaleureuse, concise (3-4 phrases max), avec des emojis pertinents.
Inclus toujours une astuce actionnable ou un encouragement spécifique.`;

    const response = await fetch(getBackendApiPath('anthropic-messages.php'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'llama-3.3-70b-versatile',
        max_tokens: 1000,
        system: systemPrompt,
        messages: [{ role: 'user', content: contextPrompt }],
      }),
    });

    const data = await response.json();
    const aiText = data.content?.[0]?.text || 'Je suis là pour vous aider ! Posez-moi une question sur vos défis. 🌿';

    clearTypingIndicator();

    ch.messages.push({
      id: generateId(),
      author: CHAT_CONFIG.aiName,
      avatar: CHAT_CONFIG.aiAvatar,
      text: aiText,
      time: Date.now(),
      own: false,
      isAi: true,
      reactions: [
        { emoji: '👍', count: 0, active: false },
        { emoji: '🌿', count: 0, active: false },
      ],
    });

    const suggestions = getSuggestedChallenges(ChatState.activeChannel);
    if (suggestions.length) {
      ch.messages.push({
        id: generateId(),
        author: CHAT_CONFIG.aiName,
        avatar: CHAT_CONFIG.aiAvatar,
        text: 'Défis recommandés :',
        time: Date.now() + 1,
        own: false,
        isAi: true,
        type: 'cards',
        cards: suggestions,
        reactions: [],
      });
    }

    renderMessages(ChatState.activeChannel);

  } catch (err) {
    console.error('Chat IA error:', err);
    clearTypingIndicator();
    ch.messages.push({
      id: generateId(),
      author: CHAT_CONFIG.aiName,
      avatar: CHAT_CONFIG.aiAvatar,
      text: 'Désolé, je suis temporairement indisponible. Continuez vos défis, vous êtes sur la bonne voie ! 💪🌿',
      time: Date.now(),
      own: false,
      isAi: true,
      reactions: [],
    });
    renderMessages(ChatState.activeChannel);
  } finally {
    ChatState.isAiThinking = false;
    if (aiBtn) aiBtn.classList.remove('loading');
  }
}

function getSuggestedChallenges(channelId) {
  const challenges = window.allChallenges;
  if (!Array.isArray(challenges) || challenges.length === 0) return [];

  const wanted = (() => {
    const id = normalizeStr(channelId);
    if (id === 'ecology') return 'écologie';
    if (id === 'sport') return 'sport';
    if (id === 'nutrition') return 'nutrition';
    return '';
  })();

  const active = challenges.filter(c => normalizeStr(c.statut) === 'actif');
  const scoped = wanted
    ? active.filter(c => normalizeStr(c.objectif) === normalizeStr(wanted))
    : active;

  const pick = (scoped.length ? scoped : active).slice(0, 3);
  return pick.map(c => {
    const dateFin = new Date(c.date_fin);
    const hasValidDate = !Number.isNaN(dateFin.getTime());
    const days = hasValidDate ? Math.ceil((dateFin - new Date()) / 86400000) : null;
    const daysLeft = days === null ? '' : (days < 0 ? 'Terminé' : `${Math.max(0, days)}j`);
    return {
      id: c.id,
      titre: c.titre,
      description: c.description,
      participants_count: c.participants_count,
      progression: c.progression,
      daysLeft,
      badge: normalizeStr(c.statut) === 'actif' ? '✅ Actif' : '🔜',
    };
  });
}

window.openChallengeFromChat = function (challengeId) {
  const id = parseInt(challengeId || 0, 10);
  if (!id) return;
  if (typeof window.showChallengeDetail === 'function') {
    window.showChallengeDetail(id);
  }
};

// ═══════════════════════════════════════════════════════════
// RÉACTIONS
// ═══════════════════════════════════════════════════════════
window.toggleReaction = function(msgId, emoji) {
  const ch = ChatState.channels[ChatState.activeChannel];
  if (!ch) return;
  const msg = ch.messages.find(m => m.id === msgId);
  if (!msg) return;

  const reaction = msg.reactions.find(r => r.emoji === emoji);
  if (reaction) {
    reaction.active = !reaction.active;
    reaction.count += reaction.active ? 1 : -1;
    if (reaction.count < 0) reaction.count = 0;
  }

  renderMessages(ChatState.activeChannel);
};

// ═══════════════════════════════════════════════════════════
// SYNCHRONISATION avec les défis chargés
// ═══════════════════════════════════════════════════════════
function syncWithChallenges(challenges) {
  if (!Array.isArray(challenges)) return;

  challenges.forEach(ch => {
    const id = `defi_${ch.id}`;
    const count = Math.max(0, parseInt(ch.participants_count || 0, 10));
    const base = {
      id,
      challengeNumericId: ch.id,
      icon: ch.steaker || ch.streak_icon || '🏆',
      name: ch.titre ? ch.titre.substring(0, 28) : 'Défi',
      desc: ch.description
        ? (ch.description.length > 72 ? ch.description.substring(0, 72) + '…' : ch.description)
        : '',
      section: 'DÉFIS',
      participants_count: count,
      participantsList: ChatState.channels[id]?.participantsList || [],
      messages: ChatState.channels[id]?.messages || [],
      unread: ChatState.channels[id]?.unread || 0,
      onlineCount: Math.max(count, 1),
    };

    if (!ChatState.channels[id]) {
      ChatState.channels[id] = { ...base };
    } else {
      const keep = ChatState.channels[id];
      keep.icon = base.icon;
      keep.name = base.name;
      keep.desc = base.desc;
      keep.section = base.section;
      keep.challengeNumericId = base.challengeNumericId;
      keep.participants_count = base.participants_count;
      keep.onlineCount = base.onlineCount;
    }
  });

  const list = document.getElementById('chat-channel-list');
  if (list) renderChannelList();
}

window.syncChatWithChallenges = syncWithChallenges;

function ensureChatChannelsReady() {
  if (window.__chatChannelsReady) return;
  window.__chatChannelsReady = true;
  buildDefaultChannels();
  if (window.allChallenges && window.allChallenges.length > 0) {
    syncWithChallenges(window.allChallenges);
  }
  const _origLoad = window.loadChallenges;
  if (typeof _origLoad === 'function' && !window.__chatLoadChallengesPatched) {
    window.__chatLoadChallengesPatched = true;
    window.loadChallenges = function () {
      const r = _origLoad.apply(this, arguments);
      setTimeout(() => {
        if (window.allChallenges) syncWithChallenges(window.allChallenges);
      }, 1200);
      return r;
    };
  }
}

window.ensureChatChannelsReady = ensureChatChannelsReady;

// ═══════════════════════════════════════════════════════════
// INITIALISATION DU MODULE CHAT
// ═══════════════════════════════════════════════════════════
function initChat() {
  ensureChatChannelsReady();
  void refreshChatSessionUser();

  const chatRoot = document.getElementById('gl-chat-root');
  if (!chatRoot || chatRoot.dataset.chatInit) return;
  chatRoot.dataset.chatInit = '1';

  renderChannelList();
  renderTopbar(ChatState.activeChannel);
  renderMessages(ChatState.activeChannel);

  // Envoi message — bouton
  const sendBtn = document.getElementById('chat-send-btn');
  if (sendBtn) {
    sendBtn.addEventListener('click', sendMessage);
  }

  // Envoi message — Entrée (sans Shift)
  const input = document.getElementById('chat-msg-input');
  if (input) {
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    // Auto-resize textarea
    input.addEventListener('input', () => {
      input.style.height = '';
      input.style.height = Math.min(input.scrollHeight, 90) + 'px';
      if (sendBtn) {
        sendBtn.disabled = input.value.trim() === '' && !__inlineChatPendingImage;
      }
    });

    input.addEventListener('paste', (e) => {
      const items = e.clipboardData?.items || [];
      for (let i = 0; i < items.length; i++) {
        if (items[i].type && items[i].type.indexOf('image') === 0) {
          e.preventDefault();
          const f = items[i].getAsFile();
          if (f) {
            uploadInlineChatImage(f).then((url) => setInlineChatPendingImageUrl(url)).catch(() => {});
          }
          break;
        }
      }
    });
  }

  const attachBtn = document.getElementById('chat-attach-btn');
  const fileInp = document.getElementById('chat-file-input');
  const clrImg = document.getElementById('chat-image-clear');
  if (attachBtn && fileInp) {
    attachBtn.addEventListener('click', () => fileInp.click());
    fileInp.addEventListener('change', () => {
      const f = fileInp.files?.[0];
      if (!f) return;
      uploadInlineChatImage(f)
        .then((url) => setInlineChatPendingImageUrl(url))
        .catch(() => {})
        .finally(() => { fileInp.value = ''; });
    });
  }
  if (clrImg) {
    clrImg.addEventListener('click', clearInlineChatPendingImage);
  }

  // Bouton IA
  const aiBtn = document.getElementById('chat-ai-btn');
  if (aiBtn) {
    aiBtn.addEventListener('click', askAI);
  }

  // Emojis rapides
  document.querySelectorAll('.gl-emoji-quick button').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!input) return;
      input.value += btn.textContent.trim();
      input.focus();
      input.dispatchEvent(new Event('input'));
    });
  });

  // Bouton menu mobile
  const mobileToggle = document.getElementById('chat-mobile-channels');
  const channelsSidebar = document.getElementById('chat-channels-sidebar');
  if (mobileToggle && channelsSidebar) {
    mobileToggle.addEventListener('click', () => {
      channelsSidebar.classList.toggle('mobile-open');
    });
  }

  console.log('💬 Chat GaiaLumen initialisé — canaux :', Object.keys(ChatState.channels).length);
}

// ─── Auto-init ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', initChat);
document.addEventListener('moduleLoaded', (e) => {
  if (e.detail && e.detail.moduleName === 'challenges') {
    setTimeout(initChat, 200);
  }
});
if (document.readyState !== 'loading') {
  setTimeout(initChat, 150);
}
})();
