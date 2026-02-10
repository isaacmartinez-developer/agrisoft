<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

/**
 * Retorna el COUNT(*) d'una taula, i si falla (taula no existeix, etc.) retorna 0
 */
function count_table(string $table, string $where = '1=1'): int {
  try {
    $st = db()->query("SELECT COUNT(*) AS c FROM `$table` WHERE $where");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['c'] ?? 0);
  } catch (PDOException $e) {
    return 0;
  }
}

$kpis = [
  'parcel·les'           => count_table('parcela'),
  'tractaments'          => count_table('tractaments'),
  'treballadors'         => count_table('treballadors'),
  'hores registrades'    => count_table('registres_treball'),
];

$titol = "Tauler · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
  <?php foreach($kpis as $k=>$v): ?>
    <div class="card span3">
      <div class="kpi">
        <div>
          <div class="small"><?= htmlspecialchars($k) ?></div>
          <div class="n"><?= (int)$v ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="card span8">
    <h2>Mapa (placeholder)</h2>
    <p class="small">Per tenir mapa real: afegeix Leaflet (JS/CSS) i pinta marcadors amb les coordenades GPS.</p>
    <div style="height:320px;border-radius:14px;border:1px dashed rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6)">
      Mapa interactiu (pendent d'afegir Leaflet real)
    </div>
  </div>

  <div class="card span4">
    <h2>Accions ràpides</h2>
    <a class="btn" href="parcelles.php">+ Nova parcel·la</a>
    <a class="btn secondary" href="tractaments.php" style="margin-left:8px">+ Tractament</a>
    <hr>
    <a class="btn" href="treballadors.php">Veure treballadors</a>
    <a class="btn secondary" href="registre_treball.php" style="margin-left:8px">+ Registre treball</a>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
