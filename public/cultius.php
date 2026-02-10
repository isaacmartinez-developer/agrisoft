<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear cultiu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_cultiu') {
  $name = trim($_POST['name'] ?? '');

  if ($name !== '') {
    $st = db()->prepare("INSERT INTO cultius (name) VALUES (?)");
    $st->execute([$name]);

    flash_set("Cultiu creat correctament.", "ok");
    header("Location: cultius.php");
    exit;
  } else {
    flash_set("El nom del cultiu és obligatori.", "bad");
  }
}

// Llistar cultius
$cultius = db()->query("SELECT * FROM cultius ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Cultius · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nou cultiu</h2>
    <form method="post">
      <input type="hidden" name="action" value="create_cultiu">

      <label>Nom del cultiu</label>
      <input name="name" required>

      <button class="btn" type="submit">Crear</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Cultius</h2>

    <?php if (!$cultius): ?>
      <p class="small">Encara no hi ha cultius creats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cultius as $c): ?>
            <tr>
              <td><?= (int)$c['id'] ?></td>
              <td><?= htmlspecialchars($c['name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
