<?php
require_once __DIR__ . '/config.php';

function db_read(string $dbFile): array {
    if (!file_exists($dbFile)) return [];
    return json_decode(file_get_contents($dbFile), true) ?: [];
}

function db_write(string $dbFile, array $data): bool {
    return file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
}

function is_admin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function admin_login(string $pw): bool {
    if (ADMIN_PASSWORD === '') return false;
    if (hash_equals(ADMIN_PASSWORD, $pw)) { $_SESSION['is_admin'] = true; return true; }
    return false;
}

function admin_logout(): void { unset($_SESSION['is_admin']); }

function gen_id(): string { return uniqid('', true); }

function safe_fn(string $ext): string {
    return time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

function get_ext(string $fn): string {
    return strtolower(pathinfo($fn, PATHINFO_EXTENSION));
}

function fmt_size(int $b): string {
    if ($b >= 1048576) return round($b / 1048576, 1) . 'MB';
    if ($b >= 1024) return round($b / 1024, 1) . 'KB';
    return $b . 'B';
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function is_pjax_request(): bool {
    return isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === '1';
}

function current_url_without_params(array $removeKeys): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';

    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    foreach ($removeKeys as $key) {
        unset($query[$key]);
    }

    return $path . ($query ? '?' . http_build_query($query) : '');
}

// ===== IMAGE: 포스트(페이지) 단위 =====
// 각 포스트는 images[] 배열을 가짐 (한 페이지에 여러 이미지 가능)

function migrate_image_entry(array $entry): array {
    // 구 형식(filename 단일) → 신 형식(images 배열) 변환
    if (!isset($entry['images'])) {
        $entry['images'] = [[
            'id' => $entry['id'] . '_0',
            'filename' => $entry['filename'],
            'original' => $entry['original'] ?? '',
            'size' => $entry['size'] ?? 0,
            'source' => $entry['source'] ?? 'upload',
        ]];
        unset($entry['filename'], $entry['original'], $entry['size'], $entry['source'], $entry['source_url']);
    }
    return $entry;
}

function upload_image(array $file, string $title = '', string $memo = '', bool $blind = false): array {
    $ext = get_ext($file['name']);
    if (!in_array($ext, ALLOWED_IMAGE_EXT))
        return ['ok' => false, 'error' => '허용되지 않는 형식'];
    if ($file['size'] > MAX_IMAGE_SIZE)
        return ['ok' => false, 'error' => '용량 초과 (' . fmt_size(MAX_IMAGE_SIZE) . ')'];

    $newName = safe_fn($ext);
    if (!move_uploaded_file($file['tmp_name'], IMAGE_DIR . '/' . $newName))
        return ['ok' => false, 'error' => '저장 실패'];
    chmod(IMAGE_DIR . '/' . $newName, 0644);

    $entry = [
        'id' => gen_id(),
        'images' => [[
            'id' => gen_id(),
            'filename' => $newName,
            'original' => $file['name'],
            'size' => $file['size'],
            'source' => 'upload',
        ]],
        'title' => $title,
        'memo' => $memo,
        'blind' => $blind,
        'date' => date('Y-m-d H:i:s'),
    ];
    $db = db_read(DB_IMAGES);
    array_unshift($db, $entry);
    db_write(DB_IMAGES, $db);
    return ['ok' => true];
}

function upload_image_from_url(string $url, string $title = '', string $memo = '', bool $blind = false): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'Mozilla/5.0']);
    $data = curl_exec($ch);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || !$data) return ['ok' => false, 'error' => '다운로드 실패'];

    $ext = get_ext(parse_url($url, PHP_URL_PATH) ?? '');
    if (!in_array($ext, ALLOWED_IMAGE_EXT)) {
        if (strpos($ct, 'jpeg') !== false) $ext = 'jpg';
        elseif (strpos($ct, 'png') !== false) $ext = 'png';
        elseif (strpos($ct, 'gif') !== false) $ext = 'gif';
        elseif (strpos($ct, 'webp') !== false) $ext = 'webp';
        else return ['ok' => false, 'error' => '지원되지 않는 형식'];
    }
    if (strlen($data) > MAX_IMAGE_SIZE) return ['ok' => false, 'error' => '용량 초과'];

    $newName = safe_fn($ext);
    file_put_contents(IMAGE_DIR . '/' . $newName, $data);
    chmod(IMAGE_DIR . '/' . $newName, 0644);

    $entry = [
        'id' => gen_id(),
        'images' => [[
            'id' => gen_id(),
            'filename' => $newName,
            'original' => basename(parse_url($url, PHP_URL_PATH) ?? 'image.' . $ext),
            'size' => strlen($data),
            'source' => 'url',
        ]],
        'title' => $title,
        'memo' => $memo,
        'blind' => $blind,
        'date' => date('Y-m-d H:i:s'),
    ];
    $db = db_read(DB_IMAGES);
    array_unshift($db, $entry);
    db_write(DB_IMAGES, $db);
    return ['ok' => true];
}

