<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

$can_manage = can_manage();

function build_geojson_polygon(array $ptsLatLng): string {
  $coords = [];
  foreach ($ptsLatLng as $pt) {
    $coords[] = [(float)$pt[1], (float)$pt[0]]; // [lng, lat]
  }
  // tancar
  $first = $coords[0];
  $last = $coords[count($coords)-1];
  if ($last[0] !== $first[0] || $last[1] !== $first[1]) {
    $coords[] = $first;
  }

  return json_encode([
    "type" => "Polygon",
    "coordinates" => [ $coords ]
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Espera un JSON amb punts [[lat,lng], ...] i retorna una llista neta.
 * Retorna [] si és invàlid.
 */
function sanitize_polygon_points(string $polygon_raw): array {
  $points = json_decode($polygon_raw, true);
  if (!is_array($points) || count($points) < 3) return [];

  $clean = [];
  foreach ($points as $pt) {
    if (!is_array($pt) || count($pt) < 2) continue;
    $lat = (float)$pt[0];
    $lng = (float)$pt[1];
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) continue;
    $clean[] = [$lat, $lng];
  }
  return (count($clean) >= 3) ? $clean : [];
}

function centroid_latlng(array $ptsLatLng): array {
  $sumLat = 0.0; $sumLng = 0.0;
  foreach ($ptsLatLng as $pt) { $sumLat += $pt[0]; $sumLng += $pt[1]; }
  return [$sumLat / max(count($ptsLatLng), 1), $sumLng / max(count($ptsLatLng), 1)];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_parcela') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per crear parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  $name = trim($_POST['name'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $area_ha = (float)($_POST['area_ha'] ?? 0);
  $polygon_raw = $_POST['polygon'] ?? '';

  if ($name === '') {
    flash_set("El nom és obligatori.", "err");
    header("Location: parcelles.php");
    exit;
  }

  $clean = sanitize_polygon_points($polygon_raw);
  if (count($clean) < 3) {
    flash_set("El polígon no és vàlid.", "err");
    header("Location: parcelles.php");
    exit;
  }

  // centroid simple (suficient per centrar mapa)
  [$gps_lat, $gps_lng] = centroid_latlng($clean);

  $polygon_geojson = build_geojson_polygon($clean);

  $tipus_sol = '';
  $pendent_pct = null;
  $infraestructures = '';

  $pdo = db();
  $pdo->beginTransaction();

  try {
    $st = $pdo->prepare("
      INSERT INTO `parcela`
        (`name`, `gps_lat`, `gps_lng`, `area_ha`, `tipus_sòl`, `pendent_pct`, `infraestructures`, `notes`, `polygon_geojson`)
      VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $st->execute([
      $name, $gps_lat, $gps_lng, $area_ha, $tipus_sol, $pendent_pct, $infraestructures, $notes, $polygon_geojson
    ]);

    $parcela_id = (int)$pdo->lastInsertId();

    $stp = $pdo->prepare("
      INSERT INTO `parcela_punt` (`parcela_id`, `idx`, `lat`, `lng`)
      VALUES (?, ?, ?, ?)
    ");
    foreach ($clean as $i => $pt) {
      $stp->execute([$parcela_id, $i, $pt[0], $pt[1]]);
    }

    $pdo->commit();
    flash_set("Parcel·la creada correctament.", "ok");
  } catch (Throwable $e) {
    $pdo->rollBack();
    flash_set("No s'ha pogut crear la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_parcela') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per editar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per editar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  $id = (int)($_POST['parcela_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $area_ha = (float)($_POST['area_ha'] ?? 0);
  $polygon_raw = $_POST['polygon'] ?? '';

  if ($id <= 0 || $name === '') {
    flash_set("Falten dades per actualitzar la parcel·la.", "err");
    header("Location: parcelles.php");
    exit;
  }

  $clean = sanitize_polygon_points($polygon_raw);
  if (count($clean) < 3) {
    flash_set("El polígon no és vàlid.", "err");
    header("Location: parcelles.php");
    exit;
  }

  [$gps_lat, $gps_lng] = centroid_latlng($clean);
  $polygon_geojson = build_geojson_polygon($clean);

  $pdo = db();
  $pdo->beginTransaction();
  try {
    $st = $pdo->prepare("UPDATE `parcela` SET `name`=?, `gps_lat`=?, `gps_lng`=?, `area_ha`=?, `notes`=?, `polygon_geojson`=? WHERE `id`=?");
    $st->execute([$name, $gps_lat, $gps_lng, $area_ha, $notes, $polygon_geojson, $id]);

    // Reemplaça vèrtexs
    $pdo->prepare("DELETE FROM `parcela_punt` WHERE `parcela_id`=?")->execute([$id]);
    $stp = $pdo->prepare("INSERT INTO `parcela_punt` (`parcela_id`, `idx`, `lat`, `lng`) VALUES (?, ?, ?, ?)");
    foreach ($clean as $i => $pt) {
      $stp->execute([$id, $i, $pt[0], $pt[1]]);
    }

    $pdo->commit();
    flash_set("Parcel·la actualitzada correctament.", "ok");
  } catch (Throwable $e) {
    $pdo->rollBack();
    flash_set("No s'ha pogut actualitzar la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_parcela') {
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per eliminar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per eliminar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  if (!$can_manage) {
    http_response_code(403);
    flash_set("No tens permisos per eliminar parcel·les.", "bad");
    header('Location: parcelles.php');
    exit;
  }
  $id = (int)($_POST['parcela_id'] ?? 0);
  if ($id <= 0) {
    flash_set("ID de parcel·la invàlid.", "err");
    header("Location: parcelles.php");
    exit;
  }

  try {
    db()->prepare("DELETE FROM `parcela` WHERE `id`=?")->execute([$id]);
    flash_set("Parcel·la eliminada.", "ok");
  } catch (Throwable $e) {
    flash_set("No s'ha pogut eliminar la parcel·la: " . $e->getMessage(), "err");
  }

  header("Location: parcelles.php");
  exit;
}

// Carrega parcel·les
$parcelles = db()->query("SELECT * FROM `parcela` ORDER BY `id` DESC")->fetchAll(PDO::FETCH_ASSOC);

// Prepara GeoJSON per pintar al mapa (FeatureCollection)
$features = [];
foreach ($parcelles as $p) {
  $geo = $p['polygon_geojson'] ?? '';
  if (!$geo) continue;

  $geom = json_decode($geo, true);
  if (!is_array($geom) || !isset($geom['type'])) continue;

  $features[] = [
    "type" => "Feature",
    "properties" => [
      "id" => (int)$p['id'],
      "name" => (string)($p['name'] ?? ''),
      "area_ha" => (string)($p['area_ha'] ?? ''),
      "notes" => (string)($p['notes'] ?? '')
    ],
    "geometry" => $geom
  ];
}

$parcelles_fc = [
  "type" => "FeatureCollection",
  "features" => $features
];

$titol = "Parcel·les · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">
  <div class="card span12">
    <h2>Mapa de parcel·les</h2>
    <p class="small">Dibuixa, edita o elimina parcel·les directament sobre el mapa. El sistema calcula l'àrea automàticament.</p>

    <div class="parcelles-topbar">
      <div class="parcelles-stat">
        <div class="parcelles-stat__label">Hora local</div>
        <div class="parcelles-stat__value" id="nowLocal">--:--:--</div>
      </div>
      <div class="parcelles-stat">
        <div class="parcelles-stat__label">Meteo (centre mapa)</div>
        <div class="parcelles-stat__value" id="meteoNow">Carregant...</div>
      </div>
    </div>

    <div id="map" class="parcelles-map"></div>

    <form method="post" id="parcelaForm" class="parcela-form">
      <input type="hidden" name="action" id="form_action" value="create_parcela">
      <input type="hidden" name="parcela_id" id="parcela_id" value="">
      <input type="hidden" name="polygon" id="polygon">
      <input type="hidden" name="area_ha" id="area_ha" value="0">

      <div class="parcela-form-grid">
        <div class="parcela-field parcela-field--name">
          <label for="name">Nom</label>
          <input name="name" id="name" required placeholder="Ex: Parcela Nord" <?= $can_manage ? '' : 'disabled' ?>>
          <div class="parcela-mode" id="form_mode">Mode: crear</div>
        </div>

        <div class="parcela-field parcela-field--area">
          <label for="area_ha_view">Àrea (ha)</label>
          <input id="area_ha_view" type="number" step="0.0001" value="0" disabled>
        </div>

        <div class="parcela-field parcela-field--notes">
          <label for="notes">Descripció</label>
          <textarea name="notes" id="notes" placeholder="Descripció / notes..." <?= $can_manage ? '' : 'disabled' ?>></textarea>
        </div>
      </div>

      <?php if ($can_manage): ?>
        <div class="parcela-form-actions">
          <button class="btn" id="btnSave" type="submit" disabled>Guardar</button>
          <button class="btn btn-secondary" id="btnCancel" type="button" style="display:none;">↩️ Cancel·lar</button>
          <button class="btn btn-secondary" id="btnClear" type="button">Esborrar dibuix</button>
        </div>
      <?php else: ?>
        <p class="small" style="margin-top:10px;">Mode lectura: no tens permisos per crear/editar/eliminar parcel·les.</p>
      <?php endif; ?>
    </form>
  </div>

  <div class="card span12">
    <h2>Parcel·les</h2>
    <?php if (!$parcelles): ?>
      <p class="small">Encara no hi ha parcel·les.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Hectàrees</th>
            <th>Infraestructures</th>
            <th>Descripció</th>
            <th>GPS</th>
            <th>Creat</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($parcelles as $p): ?>
            <tr style="cursor:pointer" onclick="zoomToParcela(<?= (int)$p['id'] ?>)">
              <td><?= (int)$p['id'] ?></td>
              <td class="parcela-name"><?= htmlspecialchars($p['name'] ?? '') ?></td>
              <td class="parcela-area"><?= htmlspecialchars($p['area_ha'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['infraestructures'] ?? '') ?></td>
              <td class="parcela-desc"><?= nl2br(htmlspecialchars($p['notes'] ?? '')) ?></td>
              <td class="small"><?= htmlspecialchars($p['gps_lat'] ?? '') ?>, <?= htmlspecialchars($p['gps_lng'] ?? '') ?></td>
              <td class="small"><?= htmlspecialchars($p['creat'] ?? '') ?></td>
              <td>
                <?php if ($can_manage): ?>
                  <button class="btn btn-action btn-action--edit" type="button" onclick="event.stopPropagation(); editParcela(<?= (int)$p['id'] ?>)">✏️</button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Segur que vols eliminar aquesta parcel·la?');">
                    <input type="hidden" name="action" value="delete_parcela">
                    <input type="hidden" name="parcela_id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-action btn-action--delete" type="submit" onclick="event.stopPropagation();" title="Eliminar" aria-label="Eliminar">🗑️</button>
                  </form>
                <?php else: ?>
                  <span class="small">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-measure@3.3.1/dist/leaflet-measure.css">

<link rel="stylesheet" href="assets/css/parcelles-map.css">

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
<script src="https://unpkg.com/leaflet.locatecontrol@0.79.0/dist/L.Control.Locate.min.js"></script>
<script src="https://unpkg.com/leaflet-minimap@3.6.1/dist/Control.MiniMap.min.js"></script>
<script src="https://unpkg.com/leaflet-measure@3.3.1/dist/leaflet-measure.js"></script>
<script src="https://unpkg.com/@turf/turf@6/turf.min.js"></script>

<script>
  window.AGRISOFT_PARCELLES = <?= json_encode($parcelles_fc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  window.AGRISOFT_CAN_MANAGE = <?= $can_manage ? 'true' : 'false' ?>;
</script>
<script src="assets/js/parcelles-map.js"></script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
