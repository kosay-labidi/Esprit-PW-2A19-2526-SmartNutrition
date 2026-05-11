;(function () {
if (window.__GL_CHALLENGES_COMPLETE_LOADED__) {
  if (typeof window.initChallenges === 'function') {
    setTimeout(() => window.initChallenges(), 100);
  }
  return;
}
window.__GL_CHALLENGES_COMPLETE_LOADED__ = true;

/**
 * Module Défis Collaboratifs Complet - GaiaLumen
 * Gestion complète des défis avec steakers 3D, classement, formulaire, etc.
 */

console.log('🏆 Challenges Complete JS chargé');

// ═══════════════════════════════════════════════════════════
// DONNÉES
// ═══════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════
// CONFIGURATION ET ENDPOINTS
// ═══════════════════════════════════════════════════════════

// Détection dynamique du chemin de base du backend
// IMPORTANT: module-loader auto-reload may inject this file multiple times.
// Use `var` + window guard to avoid "Identifier already declared".
var getBackendPath =
  window.getBackendPath ||
  function (file) {
    var isModule = window.location.pathname.includes('/modules/');
    var base = isModule ? '../../backend' : '../backend';
    return `${base}/challenges/${file}`;
  };
window.getBackendPath = getBackendPath;

const CHALLENGES_ENDPOINT = getBackendPath('listChallenges.php?ajax=1');
const PARTICIPANTS_ENDPOINT = getBackendPath('listParticipants.php');
const ADD_PARTICIPANT_ENDPOINT = getBackendPath('addParticipant.php');
const PAYMENT_ENDPOINT = (function () {
  var isModule = window.location.pathname.includes('/modules/');
  var base = isModule ? '../../backend' : '../backend';
  return `${base}/api/challenge-payment.php`;
})();

// État global du module (éviter collisions en auto-reload)
var allChallenges = window.allChallenges || [];
var allParticipants = window.allParticipants || [];
function getStoredChallengeUser() {
  try {
    const stored = JSON.parse(localStorage.getItem('gaialumen-user') || '{}');
    const id = parseInt(sessionStorage.getItem('user_id') || localStorage.getItem('user_id') || stored.id_utilisateur || stored.id || 0, 10);
    const nom = [stored.prenom, stored.nom].filter(Boolean).join(' ').trim() || stored.nom || sessionStorage.getItem('user_nom') || 'Utilisateur';
    const email = stored.email || sessionStorage.getItem('user_email') || '';
    return { id: id || 0, nom, pseudo: nom, email };
  } catch (_) {
    const id = parseInt(sessionStorage.getItem('user_id') || localStorage.getItem('user_id') || 0, 10);
    return { id: id || 0, nom: sessionStorage.getItem('user_nom') || 'Utilisateur', pseudo: 'user', email: sessionStorage.getItem('user_email') || '' };
  }
}
var currentUser = window.__USER__ || getStoredChallengeUser();
window.allChallenges = allChallenges;
window.allParticipants = allParticipants;

// Vue active (grid | swipe)
let activeChallengesView = 'grid';
let filteredChallenges = [];

// Pagination (éviter collisions en auto-reload)
var currentPage = window.__challengesCurrentPage || 1;
window.__challengesCurrentPage = currentPage;
var itemsPerPage = window.__challengesItemsPerPage || 6;
window.__challengesItemsPerPage = itemsPerPage;

// Données classement
const sampleParticipants = [
  { id: 1, nom: 'Marie Dupont', pseudo: 'marie_eco', avatar: '👩', progression: 95, points: 1250, steaker_niveau: 'double' },
  { id: 2, nom: 'Jean Martin', pseudo: 'jean_green', avatar: '👨', progression: 85, points: 1120, steaker_niveau: 'gold' },
  { id: 3, nom: 'Sophie Bernard', pseudo: 'sophie_nature', avatar: '👩', progression: 75, points: 980, steaker_niveau: 'gold' },
  { id: 4, nom: 'Pierre Dubois', pseudo: 'pierre_eco', avatar: '👨', progression: 68, points: 850, steaker_niveau: 'orange' },
  { id: 5, nom: 'Emma Petit', pseudo: 'emma_green', avatar: '👩', progression: 62, points: 790, steaker_niveau: 'silver' },
  { id: 6, nom: 'Lucas Moreau', pseudo: 'lucas_nature', avatar: '👨', progression: 58, points: 720, steaker_niveau: 'silver' },
  { id: 7, nom: 'Chloé Laurent', pseudo: 'chloe_eco', avatar: '👩', progression: 52, points: 650, steaker_niveau: 'orange' },
  { id: 8, nom: 'Thomas Simon', pseudo: 'thomas_green', avatar: '👨', progression: 48, points: 580, steaker_niveau: 'orange' },
  { id: 9, nom: 'Léa Michel', pseudo: 'lea_nature', avatar: '👩', progression: 42, points: 510, steaker_niveau: 'orange' },
  { id: 10, nom: 'Hugo Lefebvre', pseudo: 'hugo_eco', avatar: '👨', progression: 38, points: 440, steaker_niveau: 'bronze' },
  { id: 11, nom: 'Camille Roux', pseudo: 'camille_green', avatar: '👩', progression: 35, points: 370, steaker_niveau: 'bronze' },
  { id: 12, nom: 'Nathan Garnier', pseudo: 'nathan_nature', avatar: '👨', progression: 30, points: 300, steaker_niveau: 'bronze' },
  { id: 13, nom: 'Manon Rousseau', pseudo: 'manon_eco', avatar: '👩', progression: 28, points: 250, steaker_niveau: 'bronze' },
  { id: 14, nom: 'Alexandre Blanc', pseudo: 'alex_green', avatar: '👨', progression: 25, points: 200, steaker_niveau: 'bronze' },
  { id: 15, nom: 'Julie Girard', pseudo: 'julie_nature', avatar: '👩', progression: 22, points: 150, steaker_niveau: 'bronze' }
];

// ═══════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES
// ═══════════════════════════════════════════════════════════

function getSteakerLevel(progression) {
  if (progression >= 100) return 'double';
  if (progression >= 90) return 'gold';
  if (progression >= 60) return 'silver';
  if (progression >= 30) return 'bronze';
  return 'none';
}

function getSteakerClass(niveau) {
  const classes = {
    'double': 'steaker-double',
    'gold': 'steaker-gold',
    'silver': 'steaker-silver',
    'bronze': 'steaker-bronze',
    'none': 'steaker-locked'
  };
  return classes[niveau] || 'steaker-locked';
}

function getProgressColor(progression) {
  if (progression < 30) return 'red';
  if (progression < 70) return 'orange';
  return 'green';
}

function renderDaysLeftBadge(dateFin) {
  const days = Math.ceil((new Date(dateFin) - new Date()) / 86400000);
  if (days < 0) return `<span class="gl-days-badge gl-days-badge--over">Terminé</span>`;
  if (days === 0) return `<span class="gl-days-badge gl-days-badge--today">Dernier jour !</span>`;
  if (days <= 3) return `<span class="gl-days-badge gl-days-badge--urgent">${days}j restants</span>`;
  if (days <= 7) return `<span class="gl-days-badge gl-days-badge--soon">${days}j restants</span>`;
  return `<span class="gl-days-badge">${days}j restants</span>`;
}

function formatChallengePrice(challenge) {
  const price = Math.max(0, parseFloat(challenge?.prix || 0) || 0);
  return `${price.toFixed(2).replace('.', ',')} DT`;
}

function isPaidChallenge(challenge) {
  return parseInt(challenge?.est_payant || 0, 10) === 1 && (parseFloat(challenge?.prix || 0) || 0) > 0;
}

function createSteakerHTML(icon, niveau, size = 'medium') {
  const steakerClass = getSteakerClass(niveau);
  
  if (niveau === 'double') {
    return `
      <div class="steaker-3d ${steakerClass} steaker-${size}">
        <div class="steaker-icon-main">${icon}</div>
        <div class="steaker-icon-orbit">${icon}</div>
        <div class="burst"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
      </div>
    `;
  }
  
  const particles = niveau === 'gold' ? 
    '<div class="particle"></div>'.repeat(4) : '';
  
  return `
    <div class="steaker-3d ${steakerClass} steaker-${size}">
      <div class="steaker-icon">${icon}</div>
      ${particles}
    </div>
  `;
}

/**
 * Mappe les données du backend vers le format attendu par le frontend
 */
function mapChallengesData(data) {
  if (!Array.isArray(data)) return [];
  return data.map(c => ({
    ...c,
    id: parseInt(c.id, 10),
    titre: c.titre || 'Défi sans titre',
    description: c.description || 'Aucune description disponible',
    type: c.type || 'collectif',
    objectif: c.objectif || 'écologie',
    valeur_cible: parseInt(c.valeur_cible || 0, 10),
    participants_count: parseInt(c.participants_count || 0, 10),
    progression: parseInt(c.progression || 0, 10),
    est_payant: parseInt(c.est_payant || 0, 10) === 1 ? 1 : 0,
    prix: parseFloat(c.prix || 0) || 0,
    statut: normalizeChallengeStatus(c.statut),
    steaker: c.streak_icon || c.steaker || '🏆',
    steaker_nom: c.steaker_nom || c.objectif || 'Défi',
    // Utiliser une résolution plus élevée pour la vue Swipe (image pleine largeur)
    image: c.image || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1400&auto=format&fit=crop&q=85'
  }));
}

// ═══════════════════════════════════════════════════════════
// CHARGEMENT DES DÉFIS
// ═══════════════════════════════════════════════════════════

async function loadChallenges() {
  const grid = document.getElementById('challenges-grid');
  const loading = document.getElementById('challenges-loading');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !loading || !empty) return;
  
  loading.style.display = 'block';
  grid.style.display = 'none';
  empty.style.display = 'none';
  
  try {
    const response = await fetch(`${CHALLENGES_ENDPOINT}&user_id=${encodeURIComponent(currentUser.id || '')}&t=${Date.now()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });

    if (!response.ok) throw new Error(`Erreur HTTP ${response.status}`);

    const data = await response.json();
    if (!Array.isArray(data)) {
      console.error('Données reçues non valides:', data);
      allChallenges = [];
    } else {
      allChallenges = mapChallengesData(data);
    }

    // Exposer l'état à la page (certaines features patchées y accèdent)
    window.allChallenges = allChallenges;
    if (typeof window.ensureChatChannelsReady === 'function') {
      window.ensureChatChannelsReady();
    }
    if (typeof window.syncChatWithChallenges === 'function') {
      window.syncChatWithChallenges(allChallenges);
    }
    if (typeof window.refreshChatDefiTabs === 'function') {
      window.refreshChatDefiTabs();
    }

    filterChallenges();
    populateRankingSelect();
    await loadParticipantsForRanking();
    openChallengeFromUrl();
  } catch (err) {
    console.error('Erreur lors du chargement des défis:', err);
    allChallenges = [];
    window.allChallenges = allChallenges;
    if (typeof window.ensureChatChannelsReady === 'function') {
      window.ensureChatChannelsReady();
    }
    if (typeof window.syncChatWithChallenges === 'function') {
      window.syncChatWithChallenges(allChallenges);
    }
    if (typeof window.refreshChatDefiTabs === 'function') {
      window.refreshChatDefiTabs();
    }
    filterChallenges();
    populateRankingSelect();
    await loadParticipantsForRanking();
  } finally {
    loading.style.display = 'none';
  }
}

function getChallengeShareUrl(challengeId) {
  const url = new URL(window.location.href);
  url.pathname = url.pathname.includes('/modules/')
    ? url.pathname.replace(/\/modules\/[^/]*$/, '/dashboard.html')
    : url.pathname.replace(/\/[^/]*$/, '/dashboard.html');
  url.searchParams.set('module', 'challenges');
  url.searchParams.set('challenge_id', parseInt(challengeId, 10));
  url.hash = '';
  return url.toString();
}

function getChallengeParticipationUrl(challengeId) {
  const url = new URL(getChallengeShareUrl(challengeId));
  url.searchParams.set('participer', '1');
  url.searchParams.set('source', 'qr');
  return url.toString();
}

function getChallengeQrUrl(challengeId, size = 220) {
  const data = encodeURIComponent(getChallengeParticipationUrl(challengeId));
  return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&margin=12&data=${data}`;
}

function openChallengeFromUrl() {
  if (window.__challengeDeepLinkOpened) return;
  const params = new URLSearchParams(window.location.search);
  const id = parseInt(params.get('challenge_id') || params.get('defi') || '0', 10);
  if (!id || !allChallenges.some(c => parseInt(c.id, 10) === id)) return;
  window.__challengeDeepLinkOpened = true;
  const shouldOpenParticipation = ['1', 'true', 'oui', 'yes'].includes(
    (params.get('participer') || params.get('join') || '').toLowerCase()
  );
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === id);
  const isActif = normalizeChallengeStatus(challenge?.statut) === 'actif';

  setTimeout(() => {
    if (shouldOpenParticipation && isActif) {
      closeChallengeModal();
      openDrawer(id);
      if (window.showToast) {
        showToast('Défi ouvert depuis le QR', 'Complétez le formulaire pour participer.', 'info', '📱');
      }
      return;
    }
    showChallengeDetail(id);
    if (shouldOpenParticipation && !isActif && window.showToast) {
      showToast('Participation indisponible', 'Ce défi n’est pas actif pour le moment.', 'warning');
    }
  }, 250);
}

async function copyChallengeQrLink(challengeId) {
  const link = getChallengeParticipationUrl(challengeId);
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(link);
    } else {
      throw new Error('clipboard unavailable');
    }
    window.addNotification?.('Lien de participation copié.', '🔗');
  } catch (_) {
    window.prompt('Copiez le lien de participation :', link);
  }
}

