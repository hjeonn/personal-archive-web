<?php
require_once __DIR__ . '/config.php';
$pageTitle = SITE_TAB_TITLE;
$currentPage = 'home';
$pagebar = '';
require_once __DIR__ . '/header.php';
?>

<div class="home-text"><?= h(HOME_TEXT) ?></div>

<?php require_once __DIR__ . '/footer.php'; ?>
