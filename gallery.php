<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$msg = '';

// === Actions ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_admin()) {
    $act = $_POST['act'] ?? '';

    $blind = isset($_POST['blind']);
    if ($act === 'new_file' && !empty($_FILES['imagefile']['name'])) {
        $r = upload_image($_FILES['imagefile'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), $blind);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'new_url' && !empty($_POST['image_url'])) {
        $r = ref_image_url($_POST['image_url'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), $blind);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'add_file' && !empty($_FILES['addfile']['name']) && !empty($_POST['post_id'])) {
        $r = add_image_to_post($_POST['post_id'], $_FILES['addfile']);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'add_url' && !empty($_POST['add_url']) && !empty($_POST['post_id'])) {
        $r = add_image_ref_to_post($_POST['post_id'], $_POST['add_url']);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'replace_file' && !empty($_FILES['repfile']['name']) && !empty($_POST['post_id']) && !empty($_POST['img_id'])) {
        $r = replace_image_in_post($_POST['post_id'], $_POST['img_id'], $_FILES['repfile']);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'replace_url' && !empty($_POST['rep_url']) && !empty($_POST['post_id']) && !empty($_POST['img_id'])) {
        $r = replace_image_in_post($_POST['post_id'], $_POST['img_id'], null, $_POST['rep_url']);
        $msg = $r['ok'] ? '<div class="msg msg-ok">ok</div>' : '<div class="msg msg-err">'.h($r['error']).'</div>';
    } elseif ($act === 'edit' && !empty($_POST['post_id'])) {
        edit_entry('image', $_POST['post_id'], trim($_POST['title'] ?? ''), trim($_POST['memo'] ?? ''), isset($_POST['blind']));
        $msg = '<div class="msg msg-ok">ok</div>';
    }
}

if (isset($_GET['del_post']) && is_admin()) {
    delete_entry('image', $_GET['del_post']);
    echo '<script>location.href="gallery.php";</script>'; exit;
}
if (isset($_GET['del_img'], $_GET['from_post']) && is_admin()) {
    delete_single_image($_GET['from_post'], $_GET['del_img']);
    echo '<script>location.href="gallery.php?page='.intval($_GET['page'] ?? 1).'";</script>'; exit;
}

// === Data + pagination ===
$db = db_read(DB_IMAGES);
// migrate
foreach ($db as &$e) $e = migrate_image_entry($e);
unset($e);

$total = count($db);
$totalPages = max(1, $total);
$page = max(1, min($totalPages, intval($_GET['page'] ?? 1)));
$post = $db[$page - 1] ?? null;

// Pagebar
$pagebar = '<div class="pagebar">';
if ($page > 1) $pagebar .= '<a href="?page='.($page-1).'" class="arrow">&#9650;</a>';
for ($i = 1; $i <= $totalPages; $i++) {
    $pagebar .= ($i === $page)
        ? '<span class="current">'.$i.'</span>'
        : '<a href="?page='.$i.'">'.$i.'</a>';
}
if ($page < $totalPages) $pagebar .= '<a href="?page='.($page+1).'" class="arrow">&#9660;</a>';
$pagebar .= '</div>';

$currentPage = 'gallery';
require_once __DIR__ . '/header.php';
echo $msg;
?>

<?php if (is_admin()): ?>
<details class="upload-panel">
  <summary>NEW POST</summary>
  <div class="upload-tabs">
    <input type="radio" name="t" id="t1" checked><label for="t1" onclick="showForm('img','file')">file</label>
    <input type="radio" name="t" id="t2"><label for="t2" onclick="showForm('img','url')">url</label>
  </div>
  <form method="post" enctype="multipart/form-data" id="img_form_file" class="upload-form active">
    <input type="hidden" name="act" value="new_file">
    <div class="form-row">
      <div class="form-group"><label>file</label><input type="file" name="imagefile" accept="image/*" required></div>
      <div class="form-group"><label>title</label><input type="text" name="title"></div>
    </div>
    <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="2"></textarea></div>
    <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;"> blind</label></div>
    <button type="submit" class="btn">UPLOAD</button>
  </form>
  <form method="post" id="img_form_url" class="upload-form">
    <input type="hidden" name="act" value="new_url">
    <div class="form-row">
      <div class="form-group"><label>url</label><input type="url" name="image_url" required></div>
      <div class="form-group"><label>title</label><input type="text" name="title"></div>
    </div>
    <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="2"></textarea></div>
    <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;"> blind</label></div>
    <button type="submit" class="btn">SAVE</button>
  </form>
</details>
<?php endif; ?>

<?php if (!$post): ?>
  <div class="empty-state">-</div>
<?php else: ?>

