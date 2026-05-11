let usersData = [];
let filteredUsers = [];
let currentPage = 1;
let rowsPerPage = 5;
let userPieChart = null;
let userBarChart = null;
let userStatusPieChart = null;
let userStatusBarChart = null;

function showToast(message, subtitle, type = 'info') {
  console.log('Toast:', message, subtitle, type);
  let toast = document.getElementById('admin-users-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'admin-users-toast';
    toast.style.cssText = 'position:fixed;right:24px;bottom:24px;z-index:20000;max-width:360px;padding:14px 18px;border-radius:12px;font-weight:700;box-shadow:0 14px 40px rgba(0,0,0,.35);transition:opacity .25s,transform .25s;';
    document.body.appendChild(toast);
  }
  const colors = {
    success: ['rgba(34, 197, 94, .18)', '#22c55e', 'rgba(34,197,94,.45)'],
    error: ['rgba(239, 68, 68, .18)', '#ef4444', 'rgba(239,68,68,.45)'],
    info: ['rgba(59, 130, 246, .18)', '#60a5fa', 'rgba(59,130,246,.45)']
  };
  const palette = colors[type] || colors.info;
  toast.style.background = palette[0];
  toast.style.color = palette[1];
  toast.style.border = `1px solid ${palette[2]}`;
  toast.innerHTML = `<div>${message || ''}</div>${subtitle ? `<small style="display:block;margin-top:4px;color:var(--text);font-weight:500;">${subtitle}</small>` : ''}`;
  toast.style.opacity = '1';
  toast.style.transform = 'translateY(0)';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
  }, 3500);
}

document.addEventListener('adminModuleLoaded', (e) => {
  if (e.detail.moduleName === 'users') {
    // Attach edit form listener
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
      editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveUser();
      });
    }

    // Attach add form listener
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
      addForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveNewUser();
      });
    }

    // Attach search listener
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', () => {
        searchUsers();
      });
    }

    // Attach role filter listener
    const roleFilter = document.getElementById('roleFilter');
    if (roleFilter) {
      roleFilter.addEventListener('change', () => {
        filterUsers();
      });
    }

    // Attach sort listener
    const sortSelect = document.getElementById('triDate');
    if (sortSelect) {
      sortSelect.addEventListener('change', () => {
        tri();
      });
    }

    // Attach rows per page listener
    const rowsPerPageSelect = document.getElementById('rowsPerPage');
    if (rowsPerPageSelect) {
      rowsPerPageSelect.addEventListener('change', (e) => {
        setRowsPerPage(parseInt(e.target.value));
      });
    }
  }
});

function addUser() {
  const modal = document.getElementById('addUserModal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

function closeAddModal() {
  const modal = document.getElementById('addUserModal');
  if (modal) {
    modal.style.display = 'none';
    // Reset form
    const form = document.getElementById('addUserForm');
    if (form) {
      form.reset();
    }
  }
}

async function saveNewUser() {
  const nom = document.getElementById('addUserNom').value.trim();
  const prenom = document.getElementById('addUserPrenom').value.trim();
  const email = document.getElementById('addUserEmail').value.trim();
  const mdp = document.getElementById('addUserMdp').value;
  const role = document.getElementById('addUserRole').value;
  const submitBtn = document.querySelector('#addUserForm button[type="submit"]');

  if (!nom || !prenom || !email || !mdp) {
    showToast('Erreur', 'Tous les champs sont obligatoires.', 'error');
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Ajout...';
  }

  try {
    const response = await fetch('users/addUser.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        nom, 
        prenom, 
        email, 
        mdp, 
        role,
        is_admin: true 
      })
    });
    const text = await response.text();
    let result;
    try {
      result = JSON.parse(text);
    } catch (parseError) {
      throw new Error(text.trim() || 'Réponse serveur invalide');
    }
    
    if (result.success) {
      showToast('Succès', result.message, 'success');
      closeAddModal();
      await loadUsers();
    } else {
      showToast('Erreur', result.message, 'error');
    }
  } catch (error) {
    console.error('Erreur saveNewUser:', error);
    showToast('Erreur', error.message || 'Erreur de connexion', 'error');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Ajouter';
    }
  }
}

