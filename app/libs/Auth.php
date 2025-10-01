<?php
class Auth {
  public static function rol(): ?string { return $_SESSION['user']['rol'] ?? null; }
  public static function id(): ?int { return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null; }
  public static function rutPersona(): ?int {
    return isset($_SESSION['user']['rut_persona']) ? (int)$_SESSION['user']['rut_persona'] : null;
  }
  public static function can(string $p): bool {
    return in_array('*', $_SESSION['perms'] ?? [], true) || in_array($p, $_SESSION['perms'] ?? [], true);
  }
  public static function reloadPerms(): void {
    $_SESSION['perms'] = (self::rol()==='ADMIN') ? ['*'] : [];
  }
}
