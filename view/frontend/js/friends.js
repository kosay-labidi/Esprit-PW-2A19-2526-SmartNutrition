const API_BASE = '../backend/users/friends_api.php';
let currentUser = null;
let activeFriendId = null;
let lastUnreadCount = 0;

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function flash(message) {
  const box = document.getElementById('flash-msg');
  if (!box) return;
  box.textContent = message;
  box.classList.add('show');
  setTimeout(() => box.classList.remove('show'), 2200);
}

function setPanelMessage(id, message) {
  const container = document.getElementById(id);
  if (container) {
    container.innerHTML = `<div class="empty-state">${escapeHtml(message)}</div>`;
  }
}

function getStoredUserId() {
  return sessionStorage.getItem('user_id') || localStorage.getItem('user_id') || '';
}

async function readJsonResponse(res) {
  const text = await res.text();
  try {
    return JSON.parse(text);
  } catch (error) {
    throw new Error(text.trim() || `Réponse serveur invalide (${res.status})`);
  }
}

function avatarNode(user) {
  const photo = user.avatar_url || '';
  if (photo) {
    const img = document.createElement('img');
    img.className = 'avatar';
    img.src = photo;
    img.alt = 'avatar';
    img.onerror = () => {
      img.replaceWith(initialNode(user));
    };
    return img;
  }
  return initialNode(user);
}

function initialNode(user) {
  const span = document.createElement('span');
  span.className = 'fallback-avatar';
  const p = (user.prenom || '').charAt(0).toUpperCase();
  const n = (user.nom || '').charAt(0).toUpperCase();
  span.textContent = (p + n) || '?';
  return span;
}

async function api(action, method = 'GET', body = null) {
  const url = new URL(API_BASE, window.location.href);
  url.searchParams.set('action', action);
  if (method === 'GET' && body && typeof body === 'object') {
    Object.entries(body).forEach(([key, value]) => {
      url.searchParams.set(key, String(value));
    });
  }
  const options = {
    method,
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' }
  };
  const storedUserId = getStoredUserId();
  if (storedUserId) {
    options.headers['X-User-Id'] = storedUserId;
  }
  if (method !== 'GET' && body) {
    options.body = JSON.stringify(body);
  }
  const res = await fetch(url, options);
  const data = await readJsonResponse(res);
  if (!res.ok) {
    throw new Error(data.message || `Erreur HTTP ${res.status}`);
  }
  return data;
}

async function uploadAttachment(friendId, file) {
  const formData = new FormData();
  formData.append('friend_id', String(friendId));
  formData.append('attachment', file);
  const res = await fetch(`${API_BASE}?action=send_attachment`, {
    method: 'POST',
    credentials: 'include',
    headers: getStoredUserId() ? { 'X-User-Id': getStoredUserId() } : {},
    body: formData
  });
  return readJsonResponse(res);
}

async function loadSessionUser() {
  const res = await fetch(
    '../backend/users/auto_login.php',
    { method: 'GET', credentials: 'include' }
  );
  const data = await readJsonResponse(res);
  if (!data.success || !data.data) {
    const storedUserId = getStoredUserId();
    if (storedUserId) {
      currentUser = {
        id_utilisateur: storedUserId,
        nom: sessionStorage.getItem('user_nom') || '',
        prenom: sessionStorage.getItem('user_prenom') || '',
        email: sessionStorage.getItem('user_email') || ''
      };
      return;
    }
    throw new Error(data.message || 'Session expirée. Connectez-vous puis revenez au chat.');
  }
  currentUser = data.data;
  currentUser.id_utilisateur = currentUser.id_utilisateur || currentUser.id;
}

function renderUsers(users) {
  const container = document.getElementById('users-list');
  container.innerHTML = '';
  if (!users.length) {
    container.innerHTML = '<div class="empty-state">Aucun utilisateur disponible</div>';
    return;
  }

  users.forEach((user) => {
    const card = document.createElement('div');
    card.className = 'user-card';
    const left = document.createElement('div');
    left.className = 'user-info';
    left.appendChild(avatarNode(user));
    const meta = document.createElement('div');
    meta.className = 'user-meta';
    const name = document.createElement('span');
    name.className = 'user-name';
    name.textContent = `${user.prenom} ${user.nom}`;
    const sub = document.createElement('span');
    sub.className = 'user-sub';
    sub.textContent = 'Utilisateur';
    meta.appendChild(name);
    meta.appendChild(sub);
    left.appendChild(meta);

    const btn = document.createElement('button');
    if (user.is_friend) {
      btn.textContent = 'Déjà ami';
      btn.disabled = true;
    } else {
      btn.textContent = 'Ajouter';
      btn.onclick = async () => {
        const result = await api('add_friend', 'POST', { friend_id: user.id_utilisateur });
        if (!result.success) {
          flash(result.message || 'Impossible d\'ajouter cet ami');
          return;
        }
        flash('Ami ajouté');
        await refreshData();
      };
    }

    card.appendChild(left);
    card.appendChild(btn);
    container.appendChild(card);
  });
}

