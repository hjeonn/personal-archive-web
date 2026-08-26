<?php
// ===== Site identity =====
define('SITE_TITLE', 'Personal Archive');
define('SITE_TAB_TITLE', 'Personal Archive');
define('SITE_SUBTITLE', '');

// Optional visual assets. Leave blank to disable.
// Examples: 'assets/favicon.png', 'assets/cursor.cur'
define('SITE_FAVICON', '');
define('SITE_CURSOR', '');
// Set both to 0 or greater only when you need a custom cursor hotspot.
define('SITE_CURSOR_HOTSPOT_X', -1);
define('SITE_CURSOR_HOTSPOT_Y', -1);

// ===== Admin =====
// Recommended: configure SITE_ADMIN_PASSWORD as a server environment variable.
// If it is empty, the admin login UI is disabled.
define('ADMIN_PASSWORD', getenv('SITE_ADMIN_PASSWORD') ?: '');

// ===== Storage =====
define('DATA_DIR', __DIR__ . '/data');
define('IMAGE_DIR', DATA_DIR . '/images');
define('PDF_DIR', DATA_DIR . '/pdfs');
define('THUMB_DIR', DATA_DIR . '/thumbs');
define('DB_IMAGES', DATA_DIR . '/img_db.json');
define('DB_PDFS', DATA_DIR . '/pdf_db.json');

define('MAX_IMAGE_SIZE', 10 * 2048 * 1024);
define('MAX_PDF_SIZE', 50 * 1024 * 1024);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp']);

define('IMAGES_PER_PAGE', 1);
define('PDFS_PER_PAGE', 1);

// Optional content gate used by image/PDF posts.
define('CONFIRM_QUESTION', '이 콘텐츠를 열람하시겠습니까?');

// ===== BGM =====
// Fill either a YouTube playlist ID or a single video ID.
define('BGM_PLAYLIST_ID', '');
define('BGM_VIDEO_ID', '');
define('BGM_DEFAULT_VOLUME', 50);
define('BGM_AUTOPLAY', true);

// Home accordion label.
define('HOME_TEXT', 'welcome');

foreach ([DATA_DIR, IMAGE_DIR, PDF_DIR, THUMB_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
foreach ([DB_IMAGES, DB_PDFS] as $f) {
    if (!file_exists($f)) file_put_contents($f, '[]');
}
if (session_status() === PHP_SESSION_NONE) session_start();
