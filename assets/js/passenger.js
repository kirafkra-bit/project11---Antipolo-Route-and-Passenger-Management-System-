(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;

    function routeCard(row) {
        return '<article class="ticket"><h3>' + ui.escapeHtml(row.route_name) + '</h3>' +
            '<p>' + ui.escapeHtml(row.destinations || 'Destinations not listed') + '</p>' +
            '<p class="muted">' + ui.escapeHtml(row.distance || '—') + ' km · listed fare ' + ui.money(row.fare || row.stored_fare) + '</p></article>';
    }

    async function home() {
        const data = await api('/api/dashboard.php');
        const p = data.passenger || {};
        document.getElementById('passenger-name').textContent = p.name || 'Passenger';
        document.getElementById('stat-trips').textContent = (data.stats && data.stats.my_trips) || 0;
        document.getElementById('stat-routes').textContent = (data.stats && data.stats.available_routes) || 0;
        const complaintStat = document.getElementById('stat-complaints');
        if (complaintStat) complaintStat.textContent = (data.stats && data.stats.my_complaints) || 0;
        const routes = document.getElementById('route-list');
        routes.innerHTML = (data.available_routes || []).map(routeCard).join('') || '<p class="muted">No routes published yet.</p>';
        ui.renderRows(document.querySelector('#recent-trips tbody'), data.recent_trips, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                '</td><td>' + ui.escapeHtml(row.driver_name) + '</td><td>' + ui.escapeHtml(row.plate_number) +
                '</td><td>' + ui.escapeHtml(row.route_name) + '</td></tr>';
        });
    }

    async function searchPage() {
        const form = document.getElementById('search-form');
        const results = document.getElementById('search-results');

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const values = ui.formValues(form);
            const params = new URLSearchParams();
            Object.keys(values).forEach(function (key) {
                if (values[key]) params.set(key, values[key]);
            });
            try {
                const rows = await api('/api/search.php?' + params.toString());
                if (!rows.length) {
                    results.innerHTML = '<div class="card empty">No matching jeepney or route.</div>';
                    return;
                }
                results.innerHTML = rows.map(function (row) {
                    const jeep = row.jeepney ? row.jeepney.plate_number : 'No unit assigned';
                    const driver = row.driver ? row.driver.name + ' (' + row.driver.status + ')' : 'No driver yet';
                    const fare = row.estimated_fare ? ui.money(row.estimated_fare.estimated_fare) : (row.fare_error || 'Fare unavailable');
                    return '<article class="ticket"><h3>' + ui.escapeHtml(row.route_name) + '</h3>' +
                        '<p>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) + '</p>' +
                        '<p>Jeepney: ' + ui.escapeHtml(jeep) + '</p>' +
                        '<p>Driver: ' + ui.escapeHtml(driver) + '</p>' +
                        '<p><strong>' + ui.escapeHtml(fare) + '</strong></p></article>';
                }).join('');
            } catch (err) {
                ui.toast(err.message);
            }
        });
    }

    async function tripsPage() {
        const rows = await api('/api/trips.php');
        ui.renderRows(document.querySelector('#trips-table tbody'), rows, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                '</td><td>' + ui.escapeHtml(row.driver_name) + '</td><td>' + ui.escapeHtml(row.plate_number) +
                '</td><td>' + ui.escapeHtml(row.route_name) + '</td><td>' + ui.escapeHtml(row.number_of_passengers) + '</td></tr>';
        });
    }

    async function farePage() {
        document.getElementById('fare-form').addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(event.target);
            const box = document.getElementById('fare-result');
            try {
                const result = await api('/api/fare.php', { method: 'POST', body: body });
                box.innerHTML = '<div class="ticket"><h2>' + ui.money(result.estimated_fare) + '</h2>' +
                    '<p>' + ui.escapeHtml(result.vehicle_type) + ' · ' + ui.escapeHtml(result.passenger_type) + '</p>' +
                    '<p class="muted">' + ui.escapeHtml(result.formula) + '</p></div>';
            } catch (err) {
                box.innerHTML = '<div class="card">' + ui.escapeHtml(err.message) + '</div>';
            }
        });
    }

    async function complaintsPage() {
        const form = document.getElementById('complaint-form');
        const list = document.getElementById('complaint-list');
        async function load() {
            const rows = await api('/api/complaints.php');
            list.innerHTML = rows.length ? rows.map(function (row) {
                return '<article class="ticket">' +
                    '<div class="complaint-heading"><strong>' + ui.escapeHtml(row.subject) + '</strong>' +
                    '<span class="chip">' + ui.escapeHtml(row.status.replace('_', ' ')) + '</span></div>' +
                    '<p class="muted">' + ui.escapeHtml(row.category.replace('_', ' ')) + ' · ' + ui.escapeHtml(row.created_at) + '</p>' +
                    '<p>' + ui.escapeHtml(row.description) + '</p>' +
                    (row.admin_response ? '<p><strong>Operations response:</strong> ' + ui.escapeHtml(row.admin_response) + '</p>' : '') +
                    '</article>';
            }).join('') : '<p class="muted">No reports submitted yet.</p>';
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await api('/api/complaints.php', { method: 'POST', body: ui.formValues(form) });
                form.reset();
                ui.toast('Report submitted.');
                await load();
            } catch (err) {
                ui.toast(err.message);
            }
        });
        try {
            const trips = await api('/api/trips.php');
            const tripSelect = document.getElementById('trip_id');
            trips.forEach(function (trip) {
                const option = document.createElement('option');
                option.value = trip.id;
                option.textContent = trip.pickup + ' → ' + trip.drop_off + ' · ' + trip.route_name;
                tripSelect.appendChild(option);
            });
        } catch (err) {
            ui.toast('Your report can still be submitted without linking a trip.');
        }
        await load();
    }

    async function start() {
        try {
            if (page === 'dashboard') await home();
            if (page === 'search') await searchPage();
            if (page === 'trips') await tripsPage();
            if (page === 'fare') await farePage();
            if (page === 'complaints') await complaintsPage();
        } catch (err) {
            ui.toast(err.message);
        }
    }

    start();
})();