function renderFriends(friends) {
  const container = document.getElementById('friends-list');
  container.innerHTML = '';
  if (!friends.length) {
    container.innerHTML = '<div class="empty-state">Vous n\'avez pas encore d\'amis</div>';
    return;
  }

  friends.forEach((friend) => {
    const card = document.createElement('div');
    card.className = 'user-card';

    const left = document.createElement('div');
    left.className = 'user-info';
    left.appendChild(avatarNode(friend));
    const meta = document.createElement('div');
    meta.className = 'user-meta';
    const name = document.createElement('span');
    name.className = 'user-name';
    name.textContent = `${friend.prenom} ${friend.nom}`;
    const sub = document.createElement('span');
    sub.className = 'user-sub';
    sub.textContent = 'Ami';
    meta.appendChild(name);
    meta.appendChild(sub);
    left.appendChild(meta);
    card.appendChild(left);

    const actions = document.createElement('div');
    actions.className = 'friend-actions';
    const msgBtn = document.createElement('button');
    msgBtn.textContent = 'Message';
    msgBtn.onclick = async () => {
      activeFriendId = friend.id_utilisateur;
      document.getElementById('chat-title').textContent = `Messages avec ${friend.prenom} ${friend.nom}`;
      await loadMessages();
    };

    const delBtn = document.createElement('button');
    delBtn.textContent = 'Supprimer';
    delBtn.className = 'danger';
    delBtn.onclick = async () => {
      await api('remove_friend', 'POST', { friend_id: friend.id_utilisateur });
      if (activeFriendId === friend.id_utilisateur) {
        activeFriendId = null;
        document.getElementById('chat-title').textContent = 'Messages';
        document.getElementById('chat-box').innerHTML = '';
      }
      flash('Ami supprimé');
      await refreshData();
    };

    actions.appendChild(msgBtn);
    actions.appendChild(delBtn);
    card.appendChild(actions);
    container.appendChild(card);
  });
}

async function loadMessages() {
  if (!activeFriendId) return;
  const data = await api('messages', 'GET', { friend_id: activeFriendId });
  const box = document.getElementById('chat-box');
  box.innerHTML = '';

  if (!data.success) return;
  if (!data.messages.length) {
    box.innerHTML = '<div class="empty-state">Aucun message pour le moment</div>';
    await refreshNotifications();
    return;
  }
  data.messages.forEach((msg) => {
    const line = document.createElement('div');
    line.className = 'chat-message' + (Number(msg.sender_id) === Number(currentUser.id_utilisateur) ? ' me' : '');
    const author = Number(msg.sender_id) === Number(currentUser.id_utilisateur) ? 'Moi' : 'Ami';
    const when = new Date(msg.created_at.replace(' ', 'T'));
    let content = msg.message || '';
    if (msg.type === 'image' && msg.file_url) {
      content = `<span class="chat-meta">${escapeHtml(author)} • ${escapeHtml(when.toLocaleString('fr-FR'))}</span><img class="chat-media" src="${escapeHtml(msg.file_url)}" alt="image envoyee" />`;
    } else if (msg.type === 'video' && msg.file_url) {
      content = `<span class="chat-meta">${escapeHtml(author)} • ${escapeHtml(when.toLocaleString('fr-FR'))}</span><video class="chat-media" controls src="${escapeHtml(msg.file_url)}"></video>`;
    } else if (msg.type === 'link') {
      const safe = escapeHtml(msg.message || '');
      content = `<span class="chat-meta">${escapeHtml(author)} • ${escapeHtml(when.toLocaleString('fr-FR'))}</span><a class="chat-link" href="${safe}" target="_blank" rel="noopener noreferrer">${safe}</a>`;
    } else {
      content = `<span class="chat-meta">${escapeHtml(author)} • ${escapeHtml(when.toLocaleString('fr-FR'))}</span>${escapeHtml(content)}`;
    }
    line.innerHTML = content;
    box.appendChild(line);
  });

  box.scrollTop = box.scrollHeight;
  await refreshNotifications();
}

function renderNotifications(items) {
  const dropdown = document.getElementById('notif-dropdown');
  const count = document.getElementById('notif-count');
  const unread = items.reduce((sum, item) => sum + Number(item.unread_count || 0), 0);
  count.textContent = String(unread);
  count.classList.toggle('hidden', unread === 0);

  dropdown.innerHTML = '';
  if (!items.length) {
    dropdown.innerHTML = '<div class="notif-item">Aucune nouvelle notification</div>';
    return;
  }

  items.forEach((item) => {
    const row = document.createElement('div');
    row.className = 'notif-item';
    const preview = item.last_type === 'image'
      ? 'a envoye une image'
      : item.last_type === 'video'
        ? 'a envoye une video'
        : item.last_type === 'link'
          ? 'a envoye un lien'
          : 'a envoye un message';
    row.innerHTML = `<strong>${escapeHtml(item.friend_name)}</strong><br/>${escapeHtml(preview)}`;
    row.onclick = async () => {
      activeFriendId = Number(item.friend_id);
      document.getElementById('chat-title').textContent = `Messages avec ${item.friend_name}`;
      dropdown.classList.remove('open');
      await loadMessages();
    };
    dropdown.appendChild(row);
  });
}

