<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

// Resum per any (usa any_campanya i, si no hi fos, YEAR(recollit))
$per_any = db()->query("
  SELECT
    COALESCE(any_campanya, YEAR(recollit)) AS any_campanya,
    SUM(kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites
  GROUP BY COALESCE(any_campanya, YEAR(recollit))
  ORDER BY any_campanya DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Resum per cultiu (via varietat -> cultiu)
$per_cultiu = db()->query("
  SELECT
    COALESCE(c.name, '—') AS cultiu,
    SUM(co.kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites co
  LEFT JOIN varietats v ON v.id = co.varietat_id
  LEFT JOIN cultius c ON c.id = v.cultiu_id
  GROUP BY COALESCE(c.id, 0), COALESCE(c.name, '—')
  ORDER BY total_kg DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Resum per parcel·la
$per_parcela = db()->query("
  SELECT
    COALESCE(p.name, '—') AS parcela,
    SUM(co.kg) AS total_kg,
    COUNT(*) AS num_collites
  FROM collites co
  LEFT JOIN parcela p ON p.id = co.parcela_id
  GROUP BY COALESCE(p.id, 0), COALESCE(p.name, '—')
  ORDER BY total_kg DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Informes · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span12">
    <h2>Producció per any</h2>
    <?php if (!$per_any): ?>
      <p class="small">Encara no hi ha dades de collites.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Any</th>
            <th>Collites</th>
            <th>Total (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_any as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['any_campanya']) ?></td>
              <td><?= (int)$r['num_collites'] ?></td>
              <td><?= number_format((float)$r['total_kg'], 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <h2>Producció per cultiu</h2>
    <?php if (!$per_cultiu): ?>
      <p class="small">Encara no hi ha dades.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Cultiu</th>
            <th>Collites</th>
            <th>Total (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_cultiu as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['cultiu']) ?></td>
              <td><?= (int)$r['num_collites'] ?></td>
              <td><?= number_format((float)$r['total_kg'], 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card span6">
    <h2>Producció per parcel·la</h2>
    <?php if (!$per_parcela): ?>
      <p class="small">Encara no hi ha dades.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Parcel·la</th>
            <th>Collites</th>
            <th>Total (kg)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($per_parcela as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['parcela'] ?? '—') ?></td>
              <td><?= (int)$r['num_collites'] ?></td>
              <td><?= number_format((float)$r['total_kg'], 2, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
