<?php
define('SITE_TITLE', 'Archive');
define('SITE_TAB_TITLE', 'Archive');
define('SITE_SUBTITLE', '');

// Keep credentials out of the repository. Set ADMIN_PASSWORD in the server environment.
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '');

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

// Confirmation prompt used for blind posts.
define('CONFIRM_QUESTION', '이 콘텐츠를 열람하시겠습니까?');

// BGM settings. Add a playlist/video ID only in your deployment environment or private copy.
define('BGM_PLAYLIST_ID', '');
define('BGM_VIDEO_ID', '');
define('BGM_DEFAULT_VOLUME', 50);
define('BGM_AUTOPLAY', true);

define('HOME_TEXT', 'Archive');

foreach ([DATA_DIR, IMAGE_DIR, PDF_DIR, THUMB_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
foreach ([DB_IMAGES, DB_PDFS] as $f) {
    if (!file_exists($f)) file_put_contents($f, '[]');
}
if (session_status() === PHP_SESSION_NONE) session_start();