function dataUrlToBlob(dataUrl) {
  const parts = dataUrl.split(',');
  const mime = (parts[0].match(/:(.*?);/) || [])[1] || 'image/png';
  const bin = atob(parts[1] || '');
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return new Blob([bytes], { type: mime });
}

function downloadChallengeQr(challengeId) {
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === parseInt(challengeId, 10));
  const safeTitle = (challenge?.titre || `defi-${challengeId}`).toString().trim().toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || `defi-${challengeId}`;
  const link = document.createElement('a');
  link.href = getChallengeQrUrl(challengeId, 420);
  link.download = `qr-${safeTitle}.png`;
  link.target = '_blank';
  document.body.appendChild(link);
  link.click();
  link.remove();
}

function getChallengeShareText(challengeId) {
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === parseInt(challengeId, 10));
  const title = challenge?.titre || 'Défi GaiaLumen';
  return `Découvre ce défi GaiaLumen : ${title}`;
}

function getAppLogoSvgDataUri() {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60">
      <defs>
        <radialGradient id="ag" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#3A86C4"/>
          <stop offset="100%" stop-color="#5B3E96"/>
        </radialGradient>
        <linearGradient id="lg" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#1F3D2B"/>
          <stop offset="100%" stop-color="#3A86C4"/>
        </linearGradient>
      </defs>
      <circle cx="30" cy="30" r="28" stroke="url(#ag)" stroke-width="1.5" opacity=".85" fill="#0f2318"/>
      <circle cx="30" cy="30" r="22" stroke="url(#ag)" stroke-width=".8" opacity=".42" fill="none"/>
      <path d="M30 10 C42 18,46 30,30 50 C14 30,18 18,30 10Z" fill="url(#lg)"/>
      <path d="M30 14 L30 46" stroke="rgba(242,232,207,.65)" stroke-width="1" stroke-linecap="round"/>
    </svg>`;
  return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = src;
  });
}

function wrapCanvasText(ctx, text, x, y, maxWidth, lineHeight, maxLines = 3) {
  const words = String(text || '').split(/\s+/).filter(Boolean);
  let line = '';
  let lines = [];
  words.forEach(word => {
    const test = line ? `${line} ${word}` : word;
    if (ctx.measureText(test).width > maxWidth && line) {
      lines.push(line);
      line = word;
    } else {
      line = test;
    }
  });
  if (line) lines.push(line);
  lines = lines.slice(0, maxLines);
  lines.forEach((l, i) => ctx.fillText(i === maxLines - 1 && words.length > l.split(/\s+/).length ? `${l}...` : l, x, y + i * lineHeight));
}

async function createChallengeShareImage(challengeId, format = 'post') {
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === parseInt(challengeId, 10));
  if (!challenge) return null;

  const isStory = format === 'story';
  const width = isStory ? 1080 : 1080;
  const height = isStory ? 1920 : 1080;
  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext('2d');

  const safeX = isStory ? 72 : 64;
  const safeTop = isStory ? 86 : 58;
  const safeBottom = isStory ? 96 : 64;
  const cream = '#F2E8CF';
  const muted = 'rgba(242,232,207,.76)';

  function roundRect(x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function coverImage(img, x, y, w, h) {
    const scale = Math.max(w / img.width, h / img.height);
    const sw = w / scale;
    const sh = h / scale;
    const sx = (img.width - sw) / 2;
    const sy = (img.height - sh) / 2;
    ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
  }

  function drawPill(text, x, y, accent = 'rgba(168,184,160,.16)') {
    ctx.font = `800 ${isStory ? 25 : 20}px Arial, sans-serif`;
    const padX = isStory ? 22 : 18;
    const pillH = isStory ? 48 : 40;
    const w = ctx.measureText(text).width + padX * 2;
    ctx.fillStyle = accent;
    roundRect(x, y, w, pillH, pillH / 2);
    ctx.fill();
    ctx.strokeStyle = 'rgba(242,232,207,.18)';
    ctx.stroke();
    ctx.fillStyle = cream;
    ctx.fillText(text, x + padX, y + pillH * 0.67);
    return w;
  }

  const bg = ctx.createLinearGradient(0, 0, width, height);
  bg.addColorStop(0, '#07170d');
  bg.addColorStop(0.52, '#153821');
  bg.addColorStop(1, '#2f85be');
  ctx.fillStyle = bg;
  ctx.fillRect(0, 0, width, height);

  if (challenge.image) {
    try {
      const bgImg = await loadImage(challenge.image);
      coverImage(bgImg, 0, 0, width, height);
      ctx.fillStyle = 'rgba(5,18,10,.58)';
      ctx.fillRect(0, 0, width, height);
      const imageShade = ctx.createLinearGradient(0, 0, 0, height);
      imageShade.addColorStop(0, 'rgba(5,18,10,.28)');
      imageShade.addColorStop(0.52, 'rgba(5,18,10,.62)');
      imageShade.addColorStop(1, 'rgba(47,133,190,.78)');
      ctx.fillStyle = imageShade;
      ctx.fillRect(0, 0, width, height);
    } catch (_) {}
  }

  ctx.fillStyle = 'rgba(242,232,207,0.075)';
  ctx.beginPath();
  ctx.arc(width * 0.9, height * 0.12, width * 0.28, 0, Math.PI * 2);
  ctx.fill();
  ctx.beginPath();
  ctx.arc(width * 0.04, height * 0.92, width * 0.34, 0, Math.PI * 2);
  ctx.fill();

  const logo = await loadImage(getAppLogoSvgDataUri());
  const qr = await loadImage(getChallengeQrUrl(challengeId, 420));
  const logoSize = isStory ? 92 : 76;
  ctx.drawImage(logo, safeX, safeTop, logoSize, logoSize);

  ctx.fillStyle = cream;
  ctx.font = `900 ${isStory ? 42 : 34}px Arial, sans-serif`;
  ctx.fillText('GaiaLumen', safeX + logoSize + 22, safeTop + logoSize * 0.52);
  ctx.fillStyle = muted;
  ctx.font = `800 ${isStory ? 25 : 20}px Arial, sans-serif`;
  ctx.fillText('Défi collaboratif', safeX + logoSize + 22, safeTop + logoSize * 0.86);

  const pillY = isStory ? 300 : 210;
  let nextPillX = safeX;
  nextPillX += drawPill(challenge.streak_icon || '🏆', nextPillX, pillY, 'rgba(31,61,43,.45)') + 12;
  nextPillX += drawPill((challenge.type || 'Défi').toString(), nextPillX, pillY, 'rgba(31,61,43,.45)') + 12;
  if (challenge.participants_count !== undefined) {
    drawPill(`👥 ${parseInt(challenge.participants_count || 0, 10)}`, nextPillX, pillY, 'rgba(31,61,43,.45)');
  }

  ctx.fillStyle = cream;
  ctx.font = `900 ${isStory ? 78 : 62}px Arial, sans-serif`;
  wrapCanvasText(ctx, challenge.titre || 'Défi GaiaLumen', safeX, isStory ? 500 : 335, width - safeX * 2, isStory ? 90 : 72, 3);

  ctx.fillStyle = 'rgba(242,232,207,.86)';
  ctx.font = `600 ${isStory ? 34 : 28}px Arial, sans-serif`;
  wrapCanvasText(ctx, challenge.description || getChallengeShareText(challengeId), safeX, isStory ? 815 : 570, width - safeX * 2, isStory ? 50 : 40, isStory ? 5 : 3);

  const qrSize = isStory ? 300 : 226;
  const qrX = width - qrSize - safeX;
  const qrY = height - qrSize - (isStory ? 170 : 92);
  ctx.fillStyle = '#ffffff';
  roundRect(qrX - 18, qrY - 18, qrSize + 36, qrSize + 36, 28);
  ctx.fill();
  ctx.drawImage(qr, qrX, qrY, qrSize, qrSize);

  const ctaY = isStory ? height - 265 : height - 165;
  ctx.fillStyle = cream;
  ctx.font = `900 ${isStory ? 36 : 28}px Arial, sans-serif`;
  ctx.fillText('Scanne pour participer', safeX, ctaY);
  ctx.fillStyle = 'rgba(242,232,207,.72)';
  ctx.font = `700 ${isStory ? 25 : 20}px Arial, sans-serif`;
  ctx.fillText('Lien copié automatiquement', safeX, ctaY + 42);

  ctx.fillStyle = 'rgba(242,232,207,.38)';
  ctx.font = `700 ${isStory ? 20 : 16}px Arial, sans-serif`;
  ctx.fillText('gaialumen.app', safeX, height - safeBottom);

  return canvas.toDataURL('image/png');
}

async function shareChallengeVisual(challengeId, format = 'post') {
  const dataUrl = await createChallengeShareImage(challengeId, format);
  if (!dataUrl) return;
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === parseInt(challengeId, 10));
  const safeTitle = (challenge?.titre || `defi-${challengeId}`).toString().trim().toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || `defi-${challengeId}`;
  const fileName = `${format}-${safeTitle}.png`;

  try {
    const blob = dataUrlToBlob(dataUrl);
    const file = new File([blob], fileName, { type: 'image/png' });
    if (navigator.canShare?.({ files: [file] }) && navigator.share) {
      await navigator.share({
        title: 'GaiaLumen',
        text: getChallengeShareText(challengeId),
        files: [file],
      });
      return;
    }
  } catch (_) {}

  const link = document.createElement('a');
  link.href = dataUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  link.remove();
  return 'downloaded';
}

async function shareChallengeNative(challengeId) {
  const url = getChallengeShareUrl(challengeId);
  const text = getChallengeShareText(challengeId);
  if (navigator.share) {
    try {
      await navigator.share({ title: 'GaiaLumen', text, url });
      return;
    } catch (_) {}
  }
  await copyChallengeQrLink(challengeId);
}

function shareChallengeTo(platform, challengeId) {
  const url = getChallengeShareUrl(challengeId);
  const text = getChallengeShareText(challengeId);
  const encodedUrl = encodeURIComponent(url);
  const encodedText = encodeURIComponent(text);
  const targets = {
    whatsapp: `https://wa.me/?text=${encodedText}%20${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    x: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
  };

  if (platform === 'instagram' || platform === 'tiktok') {
    shareChallengeVisual(challengeId, platform === 'tiktok' ? 'story' : 'post')
      .then(() => copyChallengeQrLink(challengeId))
      .then(() => {
        const appUrl = platform === 'tiktok' ? 'https://www.tiktok.com/upload' : 'https://www.instagram.com/';
        window.addNotification?.('Image prête. Importez-la en story/post puis collez le lien si besoin.', '📲');
        window.open(appUrl, '_blank', 'noopener,noreferrer');
      });
    return;
  }

  if (targets[platform]) {
    window.open(targets[platform], '_blank', 'noopener,noreferrer,width=760,height=620');
  }
}

function getSharePlatformLabel(platform) {
  const labels = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    facebook: 'Facebook',
    whatsapp: 'WhatsApp',
    x: 'X',
    linkedin: 'LinkedIn',
    chat: 'Chat du défi',
  };
  return labels[platform] || 'Partager';
}

