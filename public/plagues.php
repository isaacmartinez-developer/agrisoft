<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear pla de tractament (planificat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_plan') {
  $title = trim($_POST['title'] ?? '');
  $planned_on = $_POST['planned_on'] ?? '';
  $parcela_id = ($_POST['parcela_id'] ?? '') !== '' ? (int)$_POST['parcela_id'] : null;
  $sector_id  = ($_POST['sector_id'] ?? '') !== '' ? (int)$_POST['sector_id'] : null;
  $notes = trim($_POST['notes'] ?? '');

  if ($title === '' || $planned_on === '') {
    flash_set("Títol i data són obligatoris.", "bad");
  } else {
    $st = db()->prepare("
      INSERT INTO plans_tractament (title, planned_on, parcela_id, sector_id, notes, status, creat)
      VALUES (?, ?, ?, ?, ?, 'pendent', ?)
    ");
    $st->execute([$title, $planned_on, $parcela_id, $sector_id, $notes, $_SESSION['user']['id']]);

    flash_set("Pla de tractament creat.", "ok");
    header("Location: plagues.php");
    exit;
  }
}

// Canviar estat (pendent/fet/cancelat)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
  $id = (int)($_POST['id'] ?? 0);
  $status = $_POST['status'] ?? 'pendent';

  if ($id > 0 && in_array($status, ['pendent','fet','cancelat'], true)) {
    $st = db()->prepare("UPDATE plans_tractament SET status = ? WHERE id = ?");
    $st->execute([$status, $id]);
    flash_set("Estat actualitzat.", "ok");
  }
  header("Location: plagues.php");
  exit;
}

// Dades per selects
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, name, parcela_id FROM sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Llista de plans
$plans = db()->query("
  SELECT pt.*,
         p.name AS parcela_name,
         s.name AS sector_name,
         u.name AS creat_per
  FROM plans_tractament pt
  LEFT JOIN parcela p ON p.id = pt.parcela_id
  LEFT JOIN sectors s ON s.id = pt.sector_id
  LEFT JOIN usuaris u ON u.id = pt.creat
  ORDER BY pt.planned_on DESC, pt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Plagues / Plans de tractament · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nou pla (plaga / tractament)</h2>

    <form method="post">
      <input type="hidden" name="action" value="create_plan">

      <label>Títol</label>
      <input name="title" placeholder="Ex: Mosca de l'olivera - tractament" required>

      <label>Data planificada</label>
      <input type="date" name="planned_on" required>

      <label>Parcel·la</label>
      <select name="parcela_id">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Notes</label>
      <textarea name="notes" placeholder="Símptomes, observacions, recomanació..."></textarea>

      <button class="btn" type="submit">Crear pla</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Plans</h2>

    <?php if (!$plans): ?>
      <p class="small">Encara no hi ha plans creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Títol</th>
            <th>Ubicació</th>
            <th>Estat</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($plans as $pt): ?>
            <tr>
              <td><?= htmlspecialchars($pt['planned_on']) ?></td>
              <td>
                <?= htmlspecialchars($pt['title']) ?>
                <?php if (!empty($pt['creat_per'])): ?>
                  <div class="small">Creat per: <?= htmlspecialchars($pt['creat_per']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?= htmlspecialchars($pt['parcela_name'] ?? '—') ?>
                <?php if (!empty($pt['sector_name'])): ?>
                  <div class="small">Sector: <?= htmlspecialchars($pt['sector_name']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($pt['status']) ?></td>
              <td>
                <form method="post" style="display:flex;gap:6px;align-items:center;">
                  <input type="hidden" name="action" value="set_status">
                  <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                  <select name="status">
                    <option value="pendent" <?= $pt['status']==='pendent'?'selected':'' ?>>pendent</option>
                    <option value="fet" <?= $pt['status']==='fet'?'selected':'' ?>>fet</option>
                    <option value="cancelat" <?= $pt['status']==='cancelat'?'selected':'' ?>>cancelat</option>
                  </select>
                  <button class="btn secondary" type="submit">Desar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
