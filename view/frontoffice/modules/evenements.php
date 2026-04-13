<?php
// Depuis frontoffice/modules/ → remonter 3 niveaux → u/
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../controller/EvenementController.php';

$evenementC = new EvenementController();
$list = $evenementC->listEvenements();
$events = $list ? $list->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<section class="content-section" id="events">
  <div class="section-header">
    <h2 class="module-title">🎯 Événements Écologiques</h2>
    <p>Découvrez et participez aux événements de la communauté GaiaLumen.</p>
  </div>

  <?php if (empty($events)): ?>
    <div style="text-align:center;padding:60px;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:16px;">📅</div>
      <p>Aucun événement disponible pour le moment.</p>
    </div>
  <?php else: ?>
    <div class="events-grid">
      <?php foreach ($events as $e): ?>
      <div class="event-card">
        <div class="event-content">
          <div class="event-type-badge">
            <?php
              $icons = ['repas'=>'🍲','sport'=>'🧘','medical'=>'🥗','atelier'=>'🌱'];
              echo ($icons[$e['type']] ?? '📌') . ' ' . htmlspecialchars($e['type']);
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
            <!--
              CHEMIN : participation/add.php est relatif à dashboard.html
              dashboard.html est dans frontoffice/
              donc participation/add.php → frontoffice/participation/add.php ✓
            -->
            <a href="participation/add.php?event_id=<?= $e['id_event'] ?>"
               class="btn-participer">
              ✅ Je participe
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
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
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-top: 24px;
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
  background: rgba(91,62,150,.2);
  border: 1px solid rgba(91,62,150,.3);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: .8rem;
  font-weight: 600;
  color: var(--text);
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