function getSharePlatformUrl(platform, challengeId) {
  const url = getChallengeShareUrl(challengeId);
  const text = getChallengeShareText(challengeId);
  const encodedUrl = encodeURIComponent(url);
  const encodedText = encodeURIComponent(text);
  const targets = {
    whatsapp: `https://wa.me/?text=${encodedText}%20${encodedUrl}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    instagram: 'https://www.instagram.com/',
    tiktok: 'https://www.tiktok.com/upload',
    x: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
  };
  return targets[platform] || '';
}

function ensureShareTypeModal() {
  let modal = document.getElementById('challenge-share-type-modal');
  if (modal) return modal;

  modal = document.createElement('div');
  modal.id = 'challenge-share-type-modal';
  modal.className = 'gl-share-type-modal';
  modal.innerHTML = `
    <div class="gl-share-type-modal__backdrop" onclick="closeShareTypeModal()"></div>
    <div class="gl-share-type-modal__panel" role="dialog" aria-modal="true">
      <button type="button" class="gl-share-type-modal__close" onclick="closeShareTypeModal()">×</button>
      <div class="gl-share-type-modal__brand">
        <img id="share-type-logo" alt="GaiaLumen">
        <div>
          <div class="gl-share-type-modal__eyebrow">GaiaLumen</div>
          <h3 id="share-type-title">Partager</h3>
        </div>
      </div>
      <p id="share-type-sub" class="gl-share-type-modal__sub"></p>
      <div class="gl-share-type-modal__choices" id="share-type-choices"></div>
      <p class="gl-share-type-modal__hint">Story/Post créent une image avec logo + QR. Message partage le lien direct.</p>
    </div>
  `;
  document.body.appendChild(modal);
  return modal;
}

function closeShareTypeModal() {
  const modal = document.getElementById('challenge-share-type-modal');
  if (modal) modal.classList.remove('active');
}

function openShareTypePicker(platform, challengeId) {
  const modal = ensureShareTypeModal();
  const label = getSharePlatformLabel(platform);
  const logo = modal.querySelector('#share-type-logo');
  const title = modal.querySelector('#share-type-title');
  const sub = modal.querySelector('#share-type-sub');
  const choices = modal.querySelector('#share-type-choices');
  if (logo) logo.src = getAppLogoSvgDataUri();
  if (title) title.textContent = `Partager sur ${label}`;
  if (sub) sub.textContent = 'Choisissez le type de partage.';

  const formats = platform === 'chat'
    ? [{ type: 'message', label: 'Message', desc: 'ouvrir le chat du défi' }]
    : [
        { type: 'story', label: 'Story', desc: 'image verticale' },
        { type: 'post', label: 'Post', desc: 'image carrée' },
        { type: 'message', label: 'Message', desc: 'lien direct' },
      ];

  choices.innerHTML = formats.map(item => `
    <button type="button" class="gl-share-type-choice" onclick="handleShareTypeChoice('${platform}', ${parseInt(challengeId, 10)}, '${item.type}')">
      <span>${item.label}</span>
      <small>${item.desc}</small>
    </button>
  `).join('');
  modal.classList.add('active');
}

async function handleShareTypeChoice(platform, challengeId, type) {
  closeShareTypeModal();
  if (platform === 'chat') {
    shareChallengeToChat(challengeId);
    return;
  }
  if (type === 'message') {
    shareChallengeTo(platform, challengeId);
    return;
  }

  const result = await shareChallengeVisual(challengeId, type);
  await copyChallengeQrLink(challengeId);
  const target = getSharePlatformUrl(platform, challengeId);
  if (target && result === 'downloaded') {
    if (platform === 'instagram' || platform === 'tiktok') {
      window.addNotification?.('Story téléchargée et lien copié. Ouvrez la plateforme pour publier.', '📲');
    }
    window.open(target, '_blank', 'noopener,noreferrer');
  }
}

function shareChallengeToChat(challengeId) {
  const challenge = allChallenges.find(c => parseInt(c.id, 10) === parseInt(challengeId, 10));
  if (typeof window.openChatModal === 'function') {
    closeChallengeModal();
    window.openChatModal(challengeId, challenge?.titre || 'Chat du Défi', challenge?.steaker || '🏆');
    return;
  }
  window.addNotification?.('Chat non disponible pour le moment.', '💬');
}

/**
 * Normalise le statut des défis pour correspondre aux classes CSS et filtres
 */
function normalizeChallengeStatus(value) {
  const raw = (value ?? '').toString().trim().toLowerCase();
  if (!raw) return 'actif';
  if (raw === 'active' || raw === 'en cours' || raw === 'en_cours' || raw === 'actif') return 'actif';
  if (raw === 'termine' || raw === 'terminé' || raw === 'terminée') return 'termine';
  if (raw === 'futur' || raw === 'a venir' || raw === 'à venir') return 'futur';
  return 'actif'; // Par défaut
}

function filterChallenges() {
  currentPage = 1; // Reset pagination
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const sortFilter = document.getElementById('challenge-sort-filter');
  const activeChip = document.querySelector('.gl-chip--active');
  const grid = document.getElementById('challenges-grid');
  const empty = document.getElementById('challenges-empty');
  
  if (!grid || !empty) return;
  
  const search = searchInput?.value.toLowerCase() || '';
  const status = statusFilter?.value || '';
  const chipStatus = activeChip?.dataset.status || '';
  const sortType = sortFilter?.value || 'date_desc';
  
  let filtered = allChallenges.filter(c => {
    const titre = (c.titre || '').toLowerCase();
    const description = (c.description || '').toLowerCase();
    const matchSearch = titre.includes(search) || description.includes(search);
    
    // Priorité au chip de filtrage
    let matchStatus = true;
    if (chipStatus === 'liked') {
      matchStatus = !!c.is_liked;
    } else if (chipStatus) {
      matchStatus = c.statut === chipStatus;
    } else if (status) {
      matchStatus = c.statut === status;
    }
    
    return matchSearch && matchStatus;
  });

  // Tri des défis
  filtered.sort((a, b) => {
    if (sortType === 'participants_desc') {
      return (b.participants_count || 0) - (a.participants_count || 0);
    } else if (sortType === 'titre_asc') {
      return (a.titre || '').localeCompare(b.titre || '');
    } else {
      // date_desc par défaut
      return new Date(b.date_debut) - new Date(a.date_debut);
    }
  });

  filteredChallenges = filtered;
  window.filteredChallenges = filteredChallenges;

  const swipeViewEl = document.getElementById('swipe-view');
  const isSwipe = activeChallengesView === 'swipe' && !!swipeViewEl;

  if (filtered.length === 0) {
    grid.style.display = 'none';
    if (isSwipe) {
      empty.style.display = 'none';
      renderSwipeDeck([]);
    } else {
      empty.style.display = 'block';
    }
    return;
  }

  empty.style.display = 'none';

  if (isSwipe) {
    grid.style.display = 'none';
    renderSwipeDeck(filtered);
  } else {
    grid.style.display = 'grid';
    renderChallenges(filtered);
  }
}

function setChallengesView(view) {
  const grid = document.getElementById('challenges-grid');
  const swipeView = document.getElementById('swipe-view');
  const btnGrid = document.getElementById('view-grid');
  const btnList = document.getElementById('view-list');
  const btnSwipe = document.getElementById('view-swipe');

  if (!grid) return;
  activeChallengesView = view === 'swipe' ? 'swipe' : 'grid';

  if (activeChallengesView === 'swipe' && swipeView) {
    grid.style.display = 'none';
    swipeView.style.display = 'flex';
    btnSwipe?.classList.add('active');
    btnGrid?.classList.remove('active');
    btnList?.classList.remove('active');
    renderSwipeDeck(filteredChallenges.length ? filteredChallenges : allChallenges);
    return;
  }

  // Grille/Liste
  if (swipeView) swipeView.style.display = 'none';
  grid.style.display = 'grid';
  btnSwipe?.classList.remove('active');
}

function escapeHtml(str) {
  return (str ?? '').toString().replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[m]));
}

function buildSwipeCard(challenge) {
  const statut = normalizeChallengeStatus(challenge.statut);
  const dateFin = new Date(challenge.date_fin);
  const hasValidDate = !Number.isNaN(dateFin.getTime());
  const days = hasValidDate ? Math.ceil((dateFin - new Date()) / 86400000) : null;
  const daysLabel = days === null ? '' : (days < 0 ? 'Terminé' : `${Math.max(days, 0)}j`);
  const rewardName = escapeHtml(challenge.steaker_nom || challenge.objectif || 'Récompense');

  return `
    <article class="gl-swipe-card" data-challenge-id="${challenge.id}">
      <div class="gl-swipe-card__like-label">LIKE</div>
      <div class="gl-swipe-card__nope-label">NOPE</div>

      ${challenge.image
        ? `<img class="gl-swipe-card__img" src="${escapeHtml(challenge.image)}" alt="${escapeHtml(challenge.titre)}" loading="lazy" decoding="async">`
        : `<div class="gl-swipe-card__img-placeholder">🏞️</div>`
      }

      <span class="gl-swipe-card__status-badge ${statut}">
        ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
      </span>
      ${daysLabel ? `<span class="gl-swipe-card__days-badge">${daysLabel}</span>` : ''}

      <div class="gl-swipe-card__body">
        <div>
          <div class="gl-swipe-card__title">${escapeHtml(challenge.titre)}</div>
          <div class="gl-swipe-card__desc">${escapeHtml(challenge.description)}</div>

          <div class="gl-swipe-card__reward">
            <span class="gl-swipe-card__reward-icon">🏆</span>
            <div>
              <div class="gl-swipe-card__reward-label">Récompense</div>
              <div class="gl-swipe-card__reward-name">${rewardName}</div>
            </div>
          </div>

          <div class="gl-swipe-card__meta">
            <div class="gl-swipe-card__meta-item">👥 <span>${parseInt(challenge.participants_count || 0, 10)}</span></div>
            <div class="gl-swipe-card__meta-item">📈 <span>${parseInt(challenge.progression || 0, 10)}%</span></div>
          </div>
        </div>

        <div class="gl-swipe-card__progress">
          <div class="gl-swipe-card__progress-row">
            <span style="font-size:.72rem;color:var(--muted,#a8b8a0)">Progression</span>
            <span style="font-weight:800">${parseInt(challenge.progression || 0, 10)}%</span>
          </div>
          <div class="gl-swipe-card__progress-bar">
            <div class="gl-swipe-card__progress-fill" style="width:${Math.max(0, Math.min(100, parseInt(challenge.progression || 0, 10)))}%"></div>
          </div>
        </div>
      </div>
    </article>
  `;
}

function updateSwipeCounter(currentIndex, total) {
  const curEl = document.getElementById('swipe-current');
  const totEl = document.getElementById('swipe-total');
  if (totEl) totEl.textContent = String(total || 0);
  if (curEl) curEl.textContent = String(Math.min(total || 0, Math.max(1, currentIndex)));
}

function getTopSwipeCard() {
  const deck = document.getElementById('swipe-deck');
  if (!deck) return null;
  const cards = deck.querySelectorAll('.gl-swipe-card');
  return cards.length ? cards[0] : null;
}

function attachSwipeDrag(card) {
  if (!card || card.dataset.dragBound === 'true') return;
  card.dataset.dragBound = 'true';

  let startX = 0;
  let startY = 0;
  let dx = 0;
  let dy = 0;
  let dragging = false;

  const onMove = (e) => {
    if (!dragging) return;
    dx = (e.clientX - startX);
    dy = (e.clientY - startY);
    const rot = Math.max(-18, Math.min(18, dx / 18));
    card.style.transform = `translate(${dx}px, ${dy * 0.25}px) rotate(${rot}deg)`;
    const like = card.querySelector('.gl-swipe-card__like-label');
    const nope = card.querySelector('.gl-swipe-card__nope-label');
    if (like) like.style.opacity = dx > 30 ? String(Math.min(1, dx / 120)) : '0';
    if (nope) nope.style.opacity = dx < -30 ? String(Math.min(1, Math.abs(dx) / 120)) : '0';
  };

  const endDrag = () => {
    if (!dragging) return;
    dragging = false;
    card.classList.remove('is-dragging');
    card.releasePointerCapture?.(pointerId);

    const threshold = 120;
    if (dx > threshold) {
      swipeExit('right');
      return;
    }
    if (dx < -threshold) {
      swipeExit('left');
      return;
    }
    card.style.transition = 'transform 180ms ease';
    card.style.transform = '';
    const like = card.querySelector('.gl-swipe-card__like-label');
    const nope = card.querySelector('.gl-swipe-card__nope-label');
    if (like) like.style.opacity = '0';
    if (nope) nope.style.opacity = '0';
    setTimeout(() => { card.style.transition = ''; }, 220);
  };

  let pointerId = null;
  card.addEventListener('pointerdown', (e) => {
    // Ne permettre le drag que sur la carte du dessus
    if (card !== getTopSwipeCard()) return;
    pointerId = e.pointerId;
    dragging = true;
    startX = e.clientX;
    startY = e.clientY;
    dx = 0;
    dy = 0;
    card.classList.add('is-dragging');
    card.setPointerCapture?.(e.pointerId);
  });
  card.addEventListener('pointermove', onMove);
  card.addEventListener('pointerup', endDrag);
  card.addEventListener('pointercancel', endDrag);
}

function swipeExit(direction) {
  const deck = document.getElementById('swipe-deck');
  if (!deck) return;
  const card = getTopSwipeCard();
  if (!card) return;

  const id = parseInt(card.getAttribute('data-challenge-id') || '0', 10);
  const challenge = (filteredChallenges.length ? filteredChallenges : allChallenges).find(c => c.id === id);

  card.classList.remove('is-dragging');
  card.style.transition = '';
  card.classList.add(direction === 'right' ? 'leaving-right' : 'leaving-left');

  // Like: ouvrir détails (et participation depuis le drawer). Skip: juste passer.
  if (direction === 'right' && typeof window.showChallengeDetail === 'function' && id) {
    try { window.showChallengeDetail(id); } catch (_) {}
    if (challenge) showToast?.(`Défi "${challenge.titre}"`, 'success');
  }

  setTimeout(() => {
    card.remove();
    const remaining = deck.querySelectorAll('.gl-swipe-card').length;
    const total = parseInt(document.getElementById('swipe-total')?.textContent || '0', 10) || 0;
    const currentShown = total - remaining;
    updateSwipeCounter(currentShown + 1, total);

    const done = document.getElementById('swipe-done');
    if (done) done.style.display = remaining === 0 ? 'flex' : 'none';

    const next = getTopSwipeCard();
    if (next) attachSwipeDrag(next);
  }, 420);
}

function renderSwipeDeck(challenges) {
  const swipeView = document.getElementById('swipe-view');
  const deck = document.getElementById('swipe-deck');
  const done = document.getElementById('swipe-done');
  if (!swipeView || !deck || !done) return;

  const list = Array.isArray(challenges) ? challenges : [];
  deck.innerHTML = '';

  const maxCards = Math.min(20, list.length);
  for (let i = 0; i < maxCards; i++) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = buildSwipeCard(list[i]);
    const el = wrapper.firstElementChild;
    if (el) deck.appendChild(el);
  }

  updateSwipeCounter(1, maxCards);
  done.style.display = maxCards === 0 ? 'flex' : 'none';

  // Bind buttons once
  const btnSkip = document.getElementById('swipe-btn-skip');
  const btnLike = document.getElementById('swipe-btn-like');
  const btnInfo = document.getElementById('swipe-btn-info');
  const btnReset = document.getElementById('swipe-reset');

  if (btnSkip && btnSkip.dataset.bound !== 'true') {
    btnSkip.addEventListener('click', () => swipeExit('left'));
    btnSkip.dataset.bound = 'true';
  }
  if (btnLike && btnLike.dataset.bound !== 'true') {
    btnLike.addEventListener('click', () => swipeExit('right'));
    btnLike.dataset.bound = 'true';
  }
  if (btnInfo && btnInfo.dataset.bound !== 'true') {
    btnInfo.addEventListener('click', () => {
      const top = getTopSwipeCard();
      const id = parseInt(top?.getAttribute('data-challenge-id') || '0', 10);
      if (id && typeof window.showChallengeDetail === 'function') window.showChallengeDetail(id);
    });
    btnInfo.dataset.bound = 'true';
  }
  if (btnReset && btnReset.dataset.bound !== 'true') {
    btnReset.addEventListener('click', () => renderSwipeDeck(list));
    btnReset.dataset.bound = 'true';
  }

  const top = getTopSwipeCard();
  if (top) attachSwipeDrag(top);
}

function renderChallenges(challenges) {
  const grid = document.getElementById('challenges-grid');
  const pagination = document.getElementById('challenges-pagination');
  if (!grid) return;

  if (challenges.length === 0) {
    grid.style.display = 'none';
    if (pagination) pagination.style.display = 'none';
    document.getElementById('challenges-empty').style.display = 'block';
    return;
  }

  document.getElementById('challenges-empty').style.display = 'none';
  grid.style.display = 'grid';

  // Logique de pagination
  const totalPages = Math.ceil(challenges.length / itemsPerPage);
  if (currentPage > totalPages) currentPage = totalPages || 1;
  
  const start = (currentPage - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  const paginatedItems = challenges.slice(start, end);

  // Mise à jour de l'UI de pagination
  if (pagination) {
    pagination.style.display = totalPages > 1 ? 'flex' : 'none';
    const pageInfo = document.getElementById('page-info');
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
    
    const btnPrev = document.getElementById('prev-page');
    const btnNext = document.getElementById('next-page');
    if (btnPrev) {
      btnPrev.disabled = currentPage === 1;
      btnPrev.style.opacity = currentPage === 1 ? '0.4' : '1';
      btnPrev.style.pointerEvents = currentPage === 1 ? 'none' : 'auto';
    }
    if (btnNext) {
      btnNext.disabled = currentPage === totalPages;
      btnNext.style.opacity = currentPage === totalPages ? '0.4' : '1';
      btnNext.style.pointerEvents = currentPage === totalPages ? 'none' : 'auto';
    }
  }

  grid.innerHTML = paginatedItems.map(c => {
    const dateDebut = new Date(c.date_debut);
    const dateFin = new Date(c.date_fin);
    const hasValidDates = !Number.isNaN(dateDebut.getTime()) && !Number.isNaN(dateFin.getTime());
    const niveau = getSteakerLevel(c.progression);
    const progressColor = getProgressColor(c.progression);
    const statut = normalizeChallengeStatus(c.statut);
    const canParticipate = statut === 'actif';
    const paid = isPaidChallenge(c);
    const dateLabel = hasValidDates
      ? `${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})} - ${dateFin.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'})}`
      : 'Dates non disponibles';
    const daysLeftBadge = hasValidDates ? renderDaysLeftBadge(c.date_fin) : '';
    
    return `
      <div class="challenge-card" data-challenge-id="${c.id}">
        <div class="challenge-card-main">
          ${c.image ? `
            <div class="challenge-image-wrap" style="position:relative;cursor:pointer;" onclick="showChallengeDetail(${c.id})">
              <img src="${c.image}" alt="${c.titre}" class="challenge-image">
              <div class="challenge-overlay-stats" style="position:absolute;top:10px;left:10px;display:flex;gap:5px;z-index:2;">
                <span class="stat-item" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);color:#fff;padding:2px 8px;border-radius:20px;font-size:0.7rem;display:flex;align-items:center;gap:4px;">
                  <i class="lni lni-eye"></i> ${c.nb_vues || 0}
                </span>
                <span class="stat-item like-count-${c.id}" style="background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);color:#fff;padding:2px 8px;border-radius:20px;font-size:0.7rem;display:flex;align-items:center;gap:4px;">
                  <i class="lni lni-heart"></i> ${c.nb_likes || 0}
                </span>
              </div>
            </div>
          ` : ''}
          
          <div class="challenge-steaker">
            ${createSteakerHTML(c.steaker, niveau, 'small')}
          </div>
          
          <div class="challenge-badge ${statut}">
            ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
          </div>

          <div class="challenge-badge" style="top:46px;background:${paid ? '#f59e0b' : '#22c55e'};color:#07170d;">
            ${paid ? `💳 ${formatChallengePrice(c)}` : '🎁 Gratuit'}
          </div>
          
          <div class="challenge-content">
            <div class="challenge-header-row" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:10px;">
              <h3 class="challenge-title" style="margin:0;font-size:1.1rem;cursor:pointer;" onclick="showChallengeDetail(${c.id})">${c.titre}</h3>
              <div style="display:flex;align-items:center;gap:7px;flex-shrink:0;">
                <button class="btn-qr" title="QR code du défi"
                        style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.05);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s;"
                        onclick="event.stopPropagation(); showChallengeDetail(${c.id}); setTimeout(() => document.getElementById('challenge-qr-section')?.scrollIntoView({behavior:'smooth', block:'center'}), 80);">
                  ▦
                </button>
                <button class="btn-like ${c.is_liked ? 'active' : ''}" 
                        style="width:32px;height:32px;border-radius:50%;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.05);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all 0.3s;"
                        onclick="event.stopPropagation(); window.toggleLike(${c.id}, this)">
                  <i class="lni lni-heart${c.is_liked ? '-fill' : ''}"></i>
                </button>
              </div>
            </div>
            <p class="challenge-description">${c.description}</p>
            
            <div class="challenge-reward">
              <div class="reward-icon">
                ${createSteakerHTML(c.steaker, 'gold', 'small')}
              </div>
              <div class="reward-info">
                <div class="reward-label">Récompense à gagner</div>
                <div class="reward-name">Steaker 3D: ${c.steaker_nom}</div>
              </div>
            </div>

            <div class="challenge-stats">
              <div class="challenge-stat">
                <span>💳</span>
                <span>${paid ? formatChallengePrice(c) : 'Gratuit'}</span>
              </div>
              <div class="challenge-stat">
                <span>👥</span>
                <span class="challenge-participants-count">${c.participants_count} participants</span>
              </div>
              <div class="challenge-stat">
                <span>📅</span>
                <span>${dateLabel}</span>
              </div>
              <div class="challenge-stat">
                <span>⏳</span>
                <span>${daysLeftBadge}</span>
              </div>
            </div>
            
            <div class="progress-wrapper">
              <div class="progress-header">
                <span class="progress-label">Progression</span>
                <span class="progress-value" style="color: var(--progress-${progressColor})">${c.progression}%</span>
              </div>
              <div class="progress-bar-container" data-progress="${c.progression}">
                <div class="progress-bar-fill ${progressColor}" style="width: ${c.progression}%"></div>
              </div>
            </div>

            <button
              class="btn-participate ${canParticipate ? '' : 'is-disabled'}"
              data-challenge-id="${c.id}"
              data-challenge-status="${statut}"
            >
              Participer
            </button>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// CLASSEMENT
// ═══════════════════════════════════════════════════════════

async function loadParticipantsForRanking(challengeId = null) {
  const loader = document.getElementById('ranking-loader');
  if (loader) loader.style.display = 'flex';

  try {
    const url = challengeId 
      ? `${PARTICIPANTS_ENDPOINT}?id_challenge=${challengeId}`
      : PARTICIPANTS_ENDPOINT;

    const response = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    if (!response.ok) throw new Error('Erreur réseau');
    
    const data = await response.json();

    let rows = [];
    if (Array.isArray(data)) {
      rows = data;
    } else if (data && Array.isArray(data.participants)) {
      rows = data.participants;
    }

    allParticipants = rows.map(mapParticipantForRanking).filter(p => p.id > 0);
    window.allParticipants = allParticipants;
  } catch (err) {
    console.error('Erreur classement:', err);
    allParticipants = [];
    window.allParticipants = allParticipants;
  } finally {
    if (loader) loader.style.display = 'none';
    renderPodium();
    renderRanking();
    renderMyRank();
  }
}

function mapParticipantForRanking(p) {
  const id = parseInt(p.id_user || p.id || 0, 10);
  const nom = (p.nom || p.pseudo || 'Participant').toString().trim() || 'Participant';
  const objectif = Math.max(0, Math.min(100, parseInt(p.objectif || 0, 10) || 0));
  const engagementRaw = parseInt(p.engagement || 0, 10) || 0;
  const engagement = Math.max(0, Math.min(100, engagementRaw));
  const hasAction = (p.action || '').toString().trim().length > 0;
  const hasMotivation = (p.motivation || '').toString().trim().length >= 10;
  const notifications = parseInt(p.notifications || 0, 10) === 1 ? 1 : 0;
  const progression = Math.max(objectif, engagement);
  const bonus = (hasAction ? 10 : 0) + (hasMotivation ? 10 : 0) + (notifications ? 5 : 0);
  const points = Math.round(progression * 10 + engagement * 4 + objectif * 2 + bonus);
  const joinedAt = new Date(p.date_inscription || 0).getTime() || 0;
  const initials = nom.split(/\s+/).filter(Boolean).slice(0, 2).map(x => x[0]).join('').toUpperCase();
  return {
    id,
    nom,
    pseudo: p.pseudo || nom,
    email: (p.email || '').toString().trim().toLowerCase(),
    avatar: p.avatar || initials || '👤',
    progression,
    engagement,
    objectif,
    points,
    joinedAt,
    challengeId: parseInt(p.id_challenge || 0, 10) || 0,
    challengeTitre: p.challenge_titre || '',
    challengeIcon: p.challenge_icon || '🏆',
    action: p.action || '',
    motivation: p.motivation || '',
  };
}

function sortParticipantsForRanking(list, sortType) {
  const rows = Array.isArray(list) ? [...list] : [];
  return rows.sort((a, b) => {
    if (sortType === 'points') return (b.points - a.points) || (b.progression - a.progression) || a.nom.localeCompare(b.nom);
    if (sortType === 'engagement') return (b.engagement - a.engagement) || (b.points - a.points) || a.nom.localeCompare(b.nom);
    if (sortType === 'recent') return (b.joinedAt - a.joinedAt) || (b.points - a.points) || a.nom.localeCompare(b.nom);
    return (b.progression - a.progression) || (b.points - a.points) || a.nom.localeCompare(b.nom);
  });
}

/**
 * Participants d'un défi pour le chat (noms réels, sans email).
 * @param {number} challengeId
 * @returns {Promise<Array<{id:number,nom:string,id_challenge:number}>>}
 */
window.fetchParticipantsForChallenge = async function fetchParticipantsForChallenge(challengeId) {
  const id = parseInt(challengeId || 0, 10);
  if (!id) return [];
  try {
    const url = `${PARTICIPANTS_ENDPOINT}?ajax=1&id_challenge=${id}&t=${Date.now()}`;
    const response = await fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json'
      }
    });
    if (!response.ok) return [];
    const data = await response.json();
    let rows = [];
    if (Array.isArray(data)) rows = data;
    else if (data && Array.isArray(data.participants)) rows = data.participants;
    return rows.map(p => ({
      id: parseInt(p.id || p.id_user || 0, 10),
      nom: (p.nom || p.pseudo || 'Participant').toString().trim() || 'Participant',
      email: (p.email || '').toString().trim().toLowerCase(),
      id_challenge: parseInt(p.id_challenge || id, 10)
    }));
  } catch (e) {
    console.warn('fetchParticipantsForChallenge:', e);
    return [];
  }
};

function populateRankingSelect() {
  const select = document.getElementById('ranking-challenge-filter');
  if (!select || select.dataset.bound === 'true') return;

  const activeChallenges = allChallenges.filter(c => c.statut === 'actif');
  
  let html = '<option value="">Global</option>';
  activeChallenges.forEach(c => {
    html += `<option value="${c.id}">${c.titre}</option>`;
  });
  
  select.innerHTML = html;
  select.addEventListener('change', (e) => {
    loadParticipantsForRanking(e.target.value);
  });
  select.dataset.bound = 'true';
}

function renderPodium() {
  const podium = document.getElementById('ranking-podium');
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!podium) return;

  const sorted = sortParticipantsForRanking(allParticipants, sortType);
  const top3 = sorted.slice(0, 3);

  if (top3.length === 0) {
    podium.innerHTML = '<p style="color:var(--muted);text-align:center;width:100%;">Aucun participant</p>';
    return;
  }

  // Ordre visuel: 2nd (gauche), 1er (centre), 3ème (droite)
  const displayOrder = [];
  if (top3[1]) displayOrder.push({ ...top3[1], rank: 2 });
  if (top3[0]) displayOrder.push({ ...top3[0], rank: 1 });
  if (top3[2]) displayOrder.push({ ...top3[2], rank: 3 });

  podium.innerHTML = displayOrder.map(p => {
    const medal = p.rank === 1 ? '🥇' : p.rank === 2 ? '🥈' : '🥉';
    return `
      <div class="podium-item podium-rank-${p.rank}" onclick="showUserProfile(${p.id})">
        <div class="podium-avatar-wrapper">
          <div class="podium-avatar">${p.avatar}</div>
          <div class="podium-medal">${medal}</div>
        </div>
        <div class="podium-column">
          <span>${p.progression}%</span>
        </div>
        <div class="podium-name">${escapeHtml(p.pseudo)}</div>
        <div class="podium-pts">${p.points} pts · ${p.challengeIcon}</div>
      </div>
    `;
  }).join('');
}

function renderMyRank() {
  const myRankCard = document.getElementById('my-ranking-card');
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!myRankCard) return;
  
  const sorted = sortParticipantsForRanking(allParticipants, sortType);
  const myIndex = sorted.findIndex(p => p.id === currentUser.id);
  
  if (myIndex === -1) {
    myRankCard.style.display = 'none';
    return;
  }
  
  myRankCard.style.display = 'flex';
  const myData = sorted[myIndex];
  const rang = myIndex + 1;
  const niveau = getSteakerLevel(myData.progression);
  const progressColor = getProgressColor(myData.progression);
  
  myRankCard.innerHTML = `
    <div class="my-ranking-rank">#${rang}</div>
    <div class="my-ranking-details">
      <div class="my-ranking-name">${myData.avatar} ${escapeHtml(myData.pseudo)}</div>
      <div class="my-ranking-stats">
        <span>${myData.progression}%</span> • <span>${myData.points} pts</span> • <span>${escapeHtml(myData.challengeTitre || 'Défi')}</span>
      </div>
      <div class="ranking-progress-bar">
        <div class="ranking-progress-fill ${progressColor}" style="width: ${myData.progression}%"></div>
      </div>
    </div>
    <div class="ranking-steaker">
      ${createSteakerHTML('🌱', niveau, 'small')}
    </div>
  `;
}

function renderRanking() {
  const rankingList = document.getElementById('ranking-list');
  const sortType = document.getElementById('ranking-sort-filter')?.value || 'progression';
  if (!rankingList) return;
  
  const sorted = sortParticipantsForRanking(allParticipants, sortType);
  const others = sorted.slice(3); // À partir du rang 4

  if (others.length === 0 && sorted.length <= 3) {
    rankingList.innerHTML = '<p style="color:var(--muted);text-align:center;padding:20px;">Fin du classement</p>';
    return;
  }
  
  rankingList.innerHTML = others.map((p, index) => {
    const rang = index + 4;
    const isCurrentUser = p.id === currentUser.id;
    const niveau = getSteakerLevel(p.progression);
    const progressColor = getProgressColor(p.progression);
    
    return `
      <div class="ranking-item ${isCurrentUser ? 'current-user' : ''}" onclick="showUserProfile(${p.id})">
        <div class="ranking-position">
          ${rang}
        </div>
        
        <div class="ranking-info">
          <div class="ranking-name">${p.avatar} ${escapeHtml(p.pseudo)}</div>
          <div class="ranking-stats">
            <span>${p.progression}%</span> • <span>${p.points} pts</span> • <span>${escapeHtml(p.challengeTitre || 'Défi')}</span>
          </div>
          <div class="ranking-progress-bar">
            <div class="ranking-progress-fill ${progressColor}" style="width: ${p.progression}%"></div>
          </div>
        </div>
        
        <div class="ranking-steaker">
          ${createSteakerHTML('🌱', niveau, 'small')}
        </div>
      </div>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// MODAL DÉTAIL DÉFI