// 기존 포스트에 이미지 추가
function add_image_to_post(string $postId, array $file): array {
    $ext = get_ext($file['name']);
    if (!in_array($ext, ALLOWED_IMAGE_EXT))
        return ['ok' => false, 'error' => '허용되지 않는 형식'];
    if ($file['size'] > MAX_IMAGE_SIZE)
        return ['ok' => false, 'error' => '용량 초과'];

    $newName = safe_fn($ext);
    if (!move_uploaded_file($file['tmp_name'], IMAGE_DIR . '/' . $newName))
        return ['ok' => false, 'error' => '저장 실패'];
    chmod(IMAGE_DIR . '/' . $newName, 0644);

    $db = db_read(DB_IMAGES);
    foreach ($db as &$entry) {
        $entry = migrate_image_entry($entry);
        if ($entry['id'] === $postId) {
            $entry['images'][] = [
                'id' => gen_id(),
                'filename' => $newName,
                'original' => $file['name'],
                'size' => $file['size'],
                'source' => 'upload',
            ];
            db_write(DB_IMAGES, $db);
            return ['ok' => true];
        }
    }
    unset($entry);
    return ['ok' => false, 'error' => '포스트를 찾을 수 없음'];
}

function add_image_to_post_url(string $postId, string $url): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'Mozilla/5.0']);
    $data = curl_exec($ch);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (!$data) return ['ok' => false, 'error' => '다운로드 실패'];

    $ext = get_ext(parse_url($url, PHP_URL_PATH) ?? '');
    if (!in_array($ext, ALLOWED_IMAGE_EXT)) {
        if (strpos($ct, 'jpeg') !== false) $ext = 'jpg';
        elseif (strpos($ct, 'png') !== false) $ext = 'png';
        elseif (strpos($ct, 'gif') !== false) $ext = 'gif';
        else return ['ok' => false, 'error' => '지원되지 않는 형식'];
    }

    $newName = safe_fn($ext);
    file_put_contents(IMAGE_DIR . '/' . $newName, $data);

    $db = db_read(DB_IMAGES);
    foreach ($db as &$entry) {
        $entry = migrate_image_entry($entry);
        if ($entry['id'] === $postId) {
            $entry['images'][] = [
                'id' => gen_id(),
                'filename' => $newName,
                'original' => basename(parse_url($url, PHP_URL_PATH) ?? 'image.' . $ext),
                'size' => strlen($data),
                'source' => 'url',
            ];
            db_write(DB_IMAGES, $db);
            return ['ok' => true];
        }
    }
    unset($entry);
    return ['ok' => false, 'error' => '포스트를 찾을 수 없음'];
}

// ===== URL 참조 (다운로드 없이 외부 URL을 직접 사용) =====

function ref_image_url(string $url, string $title = '', string $memo = '', bool $blind = false): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $entry = [
        'id' => gen_id(),
        'images' => [[
            'id' => gen_id(),
            'filename' => '',
            'ref_url' => $url,
            'original' => basename(parse_url($url, PHP_URL_PATH) ?? ''),
            'size' => 0,
            'source' => 'ref',
        ]],
        'title' => $title,
        'memo' => $memo,
        'blind' => $blind,
        'date' => date('Y-m-d H:i:s'),
    ];
    $db = db_read(DB_IMAGES);
    array_unshift($db, $entry);
    db_write(DB_IMAGES, $db);
    return ['ok' => true];
}

function add_image_ref_to_post(string $postId, string $url): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $db = db_read(DB_IMAGES);
    foreach ($db as &$entry) {
        $entry = migrate_image_entry($entry);
        if ($entry['id'] === $postId) {
            $entry['images'][] = [
                'id' => gen_id(),
                'filename' => '',
                'ref_url' => $url,
                'original' => basename(parse_url($url, PHP_URL_PATH) ?? ''),
                'size' => 0,
                'source' => 'ref',
            ];
            db_write(DB_IMAGES, $db);
            return ['ok' => true];
        }
    }
    unset($entry);
    return ['ok' => false, 'error' => '포스트를 찾을 수 없음'];
}