<?php $isBlind = !empty($post['blind']); ?>
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

  <?php if (!empty($post['title']) || !empty($post['memo'])): ?>
  <div style="margin-bottom:8px;">
    <?php if (!empty($post['title'])): ?><div style="font-weight:bold;font-size:9pt;"><?= h($post['title']) ?></div><?php endif; ?>
    <?php if (!empty($post['memo'])): ?><div class="post-memo"><?= nl2br(h($post['memo'])) ?></div><?php endif; ?>
  </div>
  <?php endif; ?>

  <?php foreach ($post['images'] as $img): ?>
  <div class="gallery-post">
    <div class="post-img" onclick="openLightbox('<?= h(img_src($img)) ?>')">
      <img src="<?= h(img_src($img)) ?>" alt="" loading="lazy">
    </div>
    <div class="post-meta">

      <?php /*
      <?= h($post['date']) ?><?= $img['size'] ? ' | ' . fmt_size($img['size']) : '' ?>
      */ ?>

      <?php if (is_admin()): ?>
        <?php if (count($post['images']) > 1): ?>
          &nbsp;<a href="?del_img=<?= urlencode($img['id']) ?>&from_post=<?= urlencode($post['id']) ?>&page=<?= $page ?>"
                  style="color:#c00;font-size:7pt;"
                  onclick="return confirm('이 이미지만 삭제?')">[x]</a>
        <?php endif; ?>

        &nbsp;<a href="#"
                style="font-size:7pt;color:#999;"
                onclick="var el=document.getElementById('rep_<?= $img['id'] ?>');el.style.display=el.style.display==='block'?'none':'block';return false;">[replace]</a>

        <div id="rep_<?= $img['id'] ?>" style="display:none;margin-top:4px;">
          <form method="post" enctype="multipart/form-data" style="margin-bottom:2px;">
            <input type="hidden" name="act" value="replace_file">
            <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
            <input type="hidden" name="img_id" value="<?= h($img['id']) ?>">
            <div class="form-row">
              <div class="form-group">
                <input type="file" name="repfile" accept="image/*" required>
              </div>
              <button type="submit" class="btn">file</button>
            </div>
          </form>

          <form method="post">
            <input type="hidden" name="act" value="replace_url">
            <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
            <input type="hidden" name="img_id" value="<?= h($img['id']) ?>">
            <div class="form-row">
              <div class="form-group">
                <input type="url" name="rep_url" placeholder="url" required>
              </div>
              <button type="submit" class="btn">url</button>
            </div>
          </form>
        </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endforeach; ?>

  <?php if (is_admin()): ?>
  <div style="margin-top:10px;border-top:1px dotted #ddd;padding-top:8px;">
    <!-- 이미지 추가 -->
    <details class="upload-panel" style="margin-bottom:6px;">
      <summary>ADD IMAGE</summary>
      <form method="post" enctype="multipart/form-data" style="margin-top:4px;">
        <input type="hidden" name="act" value="add_file">
        <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
        <div class="form-row">
          <div class="form-group"><input type="file" name="addfile" accept="image/*" required></div>
          <button type="submit" class="btn">ADD</button>
        </div>
      </form>
      <form method="post" style="margin-top:4px;">
        <input type="hidden" name="act" value="add_url">
        <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
        <div class="form-row">
          <div class="form-group"><input type="url" name="add_url" placeholder="image url" required></div>
          <button type="submit" class="btn">ADD URL</button>
        </div>
      </form>
    </details>

    <!-- 수정 -->
    <details class="upload-panel" style="margin-bottom:6px;">
      <summary>EDIT</summary>
      <form method="post" style="margin-top:4px;">
        <input type="hidden" name="act" value="edit">
        <input type="hidden" name="post_id" value="<?= h($post['id']) ?>">
        <div class="form-group" style="margin-bottom:4px"><label>title</label><input type="text" name="title" value="<?= h($post['title']) ?>"></div>
        <div class="form-group" style="margin-bottom:4px"><label>memo</label><textarea name="memo" rows="3"><?= h($post['memo']) ?></textarea></div>
        <div style="margin-bottom:4px"><label style="font-size:7pt;color:#999;cursor:pointer;"><input type="checkbox" name="blind" style="vertical-align:middle;" <?= !empty($post['blind']) ? 'checked' : '' ?>> blind</label></div>
        <button type="submit" class="btn">SAVE</button>
      </form>
    </details>

    <a href="?del_post=<?= urlencode($post['id']) ?>" style="color:#c00;font-size:7pt;" onclick="return confirm('이 포스트 전체 삭제?')">[DELETE POST]</a>
  </div>
  <?php endif; ?>

</div><!-- .gated-content -->
<?php endif; ?>

<div id="lightbox" class="lightbox-overlay" onclick="closeLightbox()">
  <img id="lbImg" src="" alt="">
</div>

<script>
function reveal(){document.getElementById('gate').style.display='none';document.getElementById('content').classList.add('revealed');}
function decline(){document.getElementById('gate').innerHTML='<p style="color:#ccc">-</p>';}
function openLightbox(s){document.getElementById('lbImg').src=s;document.getElementById('lightbox').classList.add('open');document.body.style.overflow='hidden';}
function closeLightbox(){document.getElementById('lightbox').classList.remove('open');document.body.style.overflow='';}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeLightbox();});
function showForm(p,t){document.querySelectorAll('#'+p+'_form_file,#'+p+'_form_url').forEach(function(f){f.classList.remove('active');});document.getElementById(p+'_form_'+t).classList.add('active');}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
