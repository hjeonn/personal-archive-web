# Personal Archive

## 설정

`config.php`에서 사이트 설정을 관리합니다.

* `SITE_TITLE`, `SITE_TAB_TITLE`, `HOME_TEXT`: 사이트 제목/홈 문구
* `BGM_PLAYLIST_ID` 또는 `BGM_VIDEO_ID`: YouTube BGM
* `SITE_FAVICON`: 파비콘 경로
* `SITE_CURSOR`: 마우스 커서 경로
* `SITE_CURSOR_HOTSPOT_X`, `SITE_CURSOR_HOTSPOT_Y`: 커서 hotspot

관리자 비밀번호는 서버 환경변수 `SITE_ADMIN_PASSWORD` 사용을 권장합니다. 설정하지 않으면 로그인 UI가 표시되지 않습니다.

## 콘텐츠

* `index.php`: 홈
* `gallery.php`: 이미지 게시판
* `pdfboard.php`: PDF 게시판

홈의 실제 내용은 `index.php`에서 수정할 수 있습니다.

## 데이터

최초 실행 시 `data/` 아래에 이미지/PDF 저장 폴더와 JSON DB가 자동 생성됩니다.

웹서버가 `data/` 디렉터리에 쓸 수 있는 권한이 필요합니다. 

## 주요 파일

* `config.php` — 사이트 설정
* `functions.php` — DB / 업로드 / 수정 / 삭제
* `header.php`, `footer.php` — 공통 레이아웃 / BGM
* `app.js` — PJAX 이동 / 폼 처리
* `style.css` — 스타일
