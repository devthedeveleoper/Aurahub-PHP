<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/wrapper.php';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

// Fallbacks so the app works even if the mbstring extension is not enabled.
if (!function_exists('mb_strlen'))      { function mb_strlen($s) { return (int)preg_match_all('/./us', (string)$s); } }
if (!function_exists('mb_substr'))      { function mb_substr($s, $a, $l = null) { preg_match_all('/./us', (string)$s, $m); return implode('', array_slice($m[0], $a, $l)); } }
if (!function_exists('mb_strtoupper'))  { function mb_strtoupper($s) { return strtoupper((string)$s); } }

function db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $dbUri = defined('DB_URI') && DB_URI ? DB_URI : getenv('DATABASE_URL');
        $parsed = parse_url($dbUri);
        $host = $parsed['host'] ?? 'localhost';
        $port = $parsed['port'] ?? 3306;
        $user = $parsed['user'] ?? 'root';
        $pass = $parsed['pass'] ?? '';
        $dbname = ltrim($parsed['path'] ?? '', '/');
        
        $pdo = new PDO(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }

function user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $s = db()->prepare('SELECT id, username, role, account_status, avatar_url, keep_history FROM users WHERE id = ?');
            $s->execute([$_SESSION['uid']]);
            $u = $s->fetch() ?: null;
            if ($u && $u['account_status'] !== 'active') {
              unset($_SESSION['uid']);
              session_regenerate_id(true);
              $u = null;
            }
        }
    }
    return $u;
}
function require_login(): void { if (!user()) redirect('/aurahub/public/login'); }
function require_admin(): array {
  $u = user();
  if (!$u) redirect('/aurahub/public/login');
  if (($u['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    exit('Administrator access required.');
  }
  return $u;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid form token. Go back and try again.'); }
}

function time_ago(string $ts): string {
    $d = time() - strtotime($ts);
    foreach ([31536000 => 'year', 2592000 => 'month', 86400 => 'day', 3600 => 'hour', 60 => 'minute'] as $sec => $name) {
        if ($d >= $sec) { $n = intdiv($d, $sec); return $n . ' ' . $name . ($n > 1 ? 's' : '') . ' ago'; }
    }
    return 'just now';
}

function page_header(string $title = 'Aurahub'): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $u = user(); $q = $_GET['q'] ?? '';
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/aurahub/assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head><body>
<header class="bar">
  <a class="brand" href="/aurahub/public/">▶ Aurahub</a>
  <form class="search" action="/aurahub/public/" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search videos" aria-label="Search videos">
  </form>
  <nav>
    <a href="/aurahub/public/communities">Communities</a>
    <?php if ($u): ?>
      <a class="btn" href="/aurahub/public/upload">Upload</a>
      <div class="dropdown">
        <span class="who dropbtn" onclick="document.getElementById('navDropdown').classList.toggle('show');">
          <?php if (!empty($u['avatar_url'])): ?>
            <img src="<?= e($u['avatar_url']) ?>" alt="" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; pointer-events: none;">
          <?php else: ?>
            <span style="width: 28px; height: 28px; border-radius: 50%; background: var(--bg-alt); display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; pointer-events: none;">
              <?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?>
            </span>
          <?php endif; ?>
          <span style="pointer-events: none;"><?= e($u['username']) ?> ▾</span>
        </span>
        <div class="dropdown-content" id="navDropdown">
          <a href="/aurahub/public/account">Account</a>
          <a href="/aurahub/public/subscriptions">Subscriptions</a>
          <a href="/aurahub/public/messages">Messages</a>
          <a href="/aurahub/public/communities">Communities</a>
          <a href="/aurahub/public/analytics">Analytics</a>
          <a href="/aurahub/public/playlists">Playlists</a>
          <a href="/aurahub/public/history">History</a>
          <a href="/aurahub/public/watch_later">Watch later</a>
          <a href="/aurahub/public/my_reports">My reports</a>
          <?php if (($u['role'] ?? 'user') === 'admin'): ?>
            <div class="dropdown-divider"></div>
            <a href="/aurahub/public/admin_users">Users (Admin)</a>
            <a href="/aurahub/public/moderation">Moderation (Admin)</a>
          <?php endif; ?>
          <div class="dropdown-divider"></div>
          <a href="/aurahub/public/logout">Log out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/aurahub/public/login">Log in</a>
      <a class="btn" href="/aurahub/public/register">Sign up</a>
    <?php endif; ?>
  </nav>
</header>
<main>
<script>
window.onclick = function(event) {
  if (!event.target.closest('.dropbtn')) {
    let dropdowns = document.getElementsByClassName("dropdown-content");
    for (let i = 0; i < dropdowns.length; i++) {
      if (dropdowns[i].classList.contains('show')) dropdowns[i].classList.remove('show');
    }
  }
}
</script>
<?php
}
function page_footer(): void { 
  echo "</main>
  <footer style=\"text-align: center; padding: 2rem; margin-top: 2rem; border-top: 1px solid var(--border); color: var(--text-secondary); font-size: 13px;\">
    <a href=\"/aurahub/public/policy\" style=\"color: var(--text-secondary);\">Content Policy</a> &middot; 
    <a href=\"/aurahub/public/transparency\" style=\"color: var(--text-secondary);\">Transparency Report</a> &middot; 
    &copy; " . date('Y') . " Aurahub
  </footer>
  </body></html>"; 
}