async function refreshNotifications() {
  const data = await api('notifications');
  if (!data.success) return;
  renderNotifications(data.notifications || []);
  if (typeof data.unread_count === 'number' && data.unread_count > lastUnreadCount) {
    const first = data.notifications?.[0];
    if (first?.friend_name) {
      flash(`🔔 ${first.friend_name} a envoye un message`);
    } else {
      flash('🔔 Nouveau message reçu');
    }
  }
  lastUnreadCount = Number(data.unread_count || 0);
}

async function refreshData() {
  setPanelMessage('users-list', 'Chargement des utilisateurs...');
  setPanelMessage('friends-list', 'Chargement des amis...');
  try {
    const usersData = await api('users');
    const friendsData = await api('friends');
    if (usersData.success) renderUsers(usersData.users || []);
    else setPanelMessage('users-list', usersData.message || 'Impossible de charger les utilisateurs');
    if (friendsData.success) renderFriends(friendsData.friends || []);
    else setPanelMessage('friends-list', friendsData.message || 'Impossible de charger les amis');
    await refreshNotifications();
  } catch (error) {
    console.error('refreshData erreur:', error);
    setPanelMessage('users-list', error.message);
    setPanelMessage('friends-list', error.message);
  }
}

document.getElementById('message-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const input = document.getElementById('message-input');
  const text = input.value.trim();
  if (!text || !activeFriendId) return;
  await api('send_message', 'POST', { friend_id: activeFriendId, message: text });
  input.value = '';
  await loadMessages();
});

window.addEventListener('DOMContentLoaded', async () => {
  const attachBtn = document.getElementById('attach-btn');
  const fileInput = document.getElementById('file-input');
  const linkBtn = document.getElementById('insert-link-btn');
  const dropZone = document.getElementById('drop-zone');

  attachBtn.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', async () => {
    const file = fileInput.files?.[0];
    if (!file) return;
    if (!activeFriendId) {
      flash('Choisissez un ami avant d\'envoyer un fichier');
      return;
    }
    const result = await uploadAttachment(activeFriendId, file);
    if (!result.success) {
      flash(result.message || 'Upload echoue');
      return;
    }
    fileInput.value = '';
    await loadMessages();
    flash('Fichier envoyé');
  });

  linkBtn.addEventListener('click', async () => {
    const url = window.prompt('Collez le lien (https://...)');
    if (!url) return;
    if (!activeFriendId) {
      flash('Choisissez un ami avant d\'envoyer un lien');
      return;
    }
    const result = await api('send_message', 'POST', { friend_id: activeFriendId, message: url.trim() });
    if (!result.success) {
      flash(result.message || 'Envoi du lien impossible');
      return;
    }
    await loadMessages();
    flash('Lien envoyé');
  });

  ['dragenter', 'dragover'].forEach((evt) => {
    dropZone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropZone.classList.add('active');
    });
  });
  ['dragleave', 'drop'].forEach((evt) => {
    dropZone.addEventListener(evt, (e) => {
      e.preventDefault();
      dropZone.classList.remove('active');
    });
  });
  dropZone.addEventListener('drop', async (e) => {
    const file = e.dataTransfer?.files?.[0];
    if (!file) return;
    if (!activeFriendId) {
      flash('Choisissez un ami avant le drag & drop');
      return;
    }
    const result = await uploadAttachment(activeFriendId, file);
    if (!result.success) {
      flash(result.message || 'Upload echoue');
      return;
    }
    await loadMessages();
    flash('Fichier envoyé');
  });

  document.getElementById('notif-bell').addEventListener('click', () => {
    document.getElementById('notif-dropdown').classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    const bell = document.getElementById('notif-bell');
    const dropdown = document.getElementById('notif-dropdown');
    if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
  try {
    await loadSessionUser();
    await refreshData();
  } catch (error) {
    console.error('Initialisation chat erreur:', error);
    setPanelMessage('users-list', error.message);
    setPanelMessage('friends-list', error.message);
    setPanelMessage('chat-box', 'Le chat nécessite une session active et la base de données disponible.');
    flash(error.message);
    return;
  }
  setInterval(async () => {
    try {
      await refreshNotifications();
      if (activeFriendId) await loadMessages();
    } catch (error) {
      console.error('Rafraîchissement chat erreur:', error);
    }
  }, 5000);
});
