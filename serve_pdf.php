<?php
/**
 * PDF 파일 서빙 (직접 접근 방지용, 선택사항)
 * pdfboard.php에서 data/pdfs/파일명 대신 serve_pdf.php?id=xxx 로 대체 가능
 */
require_once __DIR__ . '/functions.php';

$id = $_GET['id'] ?? '';
if (!$id) { http_response_code(400); exit('Bad request'); }

$db = db_read(DB_PDFS);
$found = null;
foreach ($db as $entry) {
    if ($entry['id'] === $id) { $found = $entry; break; }
}

if (!$found) { http_response_code(404); exit('Not found'); }

$path = PDF_DIR . '/' . $found['filename'];
if (!file_exists($path)) { http_response_code(404); exit('File not found'); }

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($found['original']) . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');

readfile($path);