// ═══════════════════════════════════════════════════════════

function showChallengeDetail(challengeId) {
  const challenge = allChallenges.find(c => c.id === challengeId);
  const modal = document.getElementById('challenge-modal');
  const modalBody = document.getElementById('challenge-modal-body');
  
  if (!challenge || !modal || !modalBody) return;

  // Incrémenter les vues
  if (window.incrementVues) window.incrementVues(challengeId);

  const dateDebut = new Date(challenge.date_debut);
  const dateFin = new Date(challenge.date_fin);
  const niveau = getSteakerLevel(challenge.progression);
  const progressColor = getProgressColor(challenge.progression);
  const statut = normalizeChallengeStatus(challenge.statut);
  const isActif = statut === 'actif';
  const isTermine = statut === 'termine';
  const paid = isPaidChallenge(challenge);
  const joursRestants = Math.ceil((dateFin - new Date()) / (1000 * 60 * 60 * 24));

  modalBody.innerHTML = `
    <div class="gl-ch-detail">
      ${challenge.image ? `
        <div class="gl-ch-detail__media" style="position:relative;">
          <img class="gl-ch-detail__img" src="${challenge.image}" alt="${challenge.titre}" loading="lazy" decoding="async">
          <div class="gl-ch-detail__mediaShade" aria-hidden="true" style="position:absolute;inset:0;background:linear-gradient(to bottom, transparent, rgba(0,0,0,0.8));"></div>
          <div style="position:absolute;bottom:20px;left:20px;right:20px;display:flex;justify-content:space-between;align-items:flex-end;z-index:2;">
            <h2 style="color:#fff;font-size:1.8rem;margin:0;font-family:'Cormorant Garamond',serif;">${challenge.titre}</h2>
            <button class="btn-like ${challenge.is_liked ? 'active' : ''}" 
                    style="width:42px;height:42px;border-radius:50%;border:1px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.1);backdrop-filter:blur(10px);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;"
                    onclick="event.stopPropagation(); window.toggleLike(${challenge.id}, this)">
              <i class="lni lni-heart${challenge.is_liked ? '-fill' : ''}" style="font-size:1.3rem;"></i>
            </button>
          </div>
        </div>
        </div>
      ` : ''}

      <div class="gl-ch-detail__content">
        <div class="gl-ch-detail__header">
          <div class="gl-ch-detail__steaker">
            ${createSteakerHTML(challenge.steaker, niveau, 'large')}
          </div>
          <div class="gl-ch-detail__titleWrap">
            <h2 class="gl-ch-detail__title">${challenge.titre}</h2>
            <div class="challenge-badge ${statut} gl-ch-detail__badge">
              ${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}
            </div>
          </div>
        </div>

        <p class="gl-ch-detail__desc">${challenge.description}</p>

        <div class="gl-ch-detail__stats">
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">👥 Participants</div>
            <div class="gl-ch-stat__val">${challenge.participants_count}</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">🎯 Objectif</div>
            <div class="gl-ch-stat__val">${challenge.valeur_cible}%</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">📊 Progression</div>
            <div class="gl-ch-stat__val gl-ch-stat__val--${progressColor}">${challenge.progression}%</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">📅 ${isActif ? 'Jours restants' : 'Durée'}</div>
            <div class="gl-ch-stat__val">${isActif ? joursRestants : Math.ceil((dateFin - dateDebut) / (1000 * 60 * 60 * 24))}j</div>
          </div>
          <div class="gl-ch-stat">
            <div class="gl-ch-stat__label">💳 Accès</div>
            <div class="gl-ch-stat__val">${paid ? formatChallengePrice(challenge) : 'Gratuit'}</div>
          </div>
        </div>

        <div class="progress-wrapper gl-ch-detail__progress">
          <div class="progress-header">
            <span class="progress-label">Progression globale</span>
            <span class="progress-value" style="color:var(--progress-${progressColor})">${challenge.progression}%</span>
          </div>
          <div class="progress-bar-container" data-progress="${challenge.progression}" style="height:14px;">
            <div class="progress-bar-fill ${progressColor}" style="width:${challenge.progression}%"></div>
          </div>
        </div>

        <div class="gl-ch-detail__qr-section" id="challenge-qr-section">
          <div class="gl-ch-detail__qr-card">
            <div class="gl-ch-detail__qr-content">
              <div class="gl-ch-detail__qr-box">
                <img src="${getChallengeQrUrl(challenge.id)}" alt="QR code participation ${challenge.titre}" loading="lazy">
              </div>
              <div class="gl-ch-detail__qr-text">
                <div class="gl-ch-detail__qr-title">QR code de participation</div>
                <div class="gl-ch-detail__qr-sub">Scannez pour rejoindre directement ce défi.</div>
                <div class="gl-ch-detail__qr-link-wrapper">
                  <div class="gl-ch-detail__qr-link-field">
                    <span class="gl-ch-detail__qr-link-text">${getChallengeParticipationUrl(challenge.id)}</span>
                    <button type="button" class="gl-ch-detail__qr-copy-btn" onclick="copyChallengeQrLink(${challenge.id})" title="Copier le lien">
                      <i class="lni lni-clipboard"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="gl-ch-detail__qr-footer">
              <button type="button" class="gl-ch-detail__qr-action-btn" onclick="copyChallengeQrLink(${challenge.id})">
                <i class="lni lni-clipboard"></i> Copier le lien
              </button>
              <button type="button" class="gl-ch-detail__qr-action-btn" onclick="downloadChallengeQr(${challenge.id})">
                <i class="lni lni-download"></i> Télécharger QR
              </button>
            </div>
          </div>
        </div>

        <div class="gl-ch-detail__share-section">
          <div class="gl-ch-detail__share-header">
            <div class="gl-ch-detail__share-header-left">
              <div class="gl-ch-detail__share-icon-circle">
                <i class="lni lni-share"></i>
              </div>
              <div>
                <div class="gl-ch-detail__share-title">Partager le défi</div>
                <div class="gl-ch-detail__share-sub">Choisissez une plateforme</div>
              </div>
            </div>
            <button type="button" class="gl-ch-detail__chat-btn" onclick="openShareTypePicker('chat', ${challenge.id})">
              <i class="lni lni-comments"></i> Chat du défi
            </button>
          </div>
          
          <div class="gl-ch-detail__share-grid">
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('whatsapp', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon whatsapp">
                <i class="lni lni-whatsapp"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">WhatsApp</div>
            </div>
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('facebook', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon facebook">
                <i class="lni lni-facebook-original"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">Facebook</div>
            </div>
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('instagram', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon instagram">
                <i class="lni lni-instagram-original"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">Instagram</div>
            </div>
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('tiktok', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon tiktok">
                <i class="lni lni-tiktok"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">TikTok</div>
            </div>
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('x', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon x">
                <i class="lni lni-x-original"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">X</div>
            </div>
            <div class="gl-ch-detail__share-card" onclick="openShareTypePicker('linkedin', ${challenge.id})">
              <div class="gl-ch-detail__share-card-icon linkedin">
                <i class="lni lni-linkedin-original"></i>
              </div>
              <div class="gl-ch-detail__share-card-label">LinkedIn</div>
            </div>
          </div>

          <div class="gl-ch-detail__share-footer">
            <div class="gl-ch-detail__share-link-field">
              <span class="gl-ch-detail__share-link-text">${getChallengeShareUrl(challenge.id)}</span>
              <button type="button" class="gl-ch-detail__share-copy-btn" onclick="copyChallengeQrLink(${challenge.id})">
                <i class="lni lni-clipboard"></i> Copier
              </button>
            </div>
          </div>
        </div>

        <div class="gl-ch-detail__cta">
          ${isActif ? `
            <button onclick="openDrawer(${challenge.id}); closeChallengeModal();" class="btn-primary gl-ch-detail__btn">
              ${paid ? '💳 Payer et participer' : '✅ Participer à ce défi'}
            </button>
          ` : isTermine ? `
            <div class="gl-ch-detail__notice gl-ch-detail__notice--muted">
              📦 Ce défi est terminé
            </div>
          ` : `
            <div class="gl-ch-detail__notice gl-ch-detail__notice--info">
              🔜 Ce défi commence le ${dateDebut.toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})}
            </div>
          `}
        </div>
      </div>
    </div>
  `;

  modal.classList.add('active');
}

