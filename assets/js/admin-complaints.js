(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const tbody = document.querySelector('#complaints-table tbody');
    const search = document.getElementById('complaint-search');
    const status = document.getElementById('complaint-status');

    function load() {
        const params = new URLSearchParams();
        if (search.value.trim()) params.set('q', search.value.trim());
        if (status.value) params.set('status', status.value);
        return api('/api/complaints.php?' + params.toString()).then(function (rows) {
            ui.renderRows(tbody, rows, function (row) {
                return '<tr><td>' + ui.escapeHtml(row.passenger_name) + '</td>' +
                    '<td>' + ui.escapeHtml(row.category.replace('_', ' ')) + '</td>' +
                    '<td>' + ui.escapeHtml(row.subject) + '</td>' +
                    '<td><span class="chip">' + ui.escapeHtml(row.status.replace('_', ' ')) + '</span></td>' +
                    '<td>' + ui.escapeHtml(row.created_at) + '</td>' +
                    '<td><button class="btn btn-ghost btn-small" data-id="' + row.id + '">Review</button></td></tr>';
            });
        }).catch(function (err) {
            ui.toast(err.message);
        });
    }

    function openReview(row) {
        ui.openModal('Review complaint', '<article class="stack">' +
            '<p><strong>' + ui.escapeHtml(row.subject) + '</strong><br>' +
            ui.escapeHtml(row.passenger_name) + ' · ' + ui.escapeHtml(row.category.replace('_', ' ')) + '</p>' +
            '<p>' + ui.escapeHtml(row.description) + '</p>' +
            '<form id="complaint-review-form" class="stack">' +
            '<input type="hidden" name="id" value="' + row.id + '">' +
            '<label>Status</label><select name="status">' +
            ['submitted', 'in_review', 'resolved', 'rejected'].map(function (value) {
                return '<option value="' + value + '"' + (row.status === value ? ' selected' : '') + '>' + value.replace('_', ' ') + '</option>';
            }).join('') + '</select>' +
            '<label>Response to passenger</label><textarea name="admin_response" rows="4">' + ui.escapeHtml(row.admin_response || '') + '</textarea>' +
            '<button class="btn" type="submit">Save update</button></form></article>');
        document.getElementById('complaint-review-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await api('/api/complaints.php', { method: 'PUT', body: ui.formValues(event.target) });
                ui.closeModal();
                ui.toast('Complaint updated.');
                load();
            } catch (err) {
                ui.toast(err.message);
            }
        });
    }

    tbody.addEventListener('click', async function (event) {
        const id = event.target.getAttribute('data-id');
        if (!id) return;
        try {
            const rows = await api('/api/complaints.php');
            const row = rows.find(function (item) { return String(item.id) === String(id); });
            if (row) openReview(row);
        } catch (err) {
            ui.toast(err.message);
        }
    });
    search.addEventListener('input', load);
    status.addEventListener('change', load);
    load();
})();
