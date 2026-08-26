<?php
require_once __DIR__ . '/config.php';
$pageTitle = SITE_TAB_TITLE;
$currentPage = 'home';
$pagebar = '';
require_once __DIR__ . '/header.php';
?>

<details class="home-sections">
    <summary class="home-text"><?= h(HOME_TEXT) ?></summary>

    <details class="content-panel">
        <summary>SITE GUIDE</summary>
        <div class="content-panel-body">
            <p>이 영역은 자유롭게 수정할 수 있는 홈 콘텐츠 예시입니다.</p>
            <p>이미지는 <strong>image</strong>, 문서는 <strong>docs</strong> 메뉴에서 관리할 수 있습니다.</p>
        </div>
    </details>

    <article class="content-article">
        <nav class="content-toc" aria-label="목차">
            <div class="content-toc-title">목차</div>
            <ol>
                <li><a href="#overview">개요</a></li>
                <li><a href="#features">기능</a></li>
                <li><a href="#customize">사용자 설정</a></li>
            </ol>
        </nav>

        <section class="content-section">
            <h1 id="overview">1. 개요</h1>
            <div class="content-notices">
                <span class="notice-tag">PHP</span>
                <span class="notice-tag">PJAX</span>
                <span class="notice-tag">JSON DB</span>
            </div>
            <p>간단한 이미지·문서 아카이브용 템플릿입니다. 서버 렌더링 구조를 유지하면서 내부 페이지 이동은 PJAX로 처리합니다.</p>
            <p>BGM을 설정한 경우 페이지를 이동해도 플레이어가 유지되어 재생이 끊기지 않습니다.</p>
        </section>

        <section class="content-section">
            <h1 id="features">2. 기능</h1>
            <h2>2.1. 이미지</h2>
            <p>이미지 업로드, 외부 URL 등록, 게시글 수정, 이미지 추가·교체·삭제를 지원합니다.</p>

            <h2>2.2. 문서</h2>
            <p>PDF 업로드 또는 외부 URL 등록과 게시글 수정·삭제를 지원합니다.</p>

            <h2>2.3. 관리 기능</h2>
            <p>관리자 비밀번호는 배포 환경에서 별도로 설정하며, 설정되지 않은 경우 로그인 UI가 표시되지 않습니다.</p>
        </section>

        <section class="content-section">
            <h1 id="customize">3. 사용자 설정</h1>
            <p>사이트 제목, 홈 문구, BGM, 파비콘, 마우스 커서는 <code>config.php</code>에서 설정할 수 있습니다.</p>
            <blockquote>이 홈 페이지는 예시입니다. 실제 배포 시 <code>index.php</code>의 내용을 원하는 형태로 교체하세요.</blockquote>
        </section>
    </article>
</details>

<?php require_once __DIR__ . '/footer.php'; ?>
