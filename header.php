<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= h(SITE_TAB_TITLE) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php
if (isset($_POST['do_login'])) {
    admin_login($_POST['admin_password'] ?? '');
    echo '<script>location.href="'.h(strtok($_SERVER['REQUEST_URI'],'?')).'";</script>';
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    admin_logout();
    echo '<script>location.href="'.h(strtok($_SERVER['REQUEST_URI'],'?')).'";</script>';
    exit;
}
?>

<nav class="sidebar">
  <a href="index.php" class="menu-btn">home</a>
  <a href="gallery.php" class="menu-btn">jpg</a>
  <a href="pdfboard.php" class="menu-btn">txt</a>
  <?php if (is_admin()): ?>
    <a href="?action=logout" class="menu-btn" onclick="return confirm('logout?')">LOGOUT</a>
  <?php else: ?>
    <button class="menu-btn" onclick="var el=document.getElementById('loginRow');el.style.display=el.style.display==='flex'?'none':'flex';">LOGIN</button>
  <?php endif; ?>
  <?php if (isset($pagebar)) echo $pagebar; ?>
</nav>

<div class="main-area">

<?php if (!is_admin()): ?>
<div class="login-inline" id="loginRow">
  <form method="post" action="<?= h($_SERVER['REQUEST_URI']) ?>" style="display:flex;align-items:center;gap:4px;margin:0;">
    <input type="password" name="admin_password" required placeholder="pass">
    <button type="submit" name="do_login" class="btn">OK</button>
  </form>
</div>
<?php endif; ?>
