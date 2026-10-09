(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const tbody = document.querySelector('#route-requests-table tbody');

    async function load() {
        const [requests, routes] = await Promise.all([
            api('/api/route-requests.php'),
            api('/api/routes.php'),
        ]);
        if (!requests.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">No pending route requests.</td></tr>';
            return;
        }
        tbody.innerHTML = requests.map(function (request) {
            return '<tr><td>' + ui.escapeHtml(request.full_name) + '<small class="driver-admin-subtext">' +
                ui.escapeHtml(request.username) + '</small></td><td>' + ui.escapeHtml(request.license_number) +
                '</td><td>' + ui.escapeHtml(request.requested_route_name || 'No longer available') + '</td><td><select data-route-for="' +
                ui.escapeHtml(request.driver_id) + '">' + routes.map(function (route) {
                    const selected = String(route.id) === String(request.requested_route_id) ? ' selected' : '';
                    return '<option value="' + ui.escapeHtml(route.id) + '"' + selected + '>' +
                        ui.escapeHtml(route.route_name) + '</option>';
                }).join('') + '</select></td><td class="driver-request-actions"><button class="btn" type="button" data-approve="' +
                ui.escapeHtml(request.driver_id) + '"' + (routes.length ? '' : ' disabled') + '>Approve</button> ' +
                '<button class="btn btn-danger btn-small" type="button" data-reject="' + ui.escapeHtml(request.driver_id) +
                '">Reject</button></td></tr>';
        }).join('');
    }

    tbody.addEventListener('click', async function (event) {
        const approveId = event.target.getAttribute('data-approve');
        const rejectId = event.target.getAttribute('data-reject');
        const driverId = approveId || rejectId;
        if (!driverId) return;
        const action = approveId ? 'approve' : 'reject';
        const routeSelect = tbody.querySelector('[data-route-for="' + CSS.escape(driverId) + '"]');
        const body = { action: action, driver_id: driverId };
        if (approveId) body.route_id = routeSelect.value;
        event.target.disabled = true;
        try {
            const result = await api('/api/route-requests.php', { method: 'POST', body: body });
            ui.toast(result.message);
            await load();
        } catch (error) {
            ui.toast(error.message);
            event.target.disabled = false;
        }
    });

    load().catch(function (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty">' + ui.escapeHtml(error.message) + '</td></tr>';
    });
})();
