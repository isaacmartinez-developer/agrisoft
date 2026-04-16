<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';
require_once __DIR__ . '/../app/helpers/flash.php';

require_login();

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Crear màquina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {

  $nom = trim($_POST['nom'] ?? '');

  if ($nom === '') {
    flash_set("El nom és obligatori.", "bad");
  } else {
    $st = db()->prepare("
      INSERT INTO maquinaria (nom, tipus, matricula, tipusCombustible, cavalls)
      VALUES (?, ?, ?, ?, ?)
    ");

    $st->execute([
      $nom,
      trim($_POST['tipus'] ?? '') ?: null,
      trim($_POST['matricula'] ?? '') ?: null,
      trim($_POST['tipusCombustible'] ?? '') ?: null,
      ($_POST['cavalls'] ?? '') !== '' ? (int)$_POST['cavalls'] : null
    ]);

    flash_set("Maquinària creada correctament.", "ok");
    header("Location: maquinaria.php");
    exit;
  }
}

// Llistar maquinària
$maquinaria = db()->query("
  SELECT * FROM maquinaria
  ORDER BY idMaquina DESC
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Maquinària · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="grid">

  <div class="card span6">
    <h2>Nova màquina</h2>

    <form method="post">
      <input type="hidden" name="action" value="create">

      <label>Nom *</label>
      <input name="nom" required placeholder="Ex: Tractor John Deere">

      <label>Tipus</label>
      <input name="tipus" placeholder="Ex: Tractor, Segadora, Polvoritzador">

      <label>Matrícula</label>
      <input name="matricula">

      <label>Tipus de combustible</label>
      <input name="tipusCombustible" placeholder="Ex: Dièsel, Gasolina, Elèctric">

      <label>Cavalls (CV)</label>
      <input type="number" name="cavalls" min="0">

      <button class="btn" type="submit">Desar</button>
    </form>
  </div>

  <div class="card span6">
    <h2>Maquinària</h2>

    <?php if (!$maquinaria): ?>
      <p class="small">Encara no hi ha maquinària registrada.</p>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Tipus</th>
            <th>Matrícula</th>
            <th>Combustible</th>
            <th>CV</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($maquinaria as $m): ?>
            <tr>
              <td><?= (int)$m['idMaquina'] ?></td>
              <td><?= h($m['nom']) ?></td>
              <td><?= h($m['tipus'] ?? '') ?></td>
              <td><?= h($m['matricula'] ?? '') ?></td>
              <td><?= h($m['tipusCombustible'] ?? '') ?></td>
              <td><?= h($m['cavalls'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
