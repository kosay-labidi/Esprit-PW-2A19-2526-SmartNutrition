<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/EvenementController.php';

$evenementC = new EvenementController();
$list   = $evenementC->listEvenements('date ASC');
$events = $list ? $list->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<section class="content-section" id="events">
  <div class="section-header">
    <h2 class="module-title">🎯 Événements Écologiques</h2>
    <p>Découvrez et participez aux événements de la communauté GaiaLumen.</p>
  </div>

  <!-- Barre filtre par type + tri -->
  <div class="ev-front-toolbar">
    <div class="ev-front-filters">
      <button class="ev-chip active" data-type="">Tous</button>
      <button class="ev-chip" data-type="repas">🍲 Repas</button>
      <button class="ev-chip" data-type="sport">🧘 Sport</button>
      <button class="ev-chip" data-type="medical">🥗 Nutrition</button>
      <button class="ev-chip" data-type="atelier">🌱 Atelier</button>
    </div>
    <select class="ev-front-sort" id="ev-sort">
      <option value="date">📅 Date ↑</option>
      <option value="date_desc">📅 Date ↓</option>
    </select>
  </div>

  <?php if (empty($events)): ?>
    <div style="text-align:center;padding:60px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:16px;">📅</div>
      <p>Aucun événement disponible pour le moment.</p>
    </div>
  <?php else: ?>
    <div class="events-grid" id="events-grid">
      <?php foreach ($events as $e): ?>
      <div class="event-card"
           data-type="<?= htmlspecialchars($e['type']) ?>"
           data-date="<?= htmlspecialchars($e['date']) ?>"
           data-titre="<?= htmlspecialchars($e['titre']) ?>">
        <div class="event-content">
          <div class="event-type-badge ev-type-<?= htmlspecialchars($e['type']) ?>">
            <?php
              $icons = ['repas'=>'🍲','sport'=>'🧘','medical'=>'🥗','atelier'=>'🌱'];
              echo ($icons[$e['type']] ?? '📌') . ' ' . ucfirst(htmlspecialchars($e['type']));
            ?>
          </div>
          <h3 class="event-title"><?= htmlspecialchars($e['titre']) ?></h3>
          <div class="event-meta">
            <span>📅 <?= date('d/m/Y', strtotime($e['date'])) ?></span>
            <span>🕐 <?= htmlspecialchars($e['heure']) ?></span>
          </div>
          <?php if (!empty($e['description'])): ?>
            <p class="event-desc"><?= nl2br(htmlspecialchars($e['description'])) ?></p>
          <?php endif; ?>
          <div class="event-actions">
            <a href="participation/add.php?event_id=<?= $e['id_event'] ?>" class="btn-participer">
              ✅ Je participe
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div id="ev-no-result" style="display:none;text-align:center;padding:60px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
      <p>Aucun événement pour ce type.</p>
    </div>
  <?php endif; ?>
</section>

<style>
.module-title {
  background: linear-gradient(135deg, var(--text), var(--blue));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.ev-front-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin: 20px 0 24px;
}
.ev-front-filters { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
.ev-chip {
  padding: 7px 16px;
  border-radius: 50px;
  border: 1.5px solid rgba(91,62,150,.3);
  background: rgba(91,62,150,.08);
  color: var(--text);
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .25s;
}
.ev-chip:hover, .ev-chip.active {
  background: var(--violet);
  border-color: var(--violet);
  color: #fff;
  box-shadow: 0 3px 12px rgba(91,62,150,.4);
}
.ev-front-sort {
  padding: 7px 14px;
  border-radius: 10px;
  border: 1.5px solid rgba(91,62,150,.3);
  background: rgba(91,62,150,.08);
  color: var(--text);
  font-size: .82rem;
  cursor: pointer;
  outline: none;
}
.ev-type-repas   { background:rgba(230,126,34,.18); color:#e67e22; border:1px solid rgba(230,126,34,.35); }
.ev-type-sport   { background:rgba(46,204,113,.18); color:#2ecc71; border:1px solid rgba(46,204,113,.35); }
.ev-type-medical { background:rgba(241,196,15,.18); color:#d4ac0d; border:1px solid rgba(241,196,15,.35); }
.ev-type-atelier { background:rgba(155,89,182,.18); color:#9b59b6; border:1px solid rgba(155,89,182,.35); }
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
}
.event-card {
  background: var(--card-bg);
  border: 1px solid rgba(91,62,150,.15);
  border-radius: 16px;
  overflow: hidden;
  transition: all .3s ease;
  backdrop-filter: blur(10px);
}
.event-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 32px rgba(91,62,150,.25);
  border-color: rgba(91,62,150,.4);
}
.event-content { padding: 24px; }
.event-type-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: .8rem;
  font-weight: 600;
  margin-bottom: 12px;
}
.event-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: var(--text);
}
.event-meta {
  display: flex;
  gap: 16px;
  font-size: .85rem;
  color: var(--muted);
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.event-desc {
  color: var(--muted);
  font-size: .9rem;
  line-height: 1.6;
  margin-bottom: 16px;
}
.event-actions { margin-top: 16px; }
.btn-participer {
  display: inline-block;
  padding: 10px 24px;
  background: linear-gradient(135deg, var(--violet), var(--blue));
  color: #fff;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  font-size: .9rem;
  transition: all .3s;
}
.btn-participer:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(91,62,150,.4);
  color: #fff;
}
</style>

<script>
(function() {
  const chips    = document.querySelectorAll('#events .ev-chip');
  const sortSel  = document.getElementById('ev-sort');
  const grid     = document.getElementById('events-grid');
  const noResult = document.getElementById('ev-no-result');
  if (!grid) return;

  let activeType = '';

  function applyFilter() {
    const cards   = [...grid.querySelectorAll('.event-card')];
    const sortKey = sortSel ? sortSel.value : 'date';
    const visible = cards.filter(c => !activeType || c.dataset.type === activeType);

    visible.sort((a, b) => a.dataset.date.localeCompare(b.dataset.date)
      * (sortKey === 'date_desc' ? -1 : 1));

    cards.forEach(c => c.style.display = 'none');
    visible.forEach(c => { c.style.display = ''; grid.appendChild(c); });

    if (noResult) noResult.style.display = visible.length ? 'none' : 'block';
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      activeType = chip.dataset.type;
      applyFilter();
    });
  });

  if (sortSel) sortSel.addEventListener('change', applyFilter);
  applyFilter();
})();
</script>