function ref_pdf_url(string $url, string $title = '', string $memo = '', bool $blind = false): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $entry = [
        'id' => gen_id(),
        'filename' => '',
        'ref_url' => $url,
        'original' => basename(parse_url($url, PHP_URL_PATH) ?? ''),
        'title' => $title ?: basename(parse_url($url, PHP_URL_PATH) ?? ''),
        'memo' => $memo,
        'blind' => $blind,
        'size' => 0,
        'date' => date('Y-m-d H:i:s'),
        'source' => 'ref',
    ];
    $db = db_read(DB_PDFS);
    array_unshift($db, $entry);
    db_write(DB_PDFS, $db);
    return ['ok' => true];
}

// 이미지 src 헬퍼: ref_url이 있으면 외부 URL, 없으면 로컬 경로
function img_src(array $img): string {
    return (!empty($img['ref_url'])) ? $img['ref_url'] : 'data/images/' . $img['filename'];
}

function pdf_src(array $entry): string {
    return (!empty($entry['ref_url'])) ? $entry['ref_url'] : 'data/pdfs/' . $entry['filename'];
}

// 포스트 내 개별 이미지 교체
function replace_image_in_post(string $postId, string $imgId, ?array $file = null, ?string $url = null): array {
    $db = db_read(DB_IMAGES);
    foreach ($db as &$entry) {
        $entry = migrate_image_entry($entry);
        if ($entry['id'] !== $postId) continue;

        foreach ($entry['images'] as &$img) {
            if ($img['id'] !== $imgId) continue;

            if ($file && !empty($file['name'])) {
                // 파일 교체
                $ext = get_ext($file['name']);
                if (!in_array($ext, ALLOWED_IMAGE_EXT))
                    return ['ok' => false, 'error' => '허용되지 않는 형식'];
                $newName = safe_fn($ext);
                if (!move_uploaded_file($file['tmp_name'], IMAGE_DIR . '/' . $newName))
                    return ['ok' => false, 'error' => '저장 실패'];
                // 기존 로컬 파일 삭제
                if (!empty($img['filename'])) {
                    $old = IMAGE_DIR . '/' . $img['filename'];
                    if (file_exists($old)) unlink($old);
                }
                $img['filename'] = $newName;
                $img['ref_url'] = '';
                $img['original'] = $file['name'];
                $img['size'] = $file['size'];
                $img['source'] = 'upload';
            } elseif ($url) {
                // URL 참조로 교체
                if (!empty($img['filename'])) {
                    $old = IMAGE_DIR . '/' . $img['filename'];
                    if (file_exists($old)) unlink($old);
                }
                $img['filename'] = '';
                $img['ref_url'] = $url;
                $img['original'] = basename(parse_url($url, PHP_URL_PATH) ?? '');
                $img['size'] = 0;
                $img['source'] = 'ref';
            } else {
                return ['ok' => false, 'error' => '파일 또는 URL 필요'];
            }

            db_write(DB_IMAGES, $db);
            return ['ok' => true];
        }
        unset($img);
    }
    unset($entry);
    return ['ok' => false, 'error' => '이미지를 찾을 수 없음'];
}

// ===== 공통: 수정, 삭제 =====

function edit_entry(string $type, string $id, string $title, string $memo, bool $blind = false): bool {
    $dbFile = ($type === 'image') ? DB_IMAGES : DB_PDFS;
    $db = db_read($dbFile);
    foreach ($db as &$entry) {
        if ($entry['id'] === $id) {
            $entry['title'] = $title;
            $entry['memo'] = $memo;
            $entry['blind'] = $blind;
            db_write($dbFile, $db);
            return true;
        }
    }
    unset($entry);
    return false;
}

