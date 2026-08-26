<?php
require_once __DIR__ . '/functions.php';

if (isset($_POST['do_login'])) {
    admin_login($_POST['admin_password'] ?? '');

    // 일반 요청에서는 POST 재전송을 막고, PJAX에서는 그대로 새 화면을 렌더링한다.
    if (!is_pjax_request()) {
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? 'index.php'), true, 303);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
    $cleanUrl = current_url_without_params(['action']);

    if (is_pjax_request()) {
        header('X-PJAX-URL: ' . $cleanUrl);
    } else {
        header('Location: ' . $cleanUrl, true, 303);
        exit;
    }
}

$pjaxRequest = is_pjax_request();
$adminEnabled = defined('ADMIN_PASSWORD') && ADMIN_PASSWORD !== '';
$documentTitle = isset($pageTitle) && $pageTitle !== '' ? $pageTitle : SITE_TAB_TITLE;

$styleVersion = @filemtime(__DIR__ . '/style.css') ?: 1;
$appVersion = @filemtime(__DIR__ . '/app.js') ?: 1;

$cursorPath = defined('SITE_CURSOR') ? trim((string) SITE_CURSOR) : '';
$cursorHotspot = '';
if (
    $cursorPath !== '' &&
    defined('SITE_CURSOR_HOTSPOT_X') &&
    defined('SITE_CURSOR_HOTSPOT_Y') &&
    (int) SITE_CURSOR_HOTSPOT_X >= 0 &&
    (int) SITE_CURSOR_HOTSPOT_Y >= 0
) {
    $cursorHotspot = ' ' . (int) SITE_CURSOR_HOTSPOT_X . ' ' . (int) SITE_CURSOR_HOTSPOT_Y;
}

if (!$pjaxRequest):
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h($documentTitle) ?></title>
<link rel="stylesheet" href="style.css?v=<?= $styleVersion ?>">
<?php if (defined('SITE_FAVICON') && SITE_FAVICON !== ''): ?>
<link rel="icon" href="<?= h(SITE_FAVICON) ?>">
<?php endif; ?>
<?php if ($cursorPath !== ''): ?>
<style>
html, body {
    cursor: url(<?= json_encode($cursorPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)<?= $cursorHotspot ?>, auto;
}
a, button, summary, label, input[type="button"], input[type="submit"], input[type="range"], input[type="checkbox"], input[type="radio"] {
    cursor: url(<?= json_encode($cursorPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)<?= $cursorHotspot ?>, pointer;
}
input[type="text"], input[type="password"], input[type="url"], textarea {
    cursor: url(<?= json_encode($cursorPath, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)<?= $cursorHotspot ?>, text;
}
</style>
<?php endif; ?>
<script src="app.js?v=<?= $appVersion ?>" defer></script>
</head>
<body>
<?php endif; ?>

<nav class="sidebar">
  <a href="index.php" class="menu-btn">home</a>
  <a href="gallery.php" class="menu-btn">image</a>
  <a href="pdfboard.php" class="menu-btn">docs</a>
  <?php if (is_admin()): ?>
    <a href="?action=logout" class="menu-btn" onclick="return confirm('logout?')">LOGOUT</a>
  <?php elseif ($adminEnabled): ?>
    <button class="menu-btn" onclick="var el=document.getElementById('loginRow');if(el)el.style.display=el.style.display==='flex'?'none':'flex';">LOGIN</button>
  <?php endif; ?>
  <?php if (isset($pagebar)) echo $pagebar; ?>
</nav>

<div class="main-area" data-page-title="<?= h($documentTitle) ?>">

<?php if (!is_admin() && $adminEnabled): ?>
<div class="login-inline" id="loginRow">
  <form method="post" action="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>" style="display:flex;align-items:center;gap:4px;margin:0;">
    <input type="hidden" name="do_login" value="1">
    <input type="password" name="admin_password" required placeholder="pass">
    <button type="submit" class="btn">OK</button>
  </form>
</div>
<?php endif; ?>
