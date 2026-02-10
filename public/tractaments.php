<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear tractament
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_tractament') {
  $st = db()->prepare("
    INSERT INTO tractaments
      (parcela_id, sector_id, fila_id, producte_id, aplicat, dosis_hectarea, dosis_total, temps, notes, creat)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");

  $st->execute([
    $_POST['parcela_id'] !== '' ? $_POST['parcela_id'] : null,
    $_POST['sector_id'] !== '' ? $_POST['sector_id'] : null,
    $_POST['fila_id'] !== '' ? $_POST['fila_id'] : null,
    $_POST['producte_id'],
    $_POST['aplicat'],
    (float)$_POST['dosis_hectarea'],
    (float)$_POST['dosis_total'],
    trim($_POST['temps'] ?? ''),
    trim($_POST['notes'] ?? ''),
    $_SESSION['user']['id']
  ]);

  flash_set("Tractament registrat correctament.", "ok");
  header("Location: tractaments.php");
  exit;
}

// Dades per als selects
$parceles = db()->query("SELECT id, name FROM parcela ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$sectors  = db()->query("SELECT id, name, parcela_id FROM sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$files    = db()->query("SELECT id, codi_fila, sector_id FROM files_arbres ORDER BY codi_fila")->fetchAll(PDO::FETCH_ASSOC);
$productes = db()->query("SELECT id, name FROM fito_productes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Llistar tractaments
$tractaments = db()->query("
  SELECT t.*, 
         p.name AS parcela_name,
         s.name AS sector_name,
         f.codi_fila AS fila_name,
         fp.name AS producte_name
  FROM tractaments t
  LEFT JOIN parcela p ON p.id = t.parcela_id
  LEFT JOIN sectors s ON s.id = t.sector_id
  LEFT JOIN files_arbres f ON f.id = t.fila_id
  LEFT JOIN fito_productes fp ON fp.id = t.producte_id
  ORDER BY t.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Tractaments · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nou tractament</h2>

    <form method="post">
      <input type="hidden" name="action" value="create_tractament">

      <label>Parcel·la</label>
      <select name="parcela_id">
        <option value="">—</option>
        <?php foreach ($parceles as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Sector</label>
      <select name="sector_id">
        <option value="">—</option>
        <?php foreach ($sectors as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Fila</label>
      <select name="fila_id">
        <option value="">—</option>
        <?php foreach ($files as $f): ?>
          <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['codi_fila']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Producte</label>
      <select name="producte_id" required>
        <?php foreach ($productes as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Data aplicació</label>
      <input type="date" name="aplicat" required>

      <label>Dosi / ha</label>
      <input type="number" step="0.01" name="dosis_hectarea" required>

      <label>Dosi total</label>
      <input type="number" step="0.01" name="dosis_total" required>

      <label>Temps</label>
      <input name="temps">

      <label>Notes</label>
      <textarea name="notes"></textarea>

      <button class="btn" type="submit">Desar</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Tractaments</h2>

    <?php if (!$tractaments): ?>
      <p class="small">Encara no hi ha tractaments registrats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Data</th>
            <th>Producte</th>
            <th>Parcel·la</th>
            <th>Sector</th>
            <th>Fila</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tractaments as $t): ?>
            <tr>
              <td><?= (int)$t['id'] ?></td>
              <td><?= htmlspecialchars($t['aplicat']) ?></td>
              <td><?= htmlspecialchars($t['producte_name']) ?></td>
              <td><?= htmlspecialchars($t['parcela_name']) ?></td>
              <td><?= htmlspecialchars($t['sector_name']) ?></td>
              <td><?= htmlspecialchars($t['fila_name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
