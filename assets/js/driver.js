(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;

    async function fillSelect(select, rows, valueKey, labelFn, selected) {
        select.innerHTML = rows.map(function (row) {
            const value = row[valueKey];
            return '<option value="' + value + '"' + (String(selected) === String(value) ? ' selected' : '') + '>' +
                ui.escapeHtml(labelFn(row)) + '</option>';
        }).join('');
    }

    async function loadShift() {
        const data = await api('/api/dashboard.php');
        const driver = data.driver || {};
        const stats = data.stats || {};
        document.getElementById('driver-name').textContent = driver.full_name || 'Driver';
        document.getElementById('driver-status').textContent = driver.status || 'unknown';
        document.getElementById('driver-license').textContent = driver.license_number || '—';
        document.getElementById('stat-trips').textContent = stats.my_trips || 0;
        document.getElementById('stat-jeepneys').textContent = stats.my_jeepneys || 0;

        const jeepWrap = document.getElementById('jeepney-cards');
        if (!data.jeepney || !data.jeepney.length) {
            jeepWrap.innerHTML = '<div class="card empty">No jeepney is assigned to you yet. Ask an administrator.</div>';
        } else {
            jeepWrap.innerHTML = data.jeepney.map(function (j) {
                return '<article class="card"><h3>' + ui.escapeHtml(j.plate_number) + '</h3>' +
                    '<p class="muted">Unit #' + ui.escapeHtml(j.jeepney_number) + '</p>' +
                    '<p>' + ui.escapeHtml(j.route_name) + '</p>' +
                    '<p class="muted">' + ui.escapeHtml(j.destinations || '') + ' · ' + ui.escapeHtml(j.distance || '—') + ' km</p></article>';
            }).join('');
        }

        ui.renderRows(document.querySelector('#recent-trips tbody'), data.recent_trips, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                '</td><td>' + ui.escapeHtml(row.passenger_name) + '</td><td>' + ui.escapeHtml(row.plate_number) +
                '</td><td>' + ui.escapeHtml(row.number_of_passengers) + '</td></tr>';
        });
    }

    async function loadJeepney() {
        const rows = await api('/api/jeepneys.php');
        const wrap = document.getElementById('jeepney-detail');
        if (!rows.length) {
            wrap.innerHTML = '<div class="card empty">No assigned unit.</div>';
            return;
        }
        wrap.innerHTML = rows.map(function (j) {
            return '<article class="card"><h2>' + ui.escapeHtml(j.plate_number) + '</h2>' +
                '<p>Jeepney number ' + ui.escapeHtml(j.jeepney_number) + '</p>' +
                '<p>Route: ' + ui.escapeHtml(j.route_name) + '</p>' +
                '<p class="muted">' + ui.escapeHtml(j.destinations || 'No destination notes') + '</p>' +
                '<p>Distance: ' + ui.escapeHtml(j.distance || '—') + ' km · Stored fare ' + ui.money(j.fare) + '</p>' +
                '<p class="muted">You can record trips for this unit only. Route and plate changes are admin-only.</p></article>';
        }).join('');
    }

    async function loadTrips() {
        const [trips, jeepneys, passengers] = await Promise.all([
            api('/api/trips.php'),
            api('/api/jeepneys.php'),
            api('/api/passengers.php'),
        ]);

        fillSelect(document.getElementById('jeepney_id'), jeepneys, 'id', function (j) {
            return j.plate_number + ' · ' + j.route_name;
        });
        fillSelect(document.getElementById('passengers_id'), passengers, 'passengers_id', function (p) {
            return p.name + (p.contact_number ? ' (' + p.contact_number + ')' : '');
        });

        ui.renderRows(document.querySelector('#trips-table tbody'), trips, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                '</td><td>' + ui.escapeHtml(row.passenger_name) + '</td><td>' + ui.escapeHtml(row.plate_number) +
                '</td><td>' + ui.escapeHtml(row.number_of_passengers) + '</td></tr>';
        });

        document.getElementById('trip-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(event.target);
            try {
                await api('/api/trips.php', { method: 'POST', body: body });
                ui.toast('Trip recorded.');
                event.target.reset();
                const refreshed = await api('/api/trips.php');
                ui.renderRows(document.querySelector('#trips-table tbody'), refreshed, function (row) {
                    return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                        '</td><td>' + ui.escapeHtml(row.passenger_name) + '</td><td>' + ui.escapeHtml(row.plate_number) +
                        '</td><td>' + ui.escapeHtml(row.number_of_passengers) + '</td></tr>';
                });
            } catch (err) {
                ui.toast(err.message);
            }
        });
    }

    async function farePage() {
        document.getElementById('fare-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(event.target);
            const box = document.getElementById('fare-result');
            try {
                const result = await api('/api/fare.php', { method: 'POST', body: body });
                box.innerHTML = '<div class="card hero"><h2>' + ui.money(result.estimated_fare) + '</h2>' +
                    '<p>' + ui.escapeHtml(result.vehicle_type) + ' · ' + ui.escapeHtml(result.passenger_type) + '</p>' +
                    '<p class="muted">' + ui.escapeHtml(result.formula) + '</p></div>';
            } catch (err) {
                box.innerHTML = '<div class="card">' + ui.escapeHtml(err.message) + '</div>';
            }
        });
    }

    async function start() {
        try {
            if (page === 'dashboard') await loadShift();
            if (page === 'jeepney') await loadJeepney();
            if (page === 'log') await loadTrips();
            if (page === 'fare') await farePage();
        } catch (err) {
            ui.toast(err.message);
        }
    }

    start();
})();