function delete_entry(string $type, string $id): bool {
    if (!in_array($type, ['image', 'pdf'], true)) return false;

    $dbFile = ($type === 'image') ? DB_IMAGES : DB_PDFS;
    $fileDir = ($type === 'image') ? IMAGE_DIR : PDF_DIR;
    $db = db_read($dbFile);

    foreach ($db as $i => $entry) {
        if (($entry['id'] ?? '') !== $id) continue;

        $filesToDelete = [];

        if ($type === 'image') {
            $entry = migrate_image_entry($entry);
            foreach ($entry['images'] as $img) {
                $filename = $img['filename'] ?? '';
                if ($filename !== '') $filesToDelete[] = $filename;
            }
        } else {
            $filename = $entry['filename'] ?? '';
            if ($filename !== '') $filesToDelete[] = $filename;
        }

        unset($db[$i]);

        // DB에서 항목을 제거하지 못했다면 실제 파일도 지우지 않는다.
        if (!db_write($dbFile, array_values($db))) {
            return false;
        }

        // DB 반영 후 로컬 파일 정리. 외부 URL 참조는 filename이 비어 있어 건너뛴다.
        foreach ($filesToDelete as $filename) {
            $path = $fileDir . '/' . $filename;
            if (is_file($path)) @unlink($path);
        }

        return true;
    }

    return false;
}

// 포스트 내 개별 이미지 삭제
function delete_single_image(string $postId, string $imgId): bool {
    $db = db_read(DB_IMAGES);

    foreach ($db as $i => $entry) {
        $entry = migrate_image_entry($entry);
        if (($entry['id'] ?? '') !== $postId) continue;

        foreach ($entry['images'] as $j => $img) {
            if (($img['id'] ?? '') !== $imgId) continue;

            $filename = $img['filename'] ?? '';

            unset($entry['images'][$j]);
            $entry['images'] = array_values($entry['images']);

            if (empty($entry['images'])) {
                // 마지막 이미지라면 포스트 자체를 DB에서 제거한다.
                unset($db[$i]);
            } else {
                $db[$i] = $entry;
            }

            if (!db_write(DB_IMAGES, array_values($db))) {
                return false;
            }

            if ($filename !== '') {
                $path = IMAGE_DIR . '/' . $filename;
                if (is_file($path)) @unlink($path);
            }

            return true;
        }

        return false;
    }

    return false;
}

// ===== PDF =====

function upload_pdf(array $file, string $title = '', string $memo = '', bool $blind = false): array {
    if (get_ext($file['name']) !== 'pdf')
        return ['ok' => false, 'error' => 'PDF만 가능'];
    if ($file['size'] > MAX_PDF_SIZE)
        return ['ok' => false, 'error' => '용량 초과'];

    $newName = safe_fn('pdf');
    if (!move_uploaded_file($file['tmp_name'], PDF_DIR . '/' . $newName))
        return ['ok' => false, 'error' => '저장 실패'];
    chmod(PDF_DIR . '/' . $newName, 0644);

    $entry = [
        'id' => gen_id(),
        'filename' => $newName,
        'original' => $file['name'],
        'title' => $title ?: pathinfo($file['name'], PATHINFO_FILENAME),
        'memo' => $memo,
        'blind' => $blind,
        'size' => $file['size'],
        'date' => date('Y-m-d H:i:s'),
        'source' => 'upload',
    ];
    $db = db_read(DB_PDFS);
    array_unshift($db, $entry);
    db_write(DB_PDFS, $db);
    return ['ok' => true];
}

function upload_pdf_from_url(string $url, string $title = '', string $memo = '', bool $blind = false): array {
    $url = filter_var($url, FILTER_VALIDATE_URL);
    if (!$url) return ['ok' => false, 'error' => '잘못된 URL'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_USERAGENT => 'Mozilla/5.0']);
    $data = curl_exec($ch);
    curl_close($ch);

    if (!$data) return ['ok' => false, 'error' => '다운로드 실패'];
    if (strlen($data) > MAX_PDF_SIZE) return ['ok' => false, 'error' => '용량 초과'];
    if (substr($data, 0, 4) !== '%PDF') return ['ok' => false, 'error' => '유효한 PDF가 아님'];

    $newName = safe_fn('pdf');
    file_put_contents(PDF_DIR . '/' . $newName, $data);

    $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
    $entry = [
        'id' => gen_id(),
        'filename' => $newName,
        'original' => basename($urlPath) ?: 'external.pdf',
        'title' => $title ?: pathinfo(basename($urlPath), PATHINFO_FILENAME),
        'memo' => $memo,
        'blind' => $blind,
        'size' => strlen($data),
        'date' => date('Y-m-d H:i:s'),
        'source' => 'url',
    ];
    $db = db_read(DB_PDFS);
    array_unshift($db, $entry);
    db_write(DB_PDFS, $db);
    return ['ok' => true];
}
