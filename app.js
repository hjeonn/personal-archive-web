(() => {
    'use strict';

    const PJAX_PAGES = new Set(['', 'index.php', 'gallery.php', 'pdfboard.php']);
    let activeController = null;

    function pageName(url) {
        const parts = url.pathname.split('/');
        return parts[parts.length - 1];
    }

    function isPjaxUrl(url) {
        return url.origin === window.location.origin && PJAX_PAGES.has(pageName(url));
    }

    function isSameDocumentHash(url) {
        return (
            url.pathname === window.location.pathname &&
            url.search === window.location.search &&
            url.hash !== ''
        );
    }

    function setLoading(on) {
        document.body.classList.toggle('pjax-loading', on);
    }

    function parsePjaxFragment(html) {
        // <template> 안에서 파싱하면 새 페이지의 iframe/script 등이 미리 실행되지 않는다.
        const template = document.createElement('template');
        template.innerHTML = html.trim();
        return template.content;
    }

    function applyFragment(fragment, finalUrl, historyMode, shouldScroll = true) {
        const currentSidebar = document.querySelector('.sidebar');
        const currentMain = document.querySelector('.main-area');
        const nextSidebar = fragment.querySelector('.sidebar');
        const nextMain = fragment.querySelector('.main-area');

        if (!currentSidebar || !currentMain || !nextSidebar || !nextMain) {
            throw new Error('PJAX 영역을 찾을 수 없습니다.');
        }

        const pageTitle = nextMain.dataset.pageTitle;
        currentSidebar.replaceWith(document.importNode(nextSidebar, true));
        currentMain.replaceWith(document.importNode(nextMain, true));

        if (pageTitle) {
            document.title = pageTitle;
        }

        const url = new URL(finalUrl, window.location.href);

        if (historyMode === 'push') {
            history.pushState({ pjax: true }, '', url.href);
        } else if (historyMode === 'replace') {
            history.replaceState({ pjax: true }, '', url.href);
        }

        if (shouldScroll) {
            if (url.hash) {
                requestAnimationFrame(() => {
                    const target = document.getElementById(decodeURIComponent(url.hash.slice(1)));
                    if (target) target.scrollIntoView();
                });
            } else {
                window.scrollTo(0, 0);
            }
        }

        document.dispatchEvent(new CustomEvent('pjax:complete', {
            detail: { url: url.href }
        }));
    }

    async function requestPage(url, options = {}) {
        const {
            method = 'GET',
            body = null,
            historyMode = 'push',
            shouldScroll = true
        } = options;

        if (activeController) activeController.abort();
        const controller = new AbortController();
        activeController = controller;

        setLoading(true);

        try {
            const response = await fetch(url, {
                method,
                body,
                credentials: 'same-origin',
                headers: {
                    'X-PJAX': '1',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const html = await response.text();
            const fragment = parsePjaxFragment(html);
            const headerUrl = response.headers.get('X-PJAX-URL');
            const finalUrl = headerUrl
                ? new URL(headerUrl, window.location.href).href
                : response.url;

            applyFragment(fragment, finalUrl, historyMode, shouldScroll);
            return true;
        } finally {
            if (activeController === controller) {
                activeController = null;
                setLoading(false);
            }
        }
    }

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a');
        if (!link) return;
        if (link.hasAttribute('download') || link.dataset.noPjax !== undefined) return;
        if (link.target && link.target.toLowerCase() !== '_self') return;

        const rawHref = link.getAttribute('href');
        if (!rawHref || rawHref.startsWith('#')) return;

        const url = new URL(link.href, window.location.href);
        if (!isPjaxUrl(url)) return;
        if (isSameDocumentHash(url)) return;

        event.preventDefault();

        requestPage(url.href, {
            historyMode: 'push',
            shouldScroll: true
        }).catch((error) => {
            if (error.name === 'AbortError') return;
            console.error('PJAX navigation failed:', error);
            window.location.href = url.href;
        });
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;

        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.noPjax !== undefined) return;
        if (form.target && form.target.toLowerCase() !== '_self') return;

        const url = new URL(form.action || window.location.href, window.location.href);
        if (!isPjaxUrl(url)) return;

        const method = (form.method || 'GET').toUpperCase();
        const formData = new FormData(form);

        // 일부 브라우저에서는 누른 submit 버튼의 name/value가 FormData에 자동 포함되지 않는다.
        const submitter = event.submitter;
        if (submitter && submitter.name && !formData.has(submitter.name)) {
            formData.append(submitter.name, submitter.value || '1');
        }

        event.preventDefault();

        if (method === 'GET') {
            const queryUrl = new URL(url.href);
            queryUrl.search = new URLSearchParams(formData).toString();

            requestPage(queryUrl.href, {
                historyMode: 'push',
                shouldScroll: true
            }).catch((error) => {
                if (error.name === 'AbortError') return;
                console.error('PJAX form navigation failed:', error);
                window.location.href = queryUrl.href;
            });
            return;
        }

        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        submitButtons.forEach((button) => button.disabled = true);

        requestPage(url.href, {
            method,
            body: formData,
            historyMode: 'replace',
            shouldScroll: false
        }).catch((error) => {
            if (error.name === 'AbortError') return;
            console.error('PJAX form submission failed:', error);
            alert('요청을 처리하지 못했습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
        }).finally(() => {
            submitButtons.forEach((button) => button.disabled = false);
        });
    });

    window.addEventListener('popstate', () => {
        requestPage(window.location.href, {
            historyMode: 'none',
            shouldScroll: true
        }).catch((error) => {
            if (error.name === 'AbortError') return;
            console.error('PJAX history navigation failed:', error);
            window.location.reload();
        });
    });

    // gallery.php / pdfboard.php에서 사용하는 공통 UI 함수.
    window.reveal = function reveal() {
        const gate = document.getElementById('gate');
        const content = document.getElementById('content');
        if (gate) gate.style.display = 'none';
        if (content) content.classList.add('revealed');
    };

    window.decline = function decline() {
        const gate = document.getElementById('gate');
        if (gate) gate.innerHTML = '<p style="color:#ccc">-</p>';
    };

    window.openLightbox = function openLightbox(src) {
        const box = document.getElementById('lightbox');
        const image = document.getElementById('lbImg');
        if (!box || !image) return;

        image.src = src;
        box.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function closeLightbox() {
        const box = document.getElementById('lightbox');
        if (!box) return;

        box.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.showForm = function showForm(prefix, type) {
        document.querySelectorAll(`#${prefix}_form_file,#${prefix}_form_url`).forEach((form) => {
            form.classList.remove('active');
        });

        const target = document.getElementById(`${prefix}_form_${type}`);
        if (target) target.classList.add('active');
    };

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') window.closeLightbox();
    });

    history.replaceState({ pjax: true }, '', window.location.href);
})();
