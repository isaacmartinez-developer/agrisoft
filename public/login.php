<?php
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/helpers/flash.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!empty($_SESSION['user'])) {
  header('Location: index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  // IMPORTANT: camps segons el teu SQL
  // A la teva taula `usuaris` el camp es diu: contrasenya_enciptada (no encriptada)
  $st = db()->prepare("
    SELECT id, name, email, contrasenya_enciptada, role
    FROM usuaris
    WHERE email = ?
    LIMIT 1
  ");
  $st->execute([$email]);
  $u = $st->fetch(PDO::FETCH_ASSOC);

  if ($u && password_verify($pass, $u['contrasenya_enciptada'])) {
    $_SESSION['user'] = [
      'id'    => $u['id'],
      'name'  => $u['name'],
      'email' => $u['email'],
      'role'  => $u['role']
    ];

    flash_set("Benvingut/da, {$u['name']}!", "ok");
    header('Location: index.php');
    exit;
  }

  flash_set("Credencials incorrectes.", "bad");
}

$titol = "Inici de sessió · AGRISOFT";
include __DIR__ . '/../app/views/layout/header.php';
?>

<div style="display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 100px); padding: 20px;">
  <div class="card" style="width: 100%; max-width: 450px;">
    <h1 style="text-align: center;">Inicia sessió</h1>

    <form method="post">
      <label>Correu</label>
      <input name="email" type="email" required style="width: 100%;" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label>Contrasenya</label>
      <input name="password" type="password" required style="width: 100%;">

      <div class="login-actions">
        <button type="submit" class="btn btn-login">Entrar</button>
      </div>
    </form>

  </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
