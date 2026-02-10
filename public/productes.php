<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear producte fitosanitari
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_producte') {
  $st = db()->prepare("
    INSERT INTO fito_productes (name, substancia_activa, unitat, stock, stock_baix, expiry_date)
    VALUES (?, ?, ?, ?, ?, ?)
  ");

  $st->execute([
    trim($_POST['name']),
    trim($_POST['substancia_activa'] ?? ''),
    $_POST['unitat'] ?? 'l',
    (float)($_POST['stock'] ?? 0),
    (float)($_POST['stock_baix'] ?? 5),
    $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null
  ]);

  flash_set("Producte creat correctament.", "ok");
  header("Location: productes.php");
  exit;
}

// Llistar productes
$productes = db()->query("SELECT * FROM fito_productes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Productes fitosanitaris · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nou producte</h2>
    <form method="post">
      <input type="hidden" name="action" value="create_producte">

      <label>Nom</label>
      <input name="name" required>

      <label>Substància activa</label>
      <input name="substancia_activa">

      <label>Unitat</label>
      <select name="unitat">
        <option value="l">Litres</option>
        <option value="kg">Kg</option>
        <option value="u">Unitats</option>
      </select>

      <label>Stock</label>
      <input name="stock" type="number" step="0.01" value="0">

      <label>Stock baix</label>
      <input name="stock_baix" type="number" step="0.01" value="5">

      <label>Data de caducitat</label>
      <input name="expiry_date" type="date">

      <button class="btn" type="submit">Crear</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Productes</h2>

    <?php if (!$productes): ?>
      <p class="small">Encara no hi ha productes creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Unitat</th>
            <th>Stock</th>
            <th>Caduca</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productes as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><?= htmlspecialchars($p['unitat']) ?></td>
              <td><?= htmlspecialchars($p['stock']) ?></td>
              <td><?= htmlspecialchars($p['expiry_date']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
