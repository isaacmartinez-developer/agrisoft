<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

// Crear treballador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
  $st = db()->prepare("
    INSERT INTO treballadors (nom_complet, telefon, rol_de_treball, cost_hora)
    VALUES (?, ?, ?, ?)
  ");

  $st->execute([
    trim($_POST['nom_complet']),
    trim($_POST['telefon'] ?? ''),
    trim($_POST['rol_de_treball'] ?? ''),
    $_POST['cost_hora'] !== '' ? $_POST['cost_hora'] : null
  ]);

  flash_set("Treballador creat correctament.", "ok");
  header("Location: personal.php");
  exit;
}

// Llistar treballadors
$treballadors = db()->query("SELECT * FROM treballadors ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Personal · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nou treballador</h2>

    <form method="post">
      <input type="hidden" name="action" value="create">

      <label>Nom complet</label>
      <input name="nom_complet" required>

      <label>Telèfon</label>
      <input name="telefon">

      <label>Rol de treball</label>
      <input name="rol_de_treball" placeholder="Ex: Podador, Tractorista...">

      <label>Cost per hora (€)</label>
      <input type="number" step="0.01" name="cost_hora">

      <button class="btn" type="submit">Desar</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Treballadors</h2>

    <?php if (!$treballadors): ?>
      <p class="small">Encara no hi ha treballadors registrats.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Telèfon</th>
            <th>Rol</th>
            <th>Cost/h</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($treballadors as $t): ?>
            <tr>
              <td><?= (int)$t['id'] ?></td>
              <td><?= htmlspecialchars($t['nom_complet']) ?></td>
              <td><?= htmlspecialchars($t['telefon']) ?></td>
              <td><?= htmlspecialchars($t['rol_de_treball']) ?></td>
              <td><?= htmlspecialchars($t['cost_hora']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