function closeChallengeModal() {
  const modal = document.getElementById('challenge-modal');
  if (modal) modal.classList.remove('active');
}

// ═══════════════════════════════════════════════════════════
// FORMULAIRE DE PARTICIPATION
// ═══════════════════════════════════════════════════════════

let currentChallengeId = null;
let activeCardId = null;

// Ouvrir le drawer
function openDrawer(challengeId) {
  const normalizedId = parseInt(challengeId, 10);
  const challenge = allChallenges.find(c => c.id === normalizedId);
  if (!challenge) return;

  // Retirer l'ancien highlight
  if (activeCardId) {
    const oldMain = document.querySelector(`.challenge-card[data-challenge-id="${activeCardId}"] .challenge-card-main`);
    if (oldMain) oldMain.classList.remove('card-active');
  }

  // Ajouter le highlight à la nouvelle carte
  activeCardId = normalizedId;
  const newMain = document.querySelector(`.challenge-card[data-challenge-id="${normalizedId}"] .challenge-card-main`);
  if (newMain) newMain.classList.add('card-active');

  const drawer = document.getElementById('participation-drawer');
  const summary = document.getElementById('drawer-challenge-summary');
  const formWrapper = document.getElementById('drawer-form-wrapper');

  if (!drawer || !summary || !formWrapper) return;

  currentChallengeId = normalizedId;

  // Remplir le résumé
  const niveau = getSteakerLevel(challenge.progression);
  const statut = normalizeChallengeStatus(challenge.statut);
  
  summary.style.backgroundImage = `url('${challenge.image || ''}')`;
  summary.innerHTML = `
    <div class="drawer-summary-overlay">
      <h3 class="drawer-summary-title">${challenge.titre}</h3>
      <div class="drawer-summary-meta">
        <span class="status-${statut}">${statut === 'actif' ? '✅ Actif' : statut === 'termine' ? '📦 Terminé' : '🔜 À venir'}</span>
        <span>👥 ${challenge.participants_count} inscrits</span>
        <span>🏆 ${challenge.steaker_nom}</span>
        <span>${isPaidChallenge(challenge) ? `💳 ${formatChallengePrice(challenge)}` : '🎁 Gratuit'}</span>
      </div>
    </div>
  `;

  // Remplir le formulaire
  formWrapper.innerHTML = getParticipationFormHTML(challenge);

  // Afficher le drawer
  drawer.setAttribute('aria-hidden', 'false');
  drawer.classList.add('is-open');
  document.body.style.overflow = 'hidden';
  
  // Focus sur le premier champ
  setTimeout(() => {
    const firstField = formWrapper.querySelector('input[name="nom"]');
    if (firstField) firstField.focus();
  }, 400);
}

