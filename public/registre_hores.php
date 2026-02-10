<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/middleware/auth.php';

require_login();

$avui = date('Y-m-d');

/**
 * Retorna l'últim torn ACTIU d'avui (treballant/pausat)
 */
function get_active_shift_today(int $idTreballador, string $avui) {
  $st = db()->prepare("
    SELECT *
    FROM registre_hores
    WHERE idTreballador = ?
      AND data = ?
      AND estat IN ('treballant','pausat')
    ORDER BY id_registre DESC
    LIMIT 1
  ");
  $st->execute([$idTreballador, $avui]);
  return $st->fetch(PDO::FETCH_ASSOC);
}

// Accions (iniciar / pausar / reprendre / acabar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $idTreballador = (int)($_POST['idTreballador'] ?? 0);
  $acc = $_POST['acc'] ?? '';

  if ($idTreballador > 0) {

    if ($acc === 'inici') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if (!$actiu) {
        db()->prepare("
          INSERT INTO registre_hores (idTreballador, data, hora_inici, estat, pauses)
          VALUES (?, ?, NOW(), 'treballant', 0)
        ")->execute([$idTreballador, $avui]);
      }
    }

    if ($acc === 'pausa') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu && $actiu['estat'] === 'treballant') {
        db()->prepare("
          UPDATE registre_hores
          SET estat='pausat'
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }

    if ($acc === 'repren') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu && $actiu['estat'] === 'pausat') {
        db()->prepare("
          UPDATE registre_hores
          SET estat='treballant',
              pauses = pauses + 1
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }

    if ($acc === 'final') {
      $actiu = get_active_shift_today($idTreballador, $avui);
      if ($actiu) {
        db()->prepare("
          UPDATE registre_hores
          SET hora_fi = NOW(),
              estat='finalitzat'
          WHERE id_registre=?
        ")->execute([(int)$actiu['id_registre']]);
      }
    }
  }

  header("Location: registre_hores.php");
  exit;
}

/**
 * 1 fila per treballador amb l'últim torn d'avui (si existeix)
 */
$rows = db()->query("
  SELECT
    t.id,
    t.nom_complet,
    r.id_registre,
    r.estat,
    r.hora_inici,
    r.hora_fi,
    r.pauses,
    DATE_FORMAT(r.hora_inici, '%H:%i') AS hora_inici_hm,
    DATE_FORMAT(r.hora_fi, '%H:%i') AS hora_fi_hm
  FROM treballadors t
  LEFT JOIN registre_hores r
    ON r.id_registre = (
      SELECT r2.id_registre
      FROM registre_hores r2
      WHERE r2.idTreballador = t.id
        AND r2.data = CURDATE()
      ORDER BY r2.id_registre DESC
      LIMIT 1
    )
  ORDER BY t.nom_complet
")->fetchAll(PDO::FETCH_ASSOC);

$titol = "Registre d'hores · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';

/** segons -> HH:MM:SS */
function fmt_hms(int $sec): string {
  if ($sec < 0) $sec = 0;
  $h = intdiv($sec, 3600);
  $m = intdiv($sec % 3600, 60);
  $s = $sec % 60;
  return sprintf("%02d:%02d:%02d", $h, $m, $s);
}

/**
 * Calcula segons treballats fins ara:
 * - si està finalitzat: (fi - inici)
 * - si està treballant: (ara - inici)
 * - si està pausat: ho deixem “clavat” al valor que es veu al carregar la pàgina
 */
function worked_seconds(?string $hora_inici, ?string $hora_fi, string $estat): int {
  if (!$hora_inici) return 0;

  $start = strtotime($hora_inici);

  if ($estat === 'finalitzat' && $hora_fi) {
    $end = strtotime($hora_fi);
  } else {
    $end = time();
  }

  return (int)max(0, $end - $start);
}
?>

<div class="card">
  <h2>Registre d'hores (<?= htmlspecialchars($avui) ?>)</h2>

  <table class="table">
    <thead>
      <tr>
        <th>Treballador</th>
        <th>Inici</th>
        <th>Fi</th>
        <th>Temps</th>
        <th>Pauses</th>
        <th>Estat</th>
        <th>Accions</th>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $estat = $r['estat'] ?? '—';
          $te_actiu = in_array($estat, ['treballant','pausat'], true);

          $initSec = worked_seconds(
            $r['hora_inici'] ?? null,
            $r['hora_fi'] ?? null,
            $estat
          );

          $startTs = !empty($r['hora_inici']) ? strtotime($r['hora_inici']) : null;
          $endTs   = !empty($r['hora_fi']) ? strtotime($r['hora_fi']) : null;
        ?>
        <tr
          data-start-ts="<?= $startTs ?? '' ?>"
          data-end-ts="<?= $endTs ?? '' ?>"
          data-estat="<?= htmlspecialchars($estat) ?>"
        >
          <td><?= htmlspecialchars($r['nom_complet']) ?></td>
          <td><?= htmlspecialchars($r['hora_inici_hm'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['hora_fi_hm'] ?? '') ?></td>

          <td>
            <span class="work-timer"><?= htmlspecialchars(fmt_hms($initSec)) ?></span>
          </td>

          <td><?= (int)($r['pauses'] ?? 0) ?></td>
          <td><?= htmlspecialchars($estat) ?></td>

          <td>
            <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;">
              <input type="hidden" name="idTreballador" value="<?= (int)$r['id'] ?>">

              <?php if (!$te_actiu): ?>
                <button name="acc" value="inici" class="btn">Iniciar</button>

              <?php elseif ($estat === 'treballant'): ?>
                <button name="acc" value="pausa" class="btn secondary">Pausar</button>
                <button name="acc" value="final" class="btn">Acabar</button>

              <?php elseif ($estat === 'pausat'): ?>
                <button name="acc" value="repren" class="btn">Reprendre</button>
                <button name="acc" value="final" class="btn secondary">Acabar</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="small" style="margin-top:10px;opacity:.85">
    El camp <b>Temps</b> és un comptador en viu quan l’estat és <b>treballant</b>. En <b>pausat</b> queda fix.
  </p>
</div>

<script>
(function () {
  function fmt(sec) {
    sec = Math.max(0, Math.floor(sec));
    const h = String(Math.floor(sec / 3600)).padStart(2, '0');
    const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
    const s = String(sec % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
  }

  function computeWorked(row) {
    const startTs = parseInt(row.dataset.startTs || '0', 10);
    const endTs = parseInt(row.dataset.endTs || '0', 10);
    if (!startTs) return 0;

    const now = Math.floor(Date.now() / 1000);
    const end = endTs ? endTs : now;
    return Math.max(0, end - startTs);
  }

  function tick() {
    document.querySelectorAll('tr[data-start-ts]').forEach(row => {
      const timer = row.querySelector('.work-timer');
      if (!timer) return;

      const estat = row.dataset.estat || '';
      // actualitzem en viu NOMÉS si està treballant
      if (estat === 'treballant') {
        timer.textContent = fmt(computeWorked(row));
      }
    });
  }

  tick();
  setInterval(tick, 1000);
})();
</script>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