async function editUser(userId) {
  const modal = document.getElementById('editUserModal');
  if (!modal) return;

  try {
    const response = await fetch(`users/updateUser.php?id=${userId}`, {
      credentials: 'include',
      headers: { 
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const result = await response.json();
    
    if (result.success) {
      document.getElementById('editUserId').value = result.data.id;
      document.getElementById('editUserNom').value = result.data.nom;
      document.getElementById('editUserPrenom').value = result.data.prenom;
      document.getElementById('editUserEmail').value = result.data.email;
      document.getElementById('editUserRole').value = result.data.role;
      
      modal.style.display = 'flex';
    } else {
      showToast('Erreur', result.message || 'Utilisateur non trouvé', 'error');
    }
  } catch (error) {
    console.error('Erreur editUser:', error);
    showToast('Erreur', 'Erreur de connexion', 'error');
  }
}

function closeEditModal() {
  const modal = document.getElementById('editUserModal');
  if (modal) {
    modal.style.display = 'none';
  }
}

async function saveUser() {
  const userId = parseInt(document.getElementById('editUserId').value);
  const nom = document.getElementById('editUserNom').value.trim();
  const prenom = document.getElementById('editUserPrenom').value.trim();
  const email = document.getElementById('editUserEmail').value.trim();
  const role = document.getElementById('editUserRole').value;

  try {
    const response = await fetch('users/updateUser.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId, nom, prenom, email, role })
    });
    const result = await response.json();
    
    if (result.success) {
      showToast('Succès', result.message, 'success');
      closeEditModal();
      await loadUsers();
    } else {
      showToast('Erreur', result.message, 'error');
    }
  } catch (error) {
    console.error('Erreur saveUser:', error);
    showToast('Erreur', 'Erreur de connexion', 'error');
  }
}

async function deleteUser(userId) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
    return;
  }

  try {
    const response = await fetch('users/deleteUser.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: userId })
    });
    const result = await response.json();
    
    if (result.success) {
      showToast('Succès', result.message, 'success');
      await loadUsers();
    } else {
      showToast('Erreur', result.message, 'error');
    }
  } catch (error) {
    console.error('Erreur deleteUser:', error);
    showToast('Erreur', 'Erreur de connexion', 'error');
  }
}

async function loadUsers() {
  const tbody = document.getElementById('usersTableBody');
  if (!tbody) return;

  tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:40px;"><div class="section-loader-spinner"></div>Chargement…</td></tr>';

  try {
    const response = await fetch('users/tri.php', {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' }
    });
    const result = await response.json();
    if (result.success) {
      usersData = result.data;
      filteredUsers = [...usersData];
      renderUsersTable();
      renderUserCharts();
    } else {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#e74c3c; padding:40px;">Erreur lors du chargement des utilisateurs</td></tr>';
    }
  } catch (error) {
    console.error('Erreur loadUsers:', error);
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:#e74c3c; padding:40px;">Erreur de connexion</td></tr>';
  }
}