// Fermer le drawer
function closeDrawer() {
  const drawer = document.getElementById('participation-drawer');
  if (drawer) {
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
  }
  document.body.style.overflow = '';

  // Retirer le highlight
  if (activeCardId) {
    const main = document.querySelector(`.challenge-card[data-challenge-id="${activeCardId}"] .challenge-card-main`);
    if (main) main.classList.remove('card-active');
    activeCardId = null;
  }
  
  currentChallengeId = null;
}

function getParticipationFormHTML(challenge) {
  const objectiveValue = Math.max(1, Math.min(100, parseInt(challenge.valeur_cible || 50, 10) || 50));
  const objectiveLabel = `${objectiveValue}%`;
  const paid = isPaidChallenge(challenge);

  return `
    <div class="drawer-form-content">
      <div id="inline-feedback-${challenge.id}" class="inline-form-feedback" aria-live="polite"></div>

      <div class="inline-form-feedback is-visible ${paid ? 'is-warning' : 'is-success'}" style="margin-bottom:16px;">
        ${paid
          ? `Ce défi est payant. Une modal de paiement simulé (${formatChallengePrice(challenge)}) s'ouvrira avant l'inscription.`
          : 'Ce défi est gratuit. Aucun paiement nécessaire.'}
      </div>

      <form id="inline-participation-form-${challenge.id}" onsubmit="window.handleParticipationSubmit(event, ${challenge.id})" novalidate>
        <div class="form-group">
          <label class="form-label">Nom complet <span class="required">*</span></label>
          <input type="text" name="nom" class="form-input" placeholder="Ex: Jean Dupont" value="${escapeHtml(currentUser.nom || '')}" required>
          <span class="error-msg" id="error-nom-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-input" placeholder="votre@email.com" value="${escapeHtml(currentUser.email || '')}" required>
          <span class="error-msg" id="error-email-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">
            Objectif personnel (%) <span class="required">*</span>
            <span class="char-count" id="objectif-label-${challenge.id}">${objectiveLabel}</span>
          </label>
          <div class="slider-container">
            <input
              type="range"
              name="objectif"
              class="form-slider"
              min="1"
              max="100"
              value="${objectiveValue}"
              required
              oninput="window.updateCharCount(this, 'objectif-value-${challenge.id}')"
            >
            <div class="slider-value" id="objectif-value-${challenge.id}">${objectiveLabel}</div>
          </div>
          <p class="form-help">Définissez votre objectif personnel pour ce défi.</p>
          <span class="error-msg" id="error-objectif-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">
            Motivation <span class="required">*</span>
            <span class="char-count" id="motivation-count-${challenge.id}">0/500</span>
          </label>
          <textarea
            name="motivation"
            class="form-textarea"
            placeholder="Pourquoi souhaitez-vous participer à ce défi ?"
            maxlength="500"
            rows="4"
            required
            oninput="window.updateCharCount(this, 'motivation-count-${challenge.id}')"
          ></textarea>
          <span class="error-msg" id="error-motivation-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-label">Première action concrète <span class="required">*</span></label>
          <textarea
            name="action"
            class="form-textarea"
            placeholder="Quelle sera votre première action pour commencer ?"
            rows="3"
            required
          ></textarea>
          <span class="error-msg" id="error-action-${challenge.id}"></span>
        </div>

        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="engagement" required>
            <span class="checkbox-label">Je m'engage à participer activement à ce défi.</span>
          </label>
        </div>

        <div class="form-group">
          <label class="form-checkbox">
            <input type="checkbox" name="notifications">
            <span class="checkbox-label">Recevoir des notifications de motivation.</span>
          </label>
        </div>

        <div class="form-actions" style="position: sticky; bottom: -24px; background: var(--surface); margin: 32px -24px -24px; padding: 20px 24px; border-top: 1px solid rgba(91,62,150,0.2);">
          <button type="button" class="btn-secondary" onclick="window.closeDrawer()" style="flex: 1;">Fermer</button>
          <button type="submit" class="btn-primary" style="flex: 2;">
            <span class="participation-submit-text">${paid ? `Payer ${formatChallengePrice(challenge)} et participer` : 'Confirmer ma participation'}</span>
          </button>
        </div>
      </form>
    </div>
  `;
}

function setInlineFormFeedback(challengeId, message = '', type = '') {
  const feedback = document.getElementById(`inline-feedback-${challengeId}`);
  if (!feedback) return;

  feedback.textContent = message;
  feedback.className = 'inline-form-feedback';

  if (!message) return;

  feedback.classList.add('is-visible');
  if (type) feedback.classList.add(`is-${type}`);
}

function clearInlineValidation(challengeId) {
  const form = document.getElementById(`inline-participation-form-${challengeId}`);
  if (!form) return;

  form.querySelectorAll('.error-msg').forEach(node => {
    node.textContent = '';
  });
  form.querySelectorAll('.invalid').forEach(field => {
    field.classList.remove('invalid');
  });
  setInlineFormFeedback(challengeId);
}

function showInlineFieldError(challengeId, fieldName, message) {
  const errorNode = document.getElementById(`error-${fieldName}-${challengeId}`);
  const form = document.getElementById(`inline-participation-form-${challengeId}`);
  const field = form?.querySelector(`[name="${fieldName}"]`);

  if (errorNode) errorNode.textContent = message;
  if (field) field.classList.add('invalid');
}

function updateCharCount(source, outputId) {
  if (typeof source === 'string') {
    const output = document.getElementById(source);
    if (output) output.textContent = outputId;
    return;
  }

  const field = source;
  const output = document.getElementById(outputId);
  if (!field || !output) return;

  if (field.type === 'range') {
    const value = `${field.value}%`;
    output.textContent = value;
    const labelId = outputId.replace('objectif-value-', 'objectif-label-');
    const label = document.getElementById(labelId);
    if (label) label.textContent = value;
    return;
  }

  const length = field.value.length;
  output.textContent = `${length}/500`;
  output.style.color = length >= 10 ? 'var(--success)' : 'var(--muted)';
}

function updateChallengeParticipationCount(challengeId, count) {
  const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
  const countNode = card?.querySelector('.challenge-participants-count');
  if (countNode) {
    countNode.textContent = `${count} participants`;
  }
}

