<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $act = $_POST['act'] ?? '';
    $blind = isset($_POST['blind']);

    if ($act === 'new_file' && !empty($_FILES['pdffile']['name'])) {
        $r = upload_pdf($_FILES['pdffile'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), $blind);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'new_url' && !empty($_POST['pdf_url'])) {
        $r = ref_pdf_url($_POST['pdf_url'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), $blind);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'edit' && !empty($_POST['post_id'])) {
        $ok = edit_entry('pdf', $_POST['post_id'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), isset($_POST['blind']));
        $msg = $ok ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">수정 실패</div>';
    } elseif ($act === 'delete_post' && !empty($_POST['post_id'])) {
        $ok = delete_entry('pdf', $_POST['post_id']);
        $msg = $ok ? '<div class="msg msg-ok">deleted</div>' : '<div class="msg msg-err">삭제 실패</div>';
    }
}

$db = db_read(DB_PDFS);
$total = count($db);
$totalPages = max(1, $total);
$page = max(1, min($totalPages, intval($_GET['page'] ?? 1)));
$cur = $db[$page - 1] ?? null;

$pagebar = '<div class="pagebar">';
if ($page > 1) $pagebar .= '<a href="?page='.($page-1).'" class="arrow">&#9650;</a>';
for ($i = 1; $i <= $totalPages; $i++) {
    $pagebar .= ($i === $page) ? '<span class="current">'.$i.'</span>' : '<a href="?page='.$i.'">'.$i.'</a>';
}
if ($page < $totalPages) $pagebar .= '<a href="?page='.($page+1).'" class="arrow">&#9660;</a>';
$pagebar .= '</div>';

$currentPage = 'pdf';
require_once __DIR__ . '/header.php';
echo $msg;
?>

<?php if (is_admin()): ?>
<details class="upload-panel">
  <summary>NEW POST</summary>
  <div class="upload-tabs">
    <input type="radio" name="t" id="t1" checked><label for="t1" onclick="showForm('pdf','file')">file</label>
    <input type="radio" name="t" id="t2"><label for="t2" onclick="showForm('pdf','url')">url</label>
  </div>
  <form method="post" enctype="multipart/form-data" id="pdf_form_file" class="upload-form active">
    <input type="hidden" name="act" value="new_file">
    <div class="form-row">
      <div class="form-group"><label>file</label><input type="file" name="pdffile" accept=".pdf" required></div>
      <div class="form-group"><label>title</label><input type="text" name="title"></div>
    </div>
    <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="3"></textarea></div>
    <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;"> blind</label></div>
    <button type="submit" class="btn">UPLOAD</button>
  </form>
  <form method="post" id="pdf_form_url" class="upload-form">
    <input type="hidden" name="act" value="new_url">
    <div class="form-row">
      <div class="form-group"><label>url</label><input type="url" name="pdf_url" required></div>
      <div class="form-group"><label>title</label><input type="text" name="title"></div>
    </div>
    <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="3"></textarea></div>
    <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;"> blind</label></div>
    <button type="submit" class="btn">SAVE</button>
  </form>
</details>
<?php endif; ?>

<?php if (!$cur): ?>
  <div class="empty-state">-</div>
<?php else: ?>

<?php $isBlind = !empty($cur['blind']); ?>
<?php if ($isBlind): ?>
<div class="gate-overlay" id="gate">
  <p><?= h(CONFIRM_QUESTION) ?></p>
  <div class="confirm-btns">
    <button class="btn" onclick="reveal()">Yes</button>
    <button class="btn" onclick="decline()">No</button>
  </div>
</div>
<?php endif; ?>

<div class="<?= $isBlind ? 'gated-content' : '' ?>" id="content">
  <div class="pdf-post">
    <div class="post-title"><?= h($cur['title']) ?></div>
    <?php /*<div class="post-meta"><?= h($cur['date']) ?><?= $cur['size'] ? ' | ' . fmt_size($cur['size']) : '' ?></div>*/ ?>
    <?php if (!empty($cur['memo'])): ?>
      <div class="post-memo"><?= nl2br(h($cur['memo'])) ?></div>
    <?php endif; ?>

    <div class="pdf-viewer revealed" id="pdfViewer">
      <iframe id="pdfFrame" src="<?= h(pdf_src($cur)) ?>"></iframe>
    </div>

    <?php if (is_admin()): ?>
    <div style="margin-top:8px;border-top:1px dotted #ddd;padding-top:8px;">
      <details class="upload-panel" style="margin-bottom:6px;">
        <summary>EDIT</summary>
        <form method="post" style="margin-top:4px;">
          <input type="hidden" name="act" value="edit">
          <input type="hidden" name="post_id" value="<?= h($cur['id']) ?>">
          <div class="form-group" style="margin-bottom:4px"><label>title</label><input type="text" name="title" value="<?= h($cur['title']) ?>"></div>
          <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="3"><?= h($cur['memo']) ?></textarea></div>
          <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;" <?= !empty($cur['blind']) ? 'checked' : '' ?>> blind</label></div>
          <button type="submit" class="btn">SAVE</button>
        </form>
      </details>
      <form method="post" class="inline-action" onsubmit="return confirm('삭제?')">
        <input type="hidden" name="act" value="delete_post">
        <input type="hidden" name="post_id" value="<?= h($cur['id']) ?>">
        <button type="submit" class="link-button danger">[DELETE]</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>


<?php require_once __DIR__ . '/footer.php'; ?>
