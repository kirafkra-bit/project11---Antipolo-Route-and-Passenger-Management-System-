(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const list = document.getElementById('portal-announcements');
    if (!list) return;
    const pagination = document.getElementById('announcement-pagination');
    const categoryButtons = Array.from(document.querySelectorAll('[data-announcement-category]'));

    const base = document.documentElement.dataset.base || '';
    const pageSize = 6;
    let announcements = [];
    let selectedCategory = 'all';
    let currentPage = 1;

    function safeLink(value) {
        try {
            const url = new URL(value, window.location.origin);
            return url.protocol === 'https:' ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function formatDate(value) {
        const date = value ? new Date(value) : null;
        return date && !Number.isNaN(date.getTime())
            ? date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
            : 'Date unavailable';
    }

    function render(item) {
        const externalUrl = safeLink(item.external_url || '');
        const title = ui.escapeHtml(item.title || '');
        const date = ui.escapeHtml(formatDate(item.posted_at));
        if (!externalUrl) {
            return '<article class="card driver-announcement"><div class="driver-announcement-meta"><span>' +
                ui.escapeHtml(item.posted_by || 'PoloNav Operations') + '</span><time>' + date +
                '</time></div><h3>' + title + '</h3><p>' +
                ui.escapeHtml(item.body || '').replace(/\r?\n/g, '<br>') + '</p></article>';
        }

        const imagePath = typeof item.image === 'string' && item.image.indexOf('assets/images/announcements/') === 0
            ? base + '/' + item.image
            : '';
        const image = imagePath
            ? '<a class="announcement-image-link" href="' + ui.escapeHtml(externalUrl) +
                '" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true"><img src="' +
                ui.escapeHtml(imagePath) + '" alt="" loading="lazy"></a>'
            : '';

        return '<article class="announcement-card">' + image +
            '<div class="announcement-card-content"><div class="announcement-card-meta"><time>' +
            date + '</time><span>PRESS RELEASE</span></div><h3><a href="' + ui.escapeHtml(externalUrl) +
            '" target="_blank" rel="noopener noreferrer">' + title + '</a></h3>' +
            '<a class="announcement-read-link" href="' + ui.escapeHtml(externalUrl) +
            '" target="_blank" rel="noopener noreferrer">See post <span aria-hidden="true">→</span></a></div></article>';
    }

    function renderPagination(pageCount, totalItems) {
        if (!pagination) return;
        if (pageCount < 2) {
            pagination.innerHTML = '';
            pagination.hidden = true;
            return;
        }

        pagination.hidden = false;
        const firstItem = (currentPage - 1) * pageSize + 1;
        const lastItem = Math.min(currentPage * pageSize, totalItems);
        let controls = '<span class="announcement-page-range">Announcements ' + firstItem + '–' + lastItem +
            ' of ' + totalItems + '</span><button type="button" data-page="' + (currentPage - 1) +
            '" aria-label="Previous page"' + (currentPage === 1 ? ' disabled' : '') + '>‹</button>';
        for (let page = 1; page <= pageCount; page += 1) {
            controls += '<button type="button" data-page="' + page + '" aria-label="Page ' + page + '"' +
                (page === currentPage ? ' aria-current="page"' : '') + '>' + page + '</button>';
        }
        controls += '<button type="button" data-page="' + (currentPage + 1) +
            '" aria-label="Next page"' + (currentPage === pageCount ? ' disabled' : '') + '>›</button>';
        pagination.innerHTML = controls;
    }

    function renderList() {
        const filtered = selectedCategory === 'all'
            ? announcements
            : announcements.filter(function (item) {
                return item.category === selectedCategory;
            });
        const pageCount = Math.ceil(filtered.length / pageSize);
        currentPage = Math.min(Math.max(currentPage, 1), Math.max(pageCount, 1));
        const start = (currentPage - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        list.innerHTML = visible.length
            ? visible.map(render).join('')
            : '<article class="card empty announcement-empty-state">No announcements in this category.</article>';
        renderPagination(pageCount, filtered.length);
    }

    categoryButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            selectedCategory = button.dataset.announcementCategory;
            currentPage = 1;
            categoryButtons.forEach(function (categoryButton) {
                categoryButton.setAttribute('aria-pressed', String(categoryButton === button));
            });
            renderList();
        });
    });

    if (pagination) {
        pagination.addEventListener('click', function (event) {
            const button = event.target.closest('[data-page]');
            if (!button || button.disabled) return;
            currentPage = Number(button.dataset.page);
            renderList();
        });
    }

    api('/api/announcements.php').then(function (items) {
        announcements = items;
        renderList();
    }).catch(function (error) {
        list.innerHTML = '<article class="card empty">' + ui.escapeHtml(error.message) + '</article>';
        if (pagination) pagination.hidden = true;
    });
})();