function openPaymentModal(challenge, data, onConfirm, onCancel) {
  const existing = document.getElementById('gl-payment-modal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'gl-payment-modal';
  const paymentMethods = [
    { id: 'card', label: 'Carte', icon: '💳', hint: 'Visa, Mastercard' },
    { id: 'apple_pay', label: 'Apple Pay', icon: '', hint: 'Paiement rapide' },
    { id: 'google_pay', label: 'Google Pay', icon: 'G', hint: 'Wallet Google' },
    { id: 'paypal', label: 'PayPal', icon: 'P', hint: 'Compte PayPal' },
    { id: 'flouci', label: 'Flouci', icon: 'F', hint: 'Wallet Tunisie' },
    { id: 'd17', label: 'D17', icon: 'D17', hint: 'Paiement mobile' }
  ];
  modal.style.cssText = 'position:fixed;inset:0;z-index:30000;background:rgba(0,0,0,.82);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;padding:18px;';
  modal.innerHTML = `
    <div style="width:min(560px,100%);max-height:calc(100vh - 36px);overflow:auto;background:#151827;border:1px solid #2b3148;border-radius:18px;box-shadow:0 24px 80px rgba(0,0,0,.65);color:#f8fafc;font-family:Inter,Arial,sans-serif;">
      <div style="padding:20px 22px;border-bottom:1px solid #2b3148;display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <div>
          <div style="font-size:10px;color:#8f9bb7;text-transform:uppercase;font-weight:900;letter-spacing:.14em;">Paiement sécurisé</div>
          <h3 style="margin:4px 0 0;font-size:1.25rem;">${escapeHtml(challenge.titre)}</h3>
        </div>
        <button type="button" id="gl-payment-close" style="width:34px;height:34px;border-radius:8px;border:1px solid #56617d;background:#101322;color:#fff;cursor:pointer;">×</button>
      </div>
      <div style="padding:22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;background:#222943;border:1px solid #48548a;border-radius:12px;padding:14px 16px;margin-bottom:18px;">
          <span style="color:#8f9bb7;font-size:12px;font-weight:800;">Montant à payer<br><small>TTC · Paiement unique</small></span>
          <strong style="font-size:1.35rem;color:#8ea2ff;">${formatChallengePrice(challenge)}</strong>
        </div>
        <div style="font-size:11px;color:#8f9bb7;text-transform:uppercase;font-weight:900;letter-spacing:.12em;margin-bottom:8px;">Mode de paiement</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(125px,1fr));gap:10px;margin-bottom:16px;">
          ${paymentMethods.map((m, i) => `
            <button type="button" class="gl-pay-method" data-method="${m.id}" style="text-align:center;padding:12px;border-radius:8px;border:1px solid ${i === 0 ? '#5b6cff' : '#46516b'};background:${i === 0 ? '#202946' : '#161b2b'};color:inherit;cursor:pointer;">
              <div style="font-size:17px;font-weight:900;color:${i === 0 ? '#8ea2ff' : '#f8fafc'};">${m.icon}</div>
              <div style="font-weight:900;font-size:12px;margin-top:4px;">${m.label}</div>
              <div style="font-size:9px;color:#8f9bb7;margin-top:2px;">${m.hint}</div>
            </button>
          `).join('')}
        </div>
        <div id="gl-card-preview" style="background:linear-gradient(135deg,#273066,#1f2450);border-radius:14px;padding:18px;margin-bottom:14px;min-height:120px;box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="width:34px;height:24px;border-radius:5px;background:#f5c542;display:inline-block;"></span>
            <span style="width:42px;height:28px;border-radius:999px;background:linear-gradient(90deg,#ef4444 0 50%,#f59e0b 50%);display:inline-block;"></span>
          </div>
          <div id="gl-card-preview-number" style="letter-spacing:.18em;font-size:18px;font-weight:800;margin-top:24px;">•••• •••• •••• ••••</div>
          <div style="display:flex;justify-content:space-between;margin-top:18px;font-size:11px;color:#cbd5e1;text-transform:uppercase;">
            <span>Titulaire<br><strong id="gl-card-preview-name" style="color:#fff;">${escapeHtml(data.nom || 'Participant')}</strong></span>
            <span>Expire<br><strong id="gl-card-preview-exp" style="color:#fff;">--/--</strong></span>
          </div>
        </div>
        <div id="gl-payment-card-fields" style="display:grid;gap:12px;">
          <label style="display:grid;gap:6px;font-size:11px;color:#8f9bb7;text-transform:uppercase;font-weight:900;">Nom sur la carte
            <input id="gl-pay-name" value="${escapeHtml(data.nom)}" style="padding:11px 12px;border-radius:7px;border:1px solid #555f78;background:#2b2c25;color:#f8fafc;">
          </label>
          <label style="display:grid;gap:6px;font-size:11px;color:#8f9bb7;text-transform:uppercase;font-weight:900;">Numéro de carte
            <input id="gl-pay-card" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" style="padding:11px 12px;border-radius:7px;border:1px solid #3d465f;background:#22293a;color:#f8fafc;">
          </label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <label style="display:grid;gap:6px;font-size:11px;color:#8f9bb7;text-transform:uppercase;font-weight:900;">Date d'expiration
              <input id="gl-pay-exp" placeholder="12/29" maxlength="5" style="padding:11px 12px;border-radius:7px;border:1px solid #3d465f;background:#22293a;color:#f8fafc;">
            </label>
            <label style="display:grid;gap:6px;font-size:11px;color:#8f9bb7;text-transform:uppercase;font-weight:900;">Code CVC
              <input id="gl-pay-cvc" inputmode="numeric" maxlength="4" placeholder="•••" style="padding:11px 12px;border-radius:7px;border:1px solid #3d465f;background:#22293a;color:#f8fafc;">
            </label>
          </div>
        </div>
        <div id="gl-payment-wallet-fields" style="display:none;background:#20263a;border:1px solid #46516b;border-radius:12px;padding:16px;line-height:1.45;">
          <strong id="gl-wallet-title">Apple Pay</strong>
          <div id="gl-wallet-help" style="color:#8f9bb7;font-size:13px;margin-top:4px;">Confirmez le paiement simulé pour continuer.</div>
        </div>
        <div id="gl-payment-error" style="min-height:18px;margin-top:10px;color:#ef4444;font-size:13px;"></div>
        <div style="display:flex;gap:10px;margin-top:18px;">
          <button type="button" id="gl-payment-cancel" style="flex:1;border:1px solid #56617d;background:#111524;color:#fff;border-radius:7px;padding:11px;font-weight:800;cursor:pointer;">Annuler</button>
          <button type="button" id="gl-payment-confirm" style="flex:2;border:1px solid #6574ff;background:#111524;color:#fff;border-radius:7px;padding:11px;font-weight:900;cursor:pointer;">Payer ${formatChallengePrice(challenge)}</button>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  const close = () => {
    modal.remove();
    if (typeof onCancel === 'function') onCancel();
  };
  modal.querySelector('#gl-payment-close').onclick = close;
  modal.querySelector('#gl-payment-cancel').onclick = close;
  let selectedMethod = 'card';
  const cardFields = modal.querySelector('#gl-payment-card-fields');
  const walletFields = modal.querySelector('#gl-payment-wallet-fields');
  const walletTitle = modal.querySelector('#gl-wallet-title');
  const walletHelp = modal.querySelector('#gl-wallet-help');
  modal.querySelectorAll('.gl-pay-method').forEach(btn => {
    btn.onclick = () => {
      selectedMethod = btn.dataset.method || 'card';
      modal.querySelectorAll('.gl-pay-method').forEach(x => {
        x.style.borderColor = '#46516b';
        x.style.background = '#161b2b';
      });
      btn.style.borderColor = '#5b6cff';
      btn.style.background = '#202946';
      const meta = paymentMethods.find(x => x.id === selectedMethod) || paymentMethods[0];
      if (selectedMethod === 'card') {
        cardFields.style.display = 'grid';
        const preview = modal.querySelector('#gl-card-preview');
        if (preview) preview.style.display = 'block';
        walletFields.style.display = 'none';
      } else {
        cardFields.style.display = 'none';
        const preview = modal.querySelector('#gl-card-preview');
        if (preview) preview.style.display = 'none';
        walletFields.style.display = 'block';
        walletTitle.textContent = meta.label;
        walletHelp.textContent = `Vous allez confirmer un paiement simulé via ${meta.label}.`;
      }
    };
  });
  const nameInput = modal.querySelector('#gl-pay-name');
  const cardInput = modal.querySelector('#gl-pay-card');
  const expInput = modal.querySelector('#gl-pay-exp');
  if (nameInput) nameInput.oninput = () => {
    const node = modal.querySelector('#gl-card-preview-name');
    if (node) node.textContent = nameInput.value.trim() || data.nom || 'Participant';
  };
  if (cardInput) cardInput.oninput = () => {
    const raw = cardInput.value.replace(/\D/g, '').slice(0, 16);
    cardInput.value = raw.replace(/(.{4})/g, '$1 ').trim();
    const node = modal.querySelector('#gl-card-preview-number');
    if (node) node.textContent = raw ? raw.padEnd(16, '•').replace(/(.{4})/g, '$1 ').trim() : '•••• •••• •••• ••••';
  };
  if (expInput) expInput.oninput = () => {
    let raw = expInput.value.replace(/\D/g, '').slice(0, 4);
    if (raw.length > 2) raw = raw.slice(0, 2) + '/' + raw.slice(2);
    expInput.value = raw;
    const node = modal.querySelector('#gl-card-preview-exp');
    if (node) node.textContent = raw || '--/--';
  };
  modal.querySelector('#gl-payment-confirm').onclick = () => {
    const err = modal.querySelector('#gl-payment-error');
    if (selectedMethod === 'card') {
      const card = modal.querySelector('#gl-pay-card').value.replace(/\s+/g, '');
      const exp = modal.querySelector('#gl-pay-exp').value.trim();
      const cvc = modal.querySelector('#gl-pay-cvc').value.trim();
      if (card.length < 12 || !/^\d+$/.test(card)) {
        err.textContent = 'Numéro de carte invalide.';
        return;
      }
      if (!/^\d{2}\/\d{2}$/.test(exp)) {
        err.textContent = 'Expiration invalide. Exemple: 12/29.';
        return;
      }
      if (!/^\d{3,4}$/.test(cvc)) {
        err.textContent = 'CVC invalide.';
        return;
      }
    }
    modal.querySelector('#gl-payment-confirm').disabled = true;
    modal.querySelector('#gl-payment-confirm').textContent = 'Paiement...';
    fetch(PAYMENT_ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({
        id_challenge: challenge.id,
        nom: data.nom,
        email: data.email,
        methode: selectedMethod
      })
    })
      .then(r => r.json())
      .then(result => {
        if (!result || !result.success || !result.payment?.reference) {
          throw new Error(result?.message || 'Paiement refusé.');
        }
        modal.remove();
        onConfirm({
          paiement_reference: result.payment.reference,
          paiement_methode: result.payment.methode,
          paiement_montant: result.payment.montant
        });
      })
      .catch(e => {
        err.textContent = e.message || 'Paiement impossible.';
        modal.querySelector('#gl-payment-confirm').disabled = false;
        modal.querySelector('#gl-payment-confirm').textContent = `Payer ${formatChallengePrice(challenge)}`;
      });
  };
}

function submitParticipationData(normalizedId, challenge, data, btnSubmit, submitText, paidLabel) {
  btnSubmit.disabled = true;
  submitText.textContent = 'Envoi...';

  fetch(ADD_PARTICIPANT_ENDPOINT, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ ...data, user_id: currentUser.id || 0 })
  })
    .then(r => r.json())
    .then(result => {
      if (!result || !result.success) {
        setInlineFormFeedback(normalizedId, result?.message || 'Erreur lors de l’inscription.', 'error');
        showToast(result?.message || 'Erreur lors de l’inscription', 'error');
        btnSubmit.disabled = false;
        submitText.textContent = paidLabel;
        return;
      }

      challenge.participants_count = parseInt(challenge.participants_count || 0, 10) + 1;
      updateChallengeParticipationCount(normalizedId, challenge.participants_count);
      const paymentRef = result.paiement?.reference ? ` Paiement: ${result.paiement.reference}.` : '';
      setInlineFormFeedback(normalizedId, `Participation confirmée pour "${challenge.titre}".${paymentRef}`, 'success');
      
      showToast(
        `Félicitations !`,
        `Votre participation au défi "${challenge.titre}" a été enregistrée.`,
        'success',
        challenge.streak_icon || '🏆'
      );
      window.addNotification(`Vous avez rejoint le défi "${challenge.titre}" !`, challenge.streak_icon || '🏆');

      markChallengeJoined(normalizedId);
      const form = document.getElementById(`inline-participation-form-${normalizedId}`);
      if (form) {
        form.reset();
        const motivationField = form.querySelector('[name="motivation"]');
        const objectifField = form.querySelector('[name="objectif"]');
        if (motivationField) updateCharCount(motivationField, `motivation-count-${normalizedId}`);
        if (objectifField) updateCharCount(objectifField, `objectif-value-${normalizedId}`);
      }

      setTimeout(() => {
        closeDrawer();
        filterChallenges();
      }, 900);
    })
    .catch(() => {
      setInlineFormFeedback(normalizedId, 'Une erreur réseau est survenue. Réessayez dans un instant.', 'error');
      showToast('Une erreur réseau est survenue', 'error');
      btnSubmit.disabled = false;
      submitText.textContent = paidLabel;
    });
}

function handleParticipationSubmit(event, challengeId = currentChallengeId) {
  event.preventDefault();

  const normalizedId = parseInt(challengeId, 10);
  const form = event.target;
  const challenge = allChallenges.find(c => c.id === normalizedId);
  const btnSubmit = form.querySelector('button[type="submit"]');
  const submitText = form.querySelector('.participation-submit-text');
  if (!challenge || !btnSubmit || !submitText) return;
  if (btnSubmit.disabled) return;

  clearInlineValidation(normalizedId);

  const formData = new FormData(form);
  const data = {
    id_challenge: normalizedId,
    nom: (formData.get('nom') || '').toString().trim(),
    email: (formData.get('email') || '').toString().trim(),
    objectif: parseInt((formData.get('objectif') || '0').toString(), 10),
    motivation: (formData.get('motivation') || '').toString().trim(),
    action: (formData.get('action') || '').toString().trim(),
    engagement: formData.get('engagement') === 'on' ? 1 : 0,
    notifications: formData.get('notifications') === 'on' ? 1 : 0
  };

  let hasErrors = false;

  if (data.nom.length < 2) {
    showInlineFieldError(normalizedId, 'nom', 'Le nom doit contenir au moins 2 caractères.');
    hasErrors = true;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
    showInlineFieldError(normalizedId, 'email', 'Veuillez saisir un email valide.');
    hasErrors = true;
  }
  if (!data.objectif || data.objectif < 1 || data.objectif > 100) {
    showInlineFieldError(normalizedId, 'objectif', 'L’objectif doit être compris entre 1 et 100%.');
    hasErrors = true;
  }
  if (data.motivation.length < 10) {
    showInlineFieldError(normalizedId, 'motivation', 'La motivation doit contenir au moins 10 caractères.');
    hasErrors = true;
  }
  if (data.action.length < 5) {
    showInlineFieldError(normalizedId, 'action', 'Décrivez une action concrète en au moins 5 caractères.');
    hasErrors = true;
  }
  if (!data.engagement) {
    setInlineFormFeedback(normalizedId, 'Vous devez confirmer votre engagement avant de participer.', 'error');
    hasErrors = true;
  }

  if (hasErrors) {
    showToast('Merci de corriger les champs indiqués.', 'error');
    return;
  }

  const paidLabel = isPaidChallenge(challenge)
    ? `Payer ${formatChallengePrice(challenge)} et participer`
    : 'Confirmer ma participation';

  if (isPaidChallenge(challenge)) {
    openPaymentModal(
      challenge,
      data,
      (paymentData) => submitParticipationData(normalizedId, challenge, { ...data, ...paymentData }, btnSubmit, submitText, paidLabel),
      () => {
        btnSubmit.disabled = false;
        submitText.textContent = paidLabel;
      }
    );
    return;
  }

  submitParticipationData(normalizedId, challenge, data, btnSubmit, submitText, paidLabel);
}

function markChallengeJoined(challengeId) {
  const btn = document.querySelector(`.btn-participate[data-challenge-id="${challengeId}"]`);
  if (!btn) return;
  btn.disabled = true;
  btn.innerHTML = '✅ Inscrit !';
  btn.classList.add('gl-btn--joined');
}

function handleJoinChallenge(btn, challengeId) {
  const originalHTML = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="gl-spinner"></span> Inscription...`;
  try {
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = originalHTML;
      openDrawer(challengeId);
    }, 350);
  } catch (e) {
    btn.disabled = false;
    btn.innerHTML = originalHTML;
  }
}

// ═══════════════════════════════════════════════════════════
// TOAST & NOTIFICATION SYSTEM
// ═══════════════════════════════════════════════════════════
window.notifications = [];

window.addNotification = function(text, icon = '🔔') {
  const notif = {
    id: Date.now(),
    text,
    icon,
    time: 'À l\'instant',
    unread: true
  };
  window.notifications.unshift(notif);
  updateNotifUI();
};

window.clearNotifications = function() {
  window.notifications = [];
  updateNotifUI();
};

function updateNotifUI() {
  const panel = document.getElementById('gl-notif-panel');
  const list = document.getElementById('gl-notif-list');
  const badge = document.getElementById('gl-notif-count');
  
  if (!list || !badge) return;

  const unreadCount = window.notifications.filter(n => n.unread).length;
  badge.textContent = unreadCount;
  badge.style.display = unreadCount > 0 ? 'flex' : 'none';

  if (window.notifications.length === 0) {
    list.innerHTML = '<div class="gl-notif-empty">Aucune nouvelle notification</div>';
    return;
  }

  list.innerHTML = window.notifications.map(n => `
    <div class="gl-notif-item ${n.unread ? 'gl-notif-item--unread' : ''}" onclick="this.classList.remove('gl-notif-item--unread')">
      <div class="gl-notif-item__icon">${n.icon}</div>
      <div class="gl-notif-item__content">
        <div class="gl-notif-item__text">${n.text}</div>
        <div class="gl-notif-item__time">${n.time}</div>
      </div>
    </div>
  `).join('');
}

