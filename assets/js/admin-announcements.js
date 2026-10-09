(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const list = document.getElementById('admin-announcements');

    function formMarkup(item) {
        item = item || {};
        return '<form id="announcement-form" class="stack">' +
            '<input type="hidden" name="id" value="' + ui.escapeHtml(item.id || '') + '">' +
            '<div><label for="announcement-title">Title</label><input id="announcement-title" name="title" maxlength="180" required value="' +
            ui.escapeHtml(item.title || '') + '"></div>' +
            '<div><label for="announcement-body">Body</label><textarea id="announcement-body" name="body" rows="7" maxlength="12000" required>' +
            ui.escapeHtml(item.body || '') + '</textarea></div>' +
            '<button class="btn" type="submit">' + (item.id ? 'Save changes' : 'Publish announcement') + '</button></form>';
    }

    async function load() {
        const items = await api('/api/announcements.php');
        list.innerHTML = items.length ? items.map(function (item) {
            const date = item.posted_at ? new Date(item.posted_at) : null;
            const posted = date && !Number.isNaN(date.getTime())
                ? date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
                : 'Date unavailable';
            if (item.type === 'external' && item.external_url) {
                const imagePath = typeof item.image === 'string' && item.image.indexOf('assets/images/announcements/') === 0
                    ? (document.documentElement.dataset.base || '') + '/' + item.image
                    : '';
                return '<article class="announcement-card admin-external-announcement">' +
                    (imagePath ? '<a class="announcement-image-link" href="' + ui.escapeHtml(item.external_url) +
                        '" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true"><img src="' +
                        ui.escapeHtml(imagePath) + '" alt="" loading="lazy"></a>' : '') +
                    '<div class="announcement-card-content"><div class="announcement-card-meta"><time>' +
                    ui.escapeHtml(posted) + '</time><span>PRESS RELEASE</span></div><h3><a href="' +
                    ui.escapeHtml(item.external_url) + '" target="_blank" rel="noopener noreferrer">' +
                    ui.escapeHtml(item.title) + '</a></h3><a class="announcement-read-link" href="' +
                    ui.escapeHtml(item.external_url) + '" target="_blank" rel="noopener noreferrer">See post <span aria-hidden="true">→</span></a></div></article>';
            }
            return '<article class="card admin-announcement"><div class="admin-announcement-meta"><span>' +
                ui.escapeHtml(item.posted_by || '') + ' · ' + ui.escapeHtml(posted) + '</span>' +
                '<span><button class="btn-edit" type="button" data-edit="' + ui.escapeHtml(item.id) + '">Edit</button> ' +
                '<button class="btn btn-danger btn-small" type="button" data-delete="' + ui.escapeHtml(item.id) + '">Delete</button></span></div>' +
                '<h3>' + ui.escapeHtml(item.title) + '</h3><p>' + ui.escapeHtml(item.body).replace(/\r?\n/g, '<br>') + '</p></article>';
        }).join('') : '<article class="card empty">No announcements have been published.</article>';
        list.dataset.items = JSON.stringify(items);
    }

    function openForm(item) {
        ui.openModal(item ? 'Edit announcement' : 'New announcement', formMarkup(item));
        const form = document.getElementById('announcement-form');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(form);
            try {
                await api('/api/announcements.php', { method: body.id ? 'PUT' : 'POST', body: body });
                ui.closeModal();
                ui.toast(body.id ? 'Announcement updated.' : 'Announcement published.');
                await load();
            } catch (error) {
                ui.toast(error.message);
            }
        });
    }

    document.getElementById('announcement-add').addEventListener('click', function () {
        openForm(null);
    });

    list.addEventListener('click', async function (event) {
        const editId = event.target.getAttribute('data-edit');
        const deleteId = event.target.getAttribute('data-delete');
        const items = JSON.parse(list.dataset.items || '[]');
        if (editId) {
            const item = items.find(function (entry) { return entry.id === editId; });
            if (item) openForm(item);
        }
        if (deleteId && window.confirm('Delete this announcement?')) {
            try {
                await api('/api/announcements.php', { method: 'DELETE', body: { id: deleteId } });
                ui.toast('Announcement deleted.');
                await load();
            } catch (error) {
                ui.toast(error.message);
            }
        }
    });

    load().catch(function (error) {
        list.innerHTML = '<article class="card empty">' + ui.escapeHtml(error.message) + '</article>';
    });
})();
