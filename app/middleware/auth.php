<?php
function require_login(): void {
  if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }
}

/**
 * Retorna el rol actual (admin|manager|worker) o '' si no hi ha sessió.
 */
function current_role(): string {
  return $_SESSION['user']['role'] ?? '';
}

/**
 * Retorna true si l'usuari actual té un dels rols indicats.
 */
function has_role(array $roles): bool {
  $r = current_role();
  return $r !== '' && in_array($r, $roles, true);
}

/**
 * Força que l'usuari tingui un dels rols indicats.
 */
function require_role(array $roles): void {
  require_login();
  if (!has_role($roles)) {
    http_response_code(403);
    echo "Accés denegat.";
    exit;
  }
}

/**
 * Permisos bàsics (RBAC simple)
 * - admin: tot
 * - manager: gestiona dades (crear/editar/eliminar)
 * - worker: només lectura
 */
function can_manage(): bool {
  return has_role(['admin','manager']);
}