function showToast(title, message = '', type = 'success', customIcon = null) {
  const cfg = {
    success: { color: '#22c55e', icon: 'lni lni-checkmark-circle' },
    error:   { color: '#ef4444', icon: 'lni lni-close' },
    warning: { color: '#f59e0b', icon: 'lni lni-warning' },
    info:    { color: '#3b82f6', icon: 'lni lni-bubble' },
  };
  const c = cfg[type] || cfg.info;
  const iconHtml = customIcon 
    ? `<span style="font-size:1.4rem;flex-shrink:0;">${customIcon}</span>`
    : `<span style="font-size:1.2rem;flex-shrink:0;margin-top:1px;color:${c.color};"><i class="${c.icon}"></i></span>`;

  let container = document.getElementById('gl-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'gl-toast-container';
    container.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;`;
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.style.cssText = `
    background:#1e1e2e; color:#e2e8f0; padding:14px 18px;
    border-radius:12px; border-left:4px solid ${c.color};
    box-shadow:0 4px 20px rgba(0,0,0,0.5); font-size:13px;
    display:flex; align-items:flex-start; gap:12px; min-width:280px; max-width:360px;
    pointer-events:all; position:relative; overflow:hidden;
    animation:glToastIn .3s cubic-bezier(.34,1.56,.64,1);
  `;
  toast.innerHTML = `
    <style>
      @keyframes glToastIn  { from{opacity:0;transform:translateX(100%)} to{opacity:1;transform:translateX(0)} }
      @keyframes glToastOut { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(100%)} }
    </style>
    ${iconHtml}
    <div style="flex:1;">
      <div style="font-weight:700;margin-bottom:2px;">${title}</div>
      ${message ? `<div style="color:#94a3b8;font-size:12px;line-height:1.4;">${message}</div>` : ''}
    </div>
    <button onclick="this.closest('[style]').remove()" style="background:none;border:none;color:#6b7280;cursor:pointer;font-size:1rem;padding:0;margin-top:1px;flex-shrink:0;">✕</button>
  `;

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.animation = 'glToastOut .3s ease forwards';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// ═══════════════════════════════════════════════════════════
// NAVIGATION
// ═══════════════════════════════════════════════════════════

function showMyChallenge() {
  showToast('📊 Page "Mon Défi" en cours de développement...', 'success');
  // TODO: Implémenter la page Mon Défi
}

function showCollection() {
  showToast('🏆 Page "Collection" en cours de développement...', 'success');
  // TODO: Implémenter la page Collection
}

function showProfile() {
  showToast('👤 Page "Profil" en cours de développement...', 'success');
  // TODO: Implémenter la page Profil
}

function showUserProfile(userId) {
  const user = allParticipants.find(p => p.id === userId);
  const modal = document.getElementById('user-profile-modal');
  const modalBody = document.getElementById('user-profile-body');
  
  if (!user || !modal || !modalBody) return;

  // Simulation de collection de steakers pour l'utilisateur
  const userSteakers = ['🌱', '💧', '🥗', '🚲', '⚡', '♻️'].slice(0, Math.floor(user.progression / 15) + 1);

  modalBody.innerHTML = `
    <div class="profile-header">
      <div class="profile-avatar">${user.avatar}</div>
      <h2 class="profile-name">${user.nom}</h2>
      <div class="profile-pseudo">@${user.pseudo}</div>
    </div>
    
    <div class="profile-stats-grid">
      <div class="profile-stat-card">
        <div class="profile-stat-value">${user.progression}%</div>
        <div class="profile-stat-label">Progression</div>
      </div>
      <div class="profile-stat-card">
        <div class="profile-stat-value">${user.points}</div>
        <div class="profile-stat-label">Points</div>
      </div>
    </div>
    
    <div class="profile-collection">
      <h3 class="collection-title">🏆 Collection de Steakers</h3>
      <div class="collection-grid">
        ${userSteakers.map(s => `
          <div class="collection-item" title="Steaker débloqué">
            ${s}
          </div>
        `).join('')}
        ${Array(6 - userSteakers.length).fill(0).map(() => `
          <div class="collection-item" style="opacity: 0.2; filter: grayscale(1)" title="Verrouillé">
            ❓
          </div>
        `).join('')}
      </div>
    </div>

    <div style="padding: 24px; text-align: center;">
      <button class="btn-primary" style="width: 100%;" onclick="showToast('Message envoyé à ${user.pseudo}!', 'success')">
        💬 Envoyer un message
      </button>
    </div>
  `;

  modal.classList.add('active');
}

function closeUserProfileModal() {
  const modal = document.getElementById('user-profile-modal');
  if (modal) modal.classList.remove('active');
}

// ═══════════════════════════════════════════════════════════
// MÉTIER : VUES & LIKES
// ═══════════════════════════════════════════════════════════

window.incrementVues = function(challengeId) {
  const endpoint = getBackendPath('listChallenges.php?action=incrementVues');
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id: challengeId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const c = allChallenges.find(ch => ch.id === parseInt(challengeId));
      if (c) {
        c.nb_vues = (parseInt(c.nb_vues) || 0) + 1;
        const card = document.querySelector(`.challenge-card[data-challenge-id="${challengeId}"]`);
        if (card) {
          const vueEl = card.querySelector('.lni-eye')?.parentElement;
          if (vueEl) vueEl.innerHTML = `<i class="lni lni-eye"></i> ${c.nb_vues}`;
        }
      }
    }
  })
  .catch(err => console.warn('Erreur incrementVues:', err));
};

window.toggleLike = function(challengeId, btn) {
  const icon = btn.querySelector('i');
  const endpoint = getBackendPath('listChallenges.php?action=toggleLike');
  
  fetch(endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    body: JSON.stringify({ id_challenge: challengeId, user_id: currentUser.id || 0 })
  })
  .then(r => r.json())
  .then(data => {
    if (data.liked !== undefined) {
      const c = allChallenges.find(ch => ch.id === parseInt(challengeId));
      if (c) {
        c.is_liked = data.liked;
        c.nb_likes = data.count;
      }
      btn.classList.toggle('active', data.liked);
      if (icon) icon.className = `lni lni-heart${data.liked ? '-fill' : ''}`;
      document.querySelectorAll(`.like-count-${challengeId}`).forEach(el => {
        el.innerHTML = `<i class="lni lni-heart"></i> ${data.count}`;
      });
      
      if (window.showToast) {
        showToast(
          data.liked ? 'Coup de cœur !' : 'Favoris mis à jour',
          data.liked ? `Défi "${c.titre}" ajouté à vos favoris` : `Défi "${c.titre}" retiré de vos favoris`,
          'info',
          data.liked ? '❤️' : '💔'
        );
      }
      window.addNotification(
        data.liked ? `Vous avez aimé le défi "${c.titre}"` : `Vous avez retiré votre like du défi "${c.titre}"`,
        data.liked ? '❤️' : '💔'
      );
    }
  })
  .catch(err => {
    console.error('Erreur toggleLike:', err);
    if (window.showToast) window.showToast('Erreur lors du like. Êtes-vous connecté ?', 'error');
  });
};

// ═══════════════════════════════════════════════════════════
// INITIALISATION
// ═══════════════════════════════════════════════════════════

function setupNotifEvents() {
  const trigger = document.getElementById('gl-notif-trigger');
  const panel = document.getElementById('gl-notif-panel');
  
  if (trigger && panel) {
    trigger.onclick = (e) => {
      e.stopPropagation();
      panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
      if (panel.style.display === 'block') {
        window.notifications.forEach(n => n.unread = false);
        updateNotifUI();
      }
    };
    
    document.addEventListener('click', (e) => {
      if (!panel.contains(e.target) && e.target !== trigger) {
        panel.style.display = 'none';
      }
    });
  }
}

function initChallenges() {
  const section = document.getElementById('challenges');
  if (!section) return;

  console.log('🎯 Initialisation du module Défis...');
  setupNotifEvents();
  
  // Event listeners
  const searchInput = document.getElementById('challenge-search');
  const statusFilter = document.getElementById('challenge-status-filter');
  const sortFilter = document.getElementById('challenge-sort-filter');
  const rankSortFilter = document.getElementById('ranking-sort-filter');
  const refreshBtn = document.getElementById('challenge-refresh');
  const grid = document.getElementById('challenges-grid');
  
  const btnPrev = document.getElementById('prev-page');
  const btnNext = document.getElementById('next-page');

  if (btnPrev && btnPrev.dataset.bound !== 'true') {
    btnPrev.onclick = () => {
      if (currentPage > 1) {
        currentPage--;
        renderChallenges(filteredChallenges.length ? filteredChallenges : allChallenges);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
    btnPrev.dataset.bound = 'true';
  }
  if (btnNext && btnNext.dataset.bound !== 'true') {
    btnNext.onclick = () => {
      const list = filteredChallenges.length ? filteredChallenges : allChallenges;
      const totalPages = Math.ceil(list.length / itemsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderChallenges(list);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };
    btnNext.dataset.bound = 'true';
  }

  if (searchInput && searchInput.dataset.bound !== 'true') {
    searchInput.addEventListener('input', filterChallenges);
    searchInput.dataset.bound = 'true';
  }
  if (statusFilter && statusFilter.dataset.bound !== 'true') {
    statusFilter.addEventListener('change', filterChallenges);
    statusFilter.dataset.bound = 'true';
  }
  if (sortFilter && sortFilter.dataset.bound !== 'true') {
    sortFilter.addEventListener('change', filterChallenges);
    sortFilter.dataset.bound = 'true';
  }
  if (rankSortFilter && rankSortFilter.dataset.bound !== 'true') {
    rankSortFilter.addEventListener('change', () => {
      renderPodium();
      renderRanking();
      renderMyRank();
    });
    rankSortFilter.dataset.bound = 'true';
  }
  if (refreshBtn && refreshBtn.dataset.bound !== 'true') {
    refreshBtn.addEventListener('click', loadChallenges);
    refreshBtn.dataset.bound = 'true';
  }
  if (grid && grid.dataset.bound !== 'true') {
    grid.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-participate');
      if (btn) {
        e.preventDefault();

        const id = parseInt(btn.getAttribute('data-challenge-id') || '0', 10);
        if (!id) return;

        const statut = normalizeChallengeStatus(btn.getAttribute('data-challenge-status'));
        if (statut !== 'actif') {
          showToast(statut === 'termine' ? 'Ce défi est terminé' : 'Ce défi n\'est pas encore disponible', 'error');
          return;
        }

        handleJoinChallenge(btn, id);
        return;
      }

      if (e.target.closest('.drawer-panel')) return;

      const card = e.target.closest('.challenge-card-main');
      if (!card) return;

      const challengeCard = card.closest('.challenge-card');
      const id = parseInt(challengeCard?.getAttribute('data-challenge-id') || '0', 10);
      if (!id) return;

      showChallengeDetail(id);
    });
    grid.dataset.bound = 'true';
  }

  // Quand on repasse en grille/liste, masquer la vue swipe
  const btnGrid = document.getElementById('view-grid');
  if (btnGrid && btnGrid.dataset.boundSwipe !== 'true') {
    btnGrid.addEventListener('click', () => setChallengesView('grid'));
    btnGrid.dataset.boundSwipe = 'true';
  }

  // Vue swipe (Tinder) + fallback
  const btnSwipe = document.getElementById('view-swipe');
  if (btnSwipe && btnSwipe.dataset.bound !== 'true') {
    btnSwipe.addEventListener('click', () => {
      setChallengesView('swipe');
      filterChallenges();
    });
    btnSwipe.dataset.bound = 'true';
  }

  // Filtre Chips
  document.querySelectorAll('.gl-chip').forEach(chip => {
    if (chip.dataset.bound === 'true') return;
    chip.addEventListener('click', () => {
      document.querySelectorAll('.gl-chip').forEach(c => c.classList.remove('gl-chip--active'));
      chip.classList.add('gl-chip--active');
      filterChallenges();
    });
    chip.dataset.bound = 'true';
  });
  
  // Fermer modals en cliquant sur overlay
  document.querySelectorAll('.modal-overlay, .gl-modal-overlay').forEach(overlay => {
    if (overlay.dataset.bound === 'true') return;
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        closeChallengeModal();
        closeDrawer();
        closeUserProfileModal();
      }
    });
    overlay.dataset.bound = 'true';
  });

  // Fermer sur Escape
  if (window.__ESCAPE_BOUND__ !== true) {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeChallengeModal();
        closeDrawer();
        closeUserProfileModal();
      }
    });
    window.__ESCAPE_BOUND__ = true;
  }
  
  // Charger les données
  loadChallenges();
}

// Charger au chargement du module
document.addEventListener('moduleLoaded', e => {
  if (e.detail.moduleName === 'challenges') {
    console.log('🎯 Module challenges détecté, initialisation...');
    setTimeout(() => initChallenges(), 100);
  }
});

// Si le module est déjà actif
const challengesSection = document.getElementById('challenges');
if (challengesSection && challengesSection.classList.contains('active')) {
  console.log('🎯 Section challenges active, initialisation immédiate...');
  initChallenges();
}

// Fallback
setTimeout(() => {
  const section = document.getElementById('challenges');
  if (section && section.classList.contains('active') && allChallenges.length === 0) {
    console.log('🎯 Chargement forcé (fallback)...');
    initChallenges();
  }
}, 500);

// Exposer les fonctions globalement
window.initChallenges = initChallenges;
window.loadChallenges = loadChallenges;
window.filterChallenges = filterChallenges;
window.setChallengesView = setChallengesView;
window.showChallengeDetail = showChallengeDetail;
window.closeChallengeModal = closeChallengeModal;
window.openDrawer = openDrawer;
window.closeDrawer = closeDrawer;
window.copyChallengeQrLink = copyChallengeQrLink;
window.downloadChallengeQr = downloadChallengeQr;
window.shareChallengeNative = shareChallengeNative;
window.shareChallengeTo = shareChallengeTo;
window.shareChallengeToChat = shareChallengeToChat;
window.shareChallengeVisual = shareChallengeVisual;
window.openShareTypePicker = openShareTypePicker;
window.closeShareTypeModal = closeShareTypeModal;
window.handleShareTypeChoice = handleShareTypeChoice;
window.updateCharCount = updateCharCount;
window.handleParticipationSubmit = handleParticipationSubmit;
window.showMyChallenge = showMyChallenge;
window.showCollection = showCollection;
window.showProfile = showProfile;
window.showUserProfile = showUserProfile;
window.closeUserProfileModal = closeUserProfileModal;

console.log('✅ Challenges Complete JS prêt');
})();