function renderUsersTable() {
  const tbody = document.getElementById('usersTableBody');
  if (!tbody) return;

  const start = (currentPage - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const pageUsers = filteredUsers.slice(start, end);

  tbody.innerHTML = pageUsers.map(user => {
    const statusClass = user.status === 'actif' ? 'badge-active' : (user.status === 'suspendu' ? 'badge-inactive' : 'badge-inactive');
    const statusText = user.status === 'actif' ? '✅ Actif' : (user.status === 'suspendu' ? '🚫 Suspendu' : '⭕ Inactif');
    
    return `
      <tr class="animate-in">
        <td><strong>#${user.id}</strong></td>
        <td>
          <strong>${user.prenom} ${user.nom}</strong>
        </td>
        <td>${user.email}</td>
        <td>
          <span class="badge ${user.role === 'admin' ? 'badge-admin' : 'badge-user'}">
            ${getRoleLabel(user.role)}
          </span>
        </td>
        <td>${user.date_creation}</td>
        <td>
          <span class="badge ${statusClass}">
            ${statusText}
          </span>
        </td>
        <td style="text-align:center;">
          <div class="action-btns" style="justify-content:center;">
            <button onclick="editUser(${user.id})" class="action-btn action-btn-edit" title="Modifier">✏️</button>
            <button onclick="toggleAccountStatus(${user.id})" class="action-btn action-btn-view" title="${user.status === 'actif' ? 'Désactiver' : 'Activer'}">
              ${user.status === 'actif' ? '⏸️' : '▶️'}
            </button>
            <button onclick="deleteUser(${user.id})" class="action-btn action-btn-delete" title="Supprimer">🗑️</button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  renderPagination();
}

function renderPagination() {
  const totalPages = Math.ceil(filteredUsers.length / rowsPerPage);
  const paginationDiv = document.querySelector('.pagination');
  if (!paginationDiv) return;

  let html = '';
  html += `<button class="page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="15 18 9 12 15 6"/></svg>
    Précédent
  </button>`;

  for (let i = 1; i <= totalPages; i++) {
    html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
  }

  html += `<button class="page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
    Suivant
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
  </button>`;

  paginationDiv.innerHTML = html;
}

function changePage(page) {
  currentPage = page;
  renderUsersTable();
}

function setRowsPerPage(value) {
  rowsPerPage = value;
  currentPage = 1;
  renderUsersTable();
}

function getRoleLabel(role) {
  const labels = {
    'utilisateur': '👤 Utilisateur',
    'admin': '🛡️ Administrateur',
    'nutritionniste': '🥗 Nutritionniste',
    'ecologiste': '🌱 Écologiste'
  };
  return labels[role] || role;
}

function searchUsers() {
  const searchInput = document.getElementById('searchInput');
  if (!searchInput) return;
  const query = searchInput.value.toLowerCase().trim();

  filteredUsers = usersData.filter(user =>
    `${user.prenom} ${user.nom}`.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query)
  );

  currentPage = 1;
  renderUsersTable();
}

function filterUsers() {
  const roleFilter = document.getElementById('roleFilter');
  if (!roleFilter) return;
  const role = roleFilter.value;

  filteredUsers = role
    ? usersData.filter(user => user.role === role)
    : [...usersData];

  currentPage = 1;
  renderUsersTable();
}

function filterByChip(element, type) {
  document.querySelectorAll('.filter-chips .chip').forEach(chip => chip.classList.remove('active'));
  element.classList.add('active');

  switch (type) {
    case 'all':
      filteredUsers = [...usersData];
      break;
    case 'active':
      filteredUsers = usersData.filter(u => u.status === 'actif');
      break;
    case 'inactive':
      filteredUsers = usersData.filter(u => u.status === 'inactif');
      break;
    case 'suspended':
      filteredUsers = usersData.filter(u => u.status === 'suspendu');
      break;
    case 'admin':
      filteredUsers = usersData.filter(u => u.role === 'admin');
      break;
  }

  currentPage = 1;
  renderUsersTable();
}

function tri() {
  const triDate = document.getElementById('triDate');
  if (!triDate) return;
  const order = triDate.value;

  filteredUsers.sort((a, b) => {
    const dateA = new Date(a.date_creation.split('/').reverse().join('-'));
    const dateB = new Date(b.date_creation.split('/').reverse().join('-'));
    return order === 'asc' ? dateA - dateB : dateB - dateA;
  });

  currentPage = 1;
  renderUsersTable();
}

function refreshUsers() {
  loadUsers();
  showToast('Utilisateurs actualisés', 'succès', 'success');
}

async function toggleAccountStatus(userId) {
  try {
    const response = await fetch(`users/toggle_account_status.php?id=${userId}`, {
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' }
    });
    const result = await response.json();
    if (result.success) {
      loadUsers();
      showToast(result.message, 'succès', 'success');
    } else {
      showToast(result.message || 'Erreur', 'erreur', 'error');
    }
  } catch (error) {
    console.error('Erreur toggleAccountStatus:', error);
    showToast('Erreur de connexion', 'erreur', 'error');
  }
}

function exportData(type) {
  if (type === 'users' || type === 'csv') {
    const csvContent = [
      ['ID', 'Nom', 'Prénom', 'Email', 'Rôle', 'Statut', 'Date d\'inscription'].join(','),
      ...usersData.map(user => [
        user.id,
        `"${user.nom}"`,
        `"${user.prenom}"`,
        `"${user.email}"`,
        user.role,
        user.status,
        user.date_creation
      ].join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `utilisateurs_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showToast('Succès', 'Export CSV téléchargé', 'success');
  } else {
    console.log('Exporter données:', type);
    showToast('Fonctionnalité à implémenter', 'info');
  }
}

function switchChartType(type) {
  const pieContainer = document.getElementById('pieChartContainer');
  const barContainer = document.getElementById('barChartContainer');
  if (!pieContainer || !barContainer) return;

  pieContainer.style.display = type === 'pie' ? 'block' : 'none';
  barContainer.style.display = type === 'bar' ? 'block' : 'none';

  document.querySelectorAll('.chart-toggle .toggle-btn').forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
}

function switchStatusChartType(type) {
  const pieContainer = document.getElementById('statusPieChartContainer');
  const barContainer = document.getElementById('statusBarChartContainer');
  if (!pieContainer || !barContainer) return;

  pieContainer.style.display = type === 'pie' ? 'block' : 'none';
  barContainer.style.display = type === 'bar' ? 'block' : 'none';

  document.querySelectorAll('#statusPieChartContainer ~ .chart-toggle .toggle-btn, #statusBarChartContainer ~ .chart-toggle .toggle-btn').forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
}

function renderUserCharts() {
  const roleCounts = {
    'utilisateur': 0,
    'admin': 0,
    'nutritionniste': 0,
    'ecologiste': 0
  };
  const statusCounts = {
    'actif': 0,
    'inactif': 0,
    'suspendu': 0
  };

  usersData.forEach(user => {
    roleCounts[user.role] = (roleCounts[user.role] || 0) + 1;
    statusCounts[user.status] = (statusCounts[user.status] || 0) + 1;
  });

  const legendDiv = document.getElementById('userStatsLegend');
  if (legendDiv) {
    legendDiv.innerHTML = `
      <div class="stat-item"><span style="color:#3b82f6;">👤 Utilisateurs:</span> <strong>${roleCounts.utilisateur}</strong></div>
      <div class="stat-item"><span style="color:#f59e0b;">🛡️ Admins:</span> <strong>${roleCounts.admin}</strong></div>
      <div class="stat-item"><span style="color:#10b981;">🥗 Nutritionnistes:</span> <strong>${roleCounts.nutritionniste}</strong></div>
      <div class="stat-item"><span style="color:#8b5cf6;">🌱 Écologistes:</span> <strong>${roleCounts.ecologiste}</strong></div>
    `;
  }

  const statusLegendDiv = document.getElementById('userStatusStatsLegend');
  if (statusLegendDiv) {
    statusLegendDiv.innerHTML = `
      <div class="stat-item"><span style="color:#10b981;">✅ Actifs:</span> <strong>${statusCounts.actif}</strong></div>
      <div class="stat-item"><span style="color:#f59e0b;">⭕ Inactifs:</span> <strong>${statusCounts.inactif}</strong></div>
      <div class="stat-item"><span style="color:#ef4444;">🚫 Suspendus:</span> <strong>${statusCounts.suspendu}</strong></div>
    `;
  }

  const pieCtx = document.getElementById('userPieChart');
  if (pieCtx && window.Chart) {
    if (userPieChart) userPieChart.destroy();
    userPieChart = new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: ['Utilisateur', 'Admin', 'Nutritionniste', 'Écologiste'],
        datasets: [{
          data: [roleCounts.utilisateur, roleCounts.admin, roleCounts.nutritionniste, roleCounts.ecologiste],
          backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6']
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  }

  const barCtx = document.getElementById('userBarChart');
  if (barCtx && window.Chart) {
    if (userBarChart) userBarChart.destroy();
    userBarChart = new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Utilisateur', 'Admin', 'Nutritionniste', 'Écologiste'],
        datasets: [{
          label: 'Nombre',
          data: [roleCounts.utilisateur, roleCounts.admin, roleCounts.nutritionniste, roleCounts.ecologiste],
          backgroundColor: ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6']
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  }

  const statusPieCtx = document.getElementById('userStatusPieChart');
  if (statusPieCtx && window.Chart) {
    if (userStatusPieChart) userStatusPieChart.destroy();
    userStatusPieChart = new Chart(statusPieCtx, {
      type: 'pie',
      data: {
        labels: ['Actif', 'Inactif', 'Suspendu'],
        datasets: [{
          data: [statusCounts.actif, statusCounts.inactif, statusCounts.suspendu],
          backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  }

  const statusBarCtx = document.getElementById('userStatusBarChart');
  if (statusBarCtx && window.Chart) {
    if (userStatusBarChart) userStatusBarChart.destroy();
    userStatusBarChart = new Chart(statusBarCtx, {
      type: 'bar',
      data: {
        labels: ['Actif', 'Inactif', 'Suspendu'],
        datasets: [{
          label: 'Nombre',
          data: [statusCounts.actif, statusCounts.inactif, statusCounts.suspendu],
          backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } } }
    });
  }
}

window.loadUsers = loadUsers;
window.refreshUsers = refreshUsers;
window.searchUsers = searchUsers;
window.filterUsers = filterUsers;
window.filterByChip = filterByChip;
window.setRowsPerPage = setRowsPerPage;
window.tri = tri;
window.editUser = editUser;
window.closeEditModal = closeEditModal;
window.deleteUser = deleteUser;
window.addUser = addUser;
window.closeAddModal = closeAddModal;
window.exportData = exportData;
window.switchChartType = switchChartType;
window.switchStatusChartType = switchStatusChartType;
window.toggleAccountStatus = toggleAccountStatus;
