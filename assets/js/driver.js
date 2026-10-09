(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;

    function display(value, fallback) {
        return value == null || value === '' ? (fallback || 'Not provided') : value;
    }

    async function loadShift() {
        const data = await api('/api/dashboard.php');
        const driver = data.driver || {};
        const stats = data.stats || {};
        const units = data.jeepney || [];
        const status = display(driver.status, 'Unknown');
        const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
        document.getElementById('driver-name').textContent = display(driver.full_name, 'Driver');
        document.getElementById('driver-header-name').textContent = display(driver.full_name, 'Driver');
        document.getElementById('driver-status').textContent = statusLabel;
        document.getElementById('driver-status-chip').textContent = statusLabel;
        document.getElementById('driver-status').classList.toggle('is-active', String(driver.status).toLowerCase() === 'active');
        document.getElementById('driver-license').textContent = display(driver.license_number);
        document.getElementById('driver-route-chip').textContent = units.length ? display(units[0].route_name, 'Route assigned') : 'No route assigned';
        document.getElementById('stat-trips').textContent = stats.my_trips || 0;
        const initials = display(driver.full_name, 'Driver').trim().split(/\s+/).map(function (part) { return part.charAt(0); }).slice(0, 2).join('').toUpperCase();
        document.getElementById('driver-avatar').textContent = initials || 'D';
        document.getElementById('driver-assigned-unit').textContent = units.length
            ? units.map(function (unit) { return unit.plate_number + ' · Unit ' + unit.jeepney_number; }).join(', ')
            : 'No unit assigned';

        document.getElementById('recent-trips-list').innerHTML = (data.recent_trips || []).length
            ? data.recent_trips.map(function (row) {
                return '<div class="driver-trip-row"><strong>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                    '</strong><span class="driver-trip-meta">' + ui.escapeHtml(row.number_of_passengers) + ' pax · ' +
                    ui.escapeHtml(formatTripDate(row.recorded_date)) + '</span></div>';
            }).join('')
            : '<p class="muted" style="padding:12px 18px">No trips have been logged yet.</p>';
    }

    async function loadJeepney() {
        const rows = await api('/api/jeepneys.php');
        const wrap = document.getElementById('jeepney-detail');
        if (!rows.length) {
            wrap.innerHTML = '<article class="card empty">No assigned unit. Please contact an administrator.</article>';
            return;
        }
        wrap.innerHTML = rows.map(function (unit) {
            return '<article class="card driver-detail-card"><div class="driver-detail-head"><span class="driver-detail-icon"><i class="bi bi-truck-front"></i></span>' +
                '<div><h3>' + ui.escapeHtml(unit.plate_number) + '</h3><p>Unit ' + ui.escapeHtml(unit.jeepney_number) + ' · ' + ui.escapeHtml(unit.route_name) + '</p></div>' +
                '<span class="chip" style="margin-left:auto">' + ui.escapeHtml(unit.driver_status || 'Assigned') + '</span></div>' +
                '<div class="driver-detail-row"><span>Plate number</span><strong>' + ui.escapeHtml(unit.plate_number) + '</strong></div>' +
                '<div class="driver-detail-row"><span>Unit number</span><strong>' + ui.escapeHtml(unit.jeepney_number) + '</strong></div>' +
                '<div class="driver-detail-row"><span>Assigned route</span><strong>' + ui.escapeHtml(unit.route_name) + '</strong></div>' +
                '<div class="driver-detail-row"><span>Destinations</span><strong>' + ui.escapeHtml(unit.destinations || 'Not listed') + '</strong></div>' +
                '<div class="driver-detail-row"><span>Distance</span><strong>' + ui.escapeHtml(unit.distance == null ? 'Not listed' : unit.distance + ' km') + '</strong></div>' +
                '<div class="driver-detail-row"><span>Listed fare</span><strong>' + ui.money(unit.fare) + '</strong></div></article>';
        }).join('');
    }

    async function loadAssignedRoute() {
        const rows = await api('/api/jeepneys.php');
        const wrap = document.getElementById('assigned-route-content');
        if (!rows.length) {
            wrap.innerHTML = '<article class="card empty">No route is assigned yet. Please contact an administrator.</article>';
            return;
        }
        const route = rows[0];
        const stops = String(route.destinations || route.route_name || '').split(/\s*(?:→|->|,|;)\s*/).filter(Boolean);
        if (!stops.length) stops.push(route.route_name || 'Assigned route');
        const stopMarkup = stops.map(function (stop, index) {
            const label = index === 0 ? 'Origin' : (index === stops.length - 1 ? 'Destination' : 'Stop');
            return '<div class="driver-route-stop"><strong>' + ui.escapeHtml(stop) + '</strong><small>' + label + '</small></div>';
        }).join('');
        wrap.innerHTML = '<section class="card driver-route-stats"><div class="driver-route-stat"><span>Route</span><strong>' + ui.escapeHtml(route.route_name || 'Assigned route') + '</strong></div>' +
            '<div class="driver-route-stat"><span>Distance</span><strong>' + ui.escapeHtml(route.distance == null ? 'Not listed' : route.distance + ' km') + '</strong></div>' +
            '<div class="driver-route-stat"><span>Listed fare</span><strong>' + ui.money(route.fare) + '</strong></div></section>' +
            '<article class="card driver-detail-card"><h3 style="margin:0;padding:16px 22px;border-bottom:1px solid var(--line)">Route map</h3>' +
            '<div class="driver-route-map"><div class="driver-route-line" style="grid-template-columns:repeat(' + stops.length + ',minmax(0,1fr))">' + stopMarkup + '</div></div>' +
            '<div class="driver-route-stops"><h3>All stops</h3>' + stops.map(function (stop, index) {
                const label = index === 0 ? 'Origin' : (index === stops.length - 1 ? 'Destination' : 'Stop');
                return '<p><strong>' + ui.escapeHtml(stop) + '</strong><span>' + label + '</span></p>';
            }).join('') + '</div></article>';
    }

    async function loadProfile() {
        const profile = await api('/api/drivers.php');
        const name = display(profile.full_name, 'Driver');
        const initials = name.trim().split(/\s+/).map(function (part) { return part.charAt(0); }).slice(0, 2).join('').toUpperCase();
        document.getElementById('driver-header-name').textContent = name;
        document.getElementById('driver-profile-name').textContent = name;
        document.getElementById('driver-profile-full-name').textContent = name;
        document.getElementById('driver-profile-username').textContent = display(profile.username);
        document.getElementById('driver-profile-contact').textContent = display(profile.contact_number);
        document.getElementById('driver-profile-license').textContent = display(profile.license_number);
        document.getElementById('driver-profile-status').textContent = display(profile.status, 'Unknown');
        document.getElementById('driver-profile-avatar').textContent = initials || 'D';
    }

    async function loadHeaderName() {
        const profile = await api('/api/drivers.php');
        document.getElementById('driver-header-name').textContent = display(profile.full_name, 'Driver');
    }

    async function loadTrips() {
        const [trips, jeepneys, passengers] = await Promise.all([
            api('/api/trips.php'), api('/api/jeepneys.php'), api('/api/passengers.php'),
        ]);
        const jeepneySelect = document.getElementById('jeepney_id');
        const passengerSelect = document.getElementById('passengers_id');
        jeepneySelect.innerHTML = '<option value="">Select...</option>' + jeepneys.map(function (unit) {
            return '<option value="' + ui.escapeHtml(unit.id) + '">' + ui.escapeHtml(unit.plate_number + ' · ' + unit.route_name) + '</option>';
        }).join('');
        passengerSelect.innerHTML = '<option value="">Select...</option>' + passengers.map(function (passenger) {
            return '<option value="' + ui.escapeHtml(passenger.passengers_id) + '">' + ui.escapeHtml(passenger.name + (passenger.contact_number ? ' (' + passenger.contact_number + ')' : '')) + '</option>';
        }).join('');
        renderRecordRows(trips);
        const form = document.getElementById('trip-form');
        const submitButton = form.querySelector('button[type="submit"]');
        if (!jeepneys.length) {
            submitButton.disabled = true;
            ui.toast('No jeepney is assigned to your account.');
        }
        if (!passengers.length) {
            submitButton.disabled = true;
            ui.toast('No passenger accounts are available to record a trip.');
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            try {
                await api('/api/trips.php', { method: 'POST', body: ui.formValues(event.target) });
                ui.toast('Trip recorded.');
                event.target.reset();
                renderRecordRows(await api('/api/trips.php'));
            } catch (error) { ui.toast(error.message); }
        });
    }

    function renderRecordRows(rows) {
        const list = document.getElementById('trips-table');
        list.innerHTML = rows.length
            ? rows.slice(0, 5).map(function (row) {
                const date = row.recorded_date ? formatTripDate(row.recorded_date) : 'Date not available';
                return '<div class="driver-record-row"><strong>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                    '</strong><span>' + ui.escapeHtml(row.number_of_passengers) + ' pax · ' + ui.escapeHtml(date) + '</span></div>';
            }).join('')
            : '<p class="driver-record-empty">No trips have been recorded yet.</p>';
    }

    function formatTripDate(value) {
        if (!value) return 'Date not available';
        const date = new Date(value + 'T00:00:00');
        return Number.isNaN(date.getTime())
            ? 'Date not available'
            : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function renderTripRows(rows, selector) {
        ui.renderRows(document.querySelector(selector), rows, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) + '</td><td>' +
                ui.escapeHtml(row.passenger_name) + '</td><td><span class="driver-plate-chip">' + ui.escapeHtml(row.plate_number) +
                '</span></td><td>' + ui.escapeHtml(formatTripDate(row.recorded_date)) + '</td><td>' +
                ui.escapeHtml(row.number_of_passengers) + '</td></tr>';
        });
    }

    async function loadTripHistory() {
        let rows = await api('/api/trips.php');
        let sortField = 'recorded_date';
        let sortDirection = 'desc';
        const tbody = document.querySelector('#trip-history-table tbody');
        const search = document.getElementById('trip-history-search');

        function render() {
            const query = search.value.trim().toLocaleLowerCase();
            const filtered = rows.filter(function (row) {
                const searchable = [row.pickup, row.drop_off, row.route_name, row.passenger_name, row.plate_number]
                    .join(' ').toLocaleLowerCase();
                return searchable.includes(query);
            }).slice().sort(function (left, right) {
                const a = sortField === 'path'
                    ? left.pickup + ' ' + left.drop_off
                    : left[sortField];
                const b = sortField === 'path'
                    ? right.pickup + ' ' + right.drop_off
                    : right[sortField];
                const comparison = sortField === 'number_of_passengers'
                    ? Number(a) - Number(b)
                    : String(a == null ? '' : a).localeCompare(String(b == null ? '' : b));
                return comparison * (sortDirection === 'asc' ? 1 : -1);
            });
            if (!filtered.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="empty">No trips match your search.</td></tr>';
                return;
            }
            renderTripRows(filtered, '#trip-history-table tbody');
        }

        search.addEventListener('input', render);
        document.querySelectorAll('#trip-history-table [data-sort]').forEach(function (button) {
            button.addEventListener('click', function () {
                const nextField = button.dataset.sort;
                sortDirection = sortField === nextField && sortDirection === 'asc' ? 'desc' : 'asc';
                sortField = nextField;
                document.querySelectorAll('#trip-history-table th').forEach(function (header) {
                    header.removeAttribute('aria-sort');
                });
                button.closest('th').setAttribute('aria-sort', sortDirection === 'asc' ? 'ascending' : 'descending');
                render();
            });
        });
        render();
    }

    function initSettings() {
        const settings = document.getElementById('driver-settings');
        if (!settings) return;

        const storageKey = 'polonav-driver-settings-' + settings.dataset.userId;
        let saved;
        try {
            const parsed = JSON.parse(localStorage.getItem(storageKey) || '{}');
            saved = parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
        } catch (error) {
            ui.toast('Unable to load your saved preferences.');
            saved = {};
        }

        settings.querySelectorAll('[data-setting]').forEach(function (control) {
            const key = control.dataset.setting;
            if (Object.prototype.hasOwnProperty.call(saved, key)) {
                if (control.type === 'checkbox') control.checked = Boolean(saved[key]);
                else control.value = saved[key];
            }
            if (key === 'language') {
                document.documentElement.lang = control.value === 'fil' ? 'fil' : 'en';
            }
            if (key === 'compactMode') {
                document.documentElement.classList.toggle('driver-compact', control.checked);
            }
            control.addEventListener('change', function () {
                if (key === 'language') {
                    document.documentElement.lang = control.value === 'fil' ? 'fil' : 'en';
                }
                if (key === 'compactMode') {
                    document.documentElement.classList.toggle('driver-compact', control.checked);
                }
                saved[key] = control.type === 'checkbox' ? control.checked : control.value;
                try {
                    localStorage.setItem(storageKey, JSON.stringify(saved));
                } catch (error) {
                    ui.toast('Unable to save your preferences in this browser.');
                }
            });
        });
    }

    function initHelp() {
        const search = document.getElementById('help-search');
        if (!search) return;
        const faqs = Array.from(document.querySelectorAll('.driver-faq'));
        const noResults = document.getElementById('help-no-results');
        search.addEventListener('input', function () {
            const query = search.value.trim().toLocaleLowerCase();
            let visibleCount = 0;
            faqs.forEach(function (faq) {
                const matches = faq.textContent.toLocaleLowerCase().includes(query);
                faq.hidden = !matches;
                if (matches) visibleCount += 1;
            });
            noResults.hidden = visibleCount !== 0;
        });
    }

    async function start() {
        try {
            initSettings();
            initHelp();
            if (page !== 'dashboard' && page !== 'profile') await loadHeaderName();
            if (page === 'dashboard') await loadShift();
            if (page === 'jeepney') await loadJeepney();
            if (page === 'assigned-route') await loadAssignedRoute();
            if (page === 'log') await loadTrips();
            if (page === 'trip-history') await loadTripHistory();
            if (page === 'profile') await loadProfile();
        } catch (error) {
            ui.toast(error.message);
            const table = document.querySelector('#trip-history-table tbody');
            if (table) table.innerHTML = '<tr><td colspan="5" class="empty">' + ui.escapeHtml(error.message) + '</td></tr>';
        }
    }

    start();
})();
