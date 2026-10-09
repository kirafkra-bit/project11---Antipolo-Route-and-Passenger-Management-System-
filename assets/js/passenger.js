(function () {
    const api = PoloNav.api, ui = PNUI, page = document.documentElement.dataset.page;

    function routeCard(row) {
        const fare = row.fare == null ? row.stored_fare : row.fare;
        return '<article class="route-row"><div><strong class="route-name">' + ui.escapeHtml(row.route_name || 'Route') +
            '</strong><span class="route-detail">' + ui.escapeHtml(row.destinations || 'Destinations not listed') +
            '</span></div><div class="route-side"><span>' + ui.escapeHtml(row.distance == null ? '—' : row.distance) +
            ' km</span><strong>Listed fare ' + ui.money(fare) + '</strong></div></article>';
    }

    async function home() {
        const data = await api('/api/dashboard.php'), passenger = data.passenger || {};
        document.getElementById('passenger-name').textContent = passenger.name || 'Passenger';
        document.getElementById('stat-trips').textContent = (data.stats && data.stats.my_trips) || 0;
        document.getElementById('stat-routes').textContent = (data.stats && data.stats.available_routes) || 0;
        document.getElementById('stat-complaints').textContent = (data.stats && data.stats.my_complaints) || 0;
        document.getElementById('route-list').innerHTML = (data.available_routes || []).map(routeCard).join('') ||
            '<p class="empty">No routes have been published yet.</p>';
        ui.renderRows(document.querySelector('#recent-trips tbody'), data.recent_trips, function (row) {
            return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                '</td><td>' + ui.escapeHtml(row.driver_name) + '</td><td>' + ui.escapeHtml(row.route_name) + '</td></tr>';
        });
    }

    async function searchPage() {
        const form = document.getElementById('search-form'), results = document.getElementById('search-results');
        const count = document.getElementById('search-result-count');
        let rows = [];
        function render() {
            const q = form.elements.q.value.trim().toLowerCase();
            const shown = rows.filter(function (row) {
                return [row.route_name, row.destinations, row.driver && row.driver.name, row.jeepney && row.jeepney.plate_number]
                    .some(function (value) { return String(value || '').toLowerCase().includes(q); });
            });
            count.textContent = shown.length + (shown.length === 1 ? ' result' : ' results');
            if (!shown.length) { results.innerHTML = '<p class="empty-search">No matching jeepney or route.</p>'; return; }
            results.innerHTML = '<div class="table-scroll"><table class="search-table"><thead><tr><th>Route</th><th>Driver</th><th>Plate</th><th>Vehicle type</th><th>Fare</th><th>Status</th><th></th></tr></thead><tbody>' +
                shown.map(function (row) {
                    const status = row.driver ? String(row.driver.status || 'Unknown').replace(/_/g, ' ') : 'No driver';
                    const fare = row.estimated_fare ? ui.money(row.estimated_fare.estimated_fare) : (row.fare_error || ui.money(row.stored_fare));
                    const stateClass = status.toLowerCase() === 'active' ? 'success' : (status.toLowerCase() === 'on break' ? 'warning' : 'neutral');
                    return '<tr><td><strong>' + ui.escapeHtml(row.route_name || '—') + '</strong><small>' +
                        ui.escapeHtml(row.destinations || 'Destinations not listed') + '</small></td><td>' +
                        ui.escapeHtml(row.driver ? row.driver.name : '—') + '</td><td><span class="plate-chip">' +
                        ui.escapeHtml(row.jeepney ? row.jeepney.plate_number : '—') + '</span></td><td>' +
                        ui.escapeHtml(row.estimated_fare ? row.estimated_fare.vehicle_type : 'Jeepney') + '</td><td><strong>' +
                        ui.escapeHtml(fare) + '</strong></td><td><span class="status-badge ' + stateClass + '">' +
                        ui.escapeHtml(status) + '</span></td><td><button class="table-view-btn" type="button" data-search-view="' +
                        ui.escapeHtml(row.route_id) + '">View</button></td></tr>';
                }).join('') + '</tbody></table></div>';
        }
        form.addEventListener('submit', function (event) { event.preventDefault(); });
        form.elements.q.addEventListener('input', render);
        results.addEventListener('click', function (event) {
            const button = event.target.closest('[data-search-view]');
            if (!button) return;
            const row = rows.find(function (item) { return String(item.route_id) === button.dataset.searchView; });
            if (!row) return;
            ui.openModal('Jeepney details', '<p><strong>Route:</strong> ' + ui.escapeHtml(row.route_name) +
                '</p><p><strong>Destinations:</strong> ' + ui.escapeHtml(row.destinations || 'Not listed') +
                '</p><p><strong>Driver:</strong> ' + ui.escapeHtml(row.driver ? row.driver.name + ' · ' + row.driver.status : 'No driver assigned') +
                '</p><p><strong>Plate:</strong> ' + ui.escapeHtml(row.jeepney ? row.jeepney.plate_number : 'No jeepney assigned') + '</p>');
        });
        rows = await api('/api/search.php'); render();
    }

    async function tripsPage() {
        const current = document.getElementById('passenger-current-ride');
        const joinPanel = document.getElementById('passenger-join-panel');
        const activeTrips = document.getElementById('passenger-active-trips');
        const form = document.getElementById('passenger-ride-form');
        const historyBody = document.querySelector('#passenger-ride-history tbody');
        const message = document.getElementById('passenger-ride-message');
        let selectedTrip = null;
        let state = null;

        function formatDate(value) {
            const date = value ? new Date(String(value).replace(' ', 'T')) : null;
            return date && !Number.isNaN(date.getTime()) ? date.toLocaleString() : '—';
        }

        function bindPlaceSearch(inputId, resultsId, latitudeId, longitudeId, signatureId) {
            const input = document.getElementById(inputId);
            const results = document.getElementById(resultsId);
            const latitude = document.getElementById(latitudeId);
            const longitude = document.getElementById(longitudeId);
            const signature = document.getElementById(signatureId);
            let timer = null;
            let requestNumber = 0;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                requestNumber += 1;
                latitude.value = '';
                longitude.value = '';
                signature.value = '';
                results.hidden = true;
                results.innerHTML = '';
                const query = input.value.trim();
                if (query.length < 3) return;
                timer = setTimeout(async function () {
                    const currentRequest = requestNumber;
                    results.hidden = false;
                    results.innerHTML = '<span class="muted">Searching places...</span>';
                    try {
                        const matches = await api('/api/passenger-rides.php?view=locations&q=' + encodeURIComponent(query));
                        if (currentRequest !== requestNumber) return;
                        results.innerHTML = matches.length
                            ? matches.map(function (place, index) {
                                return '<button type="button" role="option" data-place-index="' + index + '">' +
                                    ui.escapeHtml(place.label) + '</button>';
                            }).join('')
                            : '<span class="muted">No matching places found.</span>';
                        results.querySelectorAll('[data-place-index]').forEach(function (option) {
                            option.addEventListener('click', function () {
                                const place = matches[Number(option.dataset.placeIndex)];
                                input.value = place.label;
                                latitude.value = String(place.latitude);
                                longitude.value = String(place.longitude);
                                signature.value = place.signature;
                                results.hidden = true;
                            });
                        });
                    } catch (error) {
                        if (currentRequest !== requestNumber) return;
                        results.innerHTML = '<span class="is-error">' +
                            ui.escapeHtml(error.message || 'Place search failed.') + '</span>';
                    }
                }, 350);
            });
        }

        function renderHistory(records, legacyRecords) {
            const rows = records.map(function (ride) {
                return {
                    boarded_at: ride.boarded_at,
                    route: (ride.route_name || '—') + ' · ' + (ride.plate_number || '—'),
                    driver_name: ride.driver_name,
                    origin_name: ride.origin_name,
                    destination_name: ride.destination_name,
                    fare: ride.fare,
                    status: ride.status === 'on_board' ? 'On board' : 'Dropped off',
                    trip_id: ride.trip_id,
                };
            }).concat(legacyRecords.map(function (ride) {
                return {
                    boarded_at: ride.boarded_at,
                    route: (ride.route_name || '—') + ' · ' + (ride.plate_number || '—'),
                    driver_name: ride.driver_name,
                    origin_name: ride.origin_name || '—',
                    destination_name: ride.destination_name || '—',
                    fare: null,
                    status: ride.trip_status || 'Recorded',
                    trip_id: ride.trip_id,
                };
            })).sort(function (left, right) {
                return new Date(right.boarded_at || 0) - new Date(left.boarded_at || 0);
            });
            historyBody.innerHTML = rows.length ? rows.map(function (ride) {
                return '<tr><td>' + ui.escapeHtml(formatDate(ride.boarded_at)) + '</td><td>' +
                    ui.escapeHtml(ride.route) + ' · #' + ui.escapeHtml(ride.trip_id) + '</td><td>' +
                    ui.escapeHtml(ride.driver_name || '—') + '</td><td>' +
                    ui.escapeHtml(ride.origin_name || '—') + '</td><td>' +
                    ui.escapeHtml(ride.destination_name || '—') + '</td><td>' +
                    (ride.fare === null ? '—' : ui.money(ride.fare)) + '</td><td>' +
                    ui.escapeHtml(ride.status) + '</td></tr>';
            }).join('') : '<tr><td colspan="7" class="empty">No trips yet.</td></tr>';
        }

        function renderActiveTrips(trips) {
            activeTrips.innerHTML = trips.length ? trips.map(function (trip) {
                return '<article class="passenger-active-trip"><div><strong>' +
                    ui.escapeHtml(trip.route_name || 'Route') + '</strong><span>' +
                    ui.escapeHtml(trip.destinations || 'Route destinations not listed') + '</span></div><div>' +
                    '<strong>' + ui.escapeHtml(trip.driver_name || 'Driver') + '</strong><span>Unit ' +
                    ui.escapeHtml(trip.jeepney_number) + ' · ' + ui.escapeHtml(trip.plate_number) + '</span></div>' +
                    '<span class="passenger-trip-count">' + ui.escapeHtml(trip.on_board_count) + ' aboard</span>' +
                    '<button class="btn" type="button" data-join-trip="' + ui.escapeHtml(trip.trip_id) +
                    '">I’m riding this jeepney</button></article>';
            }).join('') : '<p class="empty">No active trips are available right now.</p>';
        }

        async function loadState() {
            state = await api('/api/passenger-rides.php');
            renderHistory(state.history || [], state.legacy_history || []);
            if (state.current_ride) {
                const ride = state.current_ride;
                joinPanel.hidden = true;
                current.hidden = false;
                current.innerHTML = '<h3>Your current ride</h3><p><strong>' +
                    ui.escapeHtml(ride.route_name || 'Route') + '</strong> · Unit ' +
                    ui.escapeHtml(ride.jeepney_number) + ' · ' + ui.escapeHtml(ride.plate_number) +
                    '</p><p>Driver: ' + ui.escapeHtml(ride.driver_name || '—') + '</p><p>' +
                    ui.escapeHtml(ride.origin_name) + ' → ' + ui.escapeHtml(ride.destination_name) +
                    ' · ' + ui.escapeHtml(Number(ride.distance_km).toFixed(2)) + ' km · ' +
                    ui.money(ride.fare) + '</p><button class="btn" type="button" data-dropoff-fare="' +
                    ui.escapeHtml(ride.fare_id) + '">I’ve arrived — mark dropped off</button>' +
                    '<p id="passenger-dropoff-message" class="muted" role="status" hidden></p>';
                form.hidden = true;
                return;
            }
            current.hidden = true;
            current.innerHTML = '';
            joinPanel.hidden = false;
            activeTrips.hidden = false;
            form.hidden = !selectedTrip;
            renderActiveTrips(state.active_trips || []);
        }

        activeTrips.addEventListener('click', function (event) {
            const button = event.target.closest('[data-join-trip]');
            if (!button || !state) return;
            selectedTrip = state.active_trips.find(function (trip) {
                return String(trip.trip_id) === button.dataset.joinTrip;
            });
            if (!selectedTrip) return;
            activeTrips.hidden = true;
            form.hidden = false;
            document.getElementById('passenger-selected-trip').innerHTML =
                '<strong>' + ui.escapeHtml(selectedTrip.route_name || 'Route') + '</strong> · ' +
                ui.escapeHtml(selectedTrip.driver_name || 'Driver') + ' · Unit ' +
                ui.escapeHtml(selectedTrip.jeepney_number) + ' · ' + ui.escapeHtml(selectedTrip.plate_number);
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        document.getElementById('passenger-ride-cancel').addEventListener('click', function () {
            selectedTrip = null;
            form.reset();
            form.hidden = true;
            activeTrips.hidden = false;
            message.hidden = true;
        });

        bindPlaceSearch('passenger-ride-origin', 'passenger-ride-origin-results',
            'passenger-ride-origin-latitude', 'passenger-ride-origin-longitude', 'passenger-ride-origin-signature');
        bindPlaceSearch('passenger-ride-destination', 'passenger-ride-destination-results',
            'passenger-ride-destination-latitude', 'passenger-ride-destination-longitude', 'passenger-ride-destination-signature');

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!selectedTrip) return;
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            message.hidden = true;
            try {
                const result = await api('/api/passenger-rides.php', {
                    method: 'POST',
                    body: Object.assign({ action: 'board', trip_id: selectedTrip.trip_id }, ui.formValues(form)),
                });
                ui.toast('You joined the trip. Fare: ' + ui.money(result.fare.fare) + '.');
                selectedTrip = null;
                form.reset();
                await loadState();
            } catch (error) {
                message.textContent = error.message || 'Could not join this trip.';
                message.hidden = false;
            } finally {
                button.disabled = false;
            }
        });

        current.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-dropoff-fare]');
            if (!button) return;
            button.disabled = true;
            try {
                await api('/api/passenger-rides.php', {
                    method: 'POST',
                    body: { action: 'drop_off', fare_id: button.dataset.dropoffFare },
                });
                ui.toast('Your ride is marked as dropped off.');
                await loadState();
            } catch (error) {
                const notice = document.getElementById('passenger-dropoff-message');
                notice.textContent = error.message || 'Could not update your ride.';
                notice.hidden = false;
                button.disabled = false;
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) loadState().catch(function (error) { ui.toast(error.message); });
        });
        await loadState();
    }

    async function farePage() {
        document.getElementById('fare-form').addEventListener('submit', async function (event) {
            event.preventDefault(); const box = document.getElementById('fare-result');
            try {
                const result = await api('/api/fare.php', { method: 'POST', body: ui.formValues(event.target) });
                box.innerHTML = '<div class="ticket fare-ticket"><span>Estimated fare</span><h2>' + ui.money(result.estimated_fare) +
                    '</h2><p>' + ui.escapeHtml(result.vehicle_type) + ' · ' + ui.escapeHtml(result.passenger_type) +
                    '</p><small>' + ui.escapeHtml(result.formula) + '</small></div>';
            } catch (error) { box.innerHTML = '<p class="form-error">' + ui.escapeHtml(error.message) + '</p>'; }
        });
    }

    async function complaintsPage() {
        const form = document.getElementById('complaint-form'), list = document.getElementById('complaint-list');
        async function load() {
            const rows = await api('/api/complaints.php');
            list.innerHTML = rows.length ? rows.map(function (row) {
                return '<article class="report-item"><strong>' + ui.escapeHtml(row.subject) + '</strong><div class="mini">' +
                    ui.escapeHtml(String(row.category || '').replace(/_/g, ' ')) + ' · ' + ui.escapeHtml(row.created_at) +
                    '</div><span class="status">' + ui.escapeHtml(row.status) + '</span>' +
                    (row.description ? '<p>' + ui.escapeHtml(row.description) + '</p>' : '') +
                    (row.admin_response ? '<p><strong>Operations response:</strong> ' + ui.escapeHtml(row.admin_response) + '</p>' : '') +
                    '</article>';
            }).join('') : '<p class="empty">No reports submitted yet.</p>';
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            try { await api('/api/complaints.php', { method: 'POST', body: ui.formValues(form) }); form.reset(); ui.toast('Report submitted.'); await load(); }
            catch (error) { ui.toast(error.message); }
        });
        try {
            const trips = await api('/api/trips.php'), select = document.getElementById('trip_id');
            trips.forEach(function (trip) { const option = document.createElement('option'); option.value = trip.id;
                option.textContent = trip.pickup + ' → ' + trip.drop_off + ' · ' + trip.route_name; select.appendChild(option); });
        } catch (error) { /* Linking a trip is optional. */ }
        await load();
    }

    async function profilePage() {
        const profile = await api('/api/passengers.php'), name = profile.name || 'Passenger';
        const initials = name.trim().split(/\s+/).map(function (part) { return part.charAt(0); }).slice(0, 2).join('').toUpperCase();
        document.getElementById('profile-name').textContent = name;
        document.getElementById('profile-username').textContent = profile.username || 'Not provided';
        document.getElementById('profile-full-name').textContent = name;
        document.getElementById('profile-contact').textContent = profile.contact_number || 'Not provided';
        document.getElementById('profile-address').textContent = profile.address || 'Not provided';
        document.getElementById('profile-avatar').textContent = initials || 'PN';
    }

    function favoritesPage() {
        const key = 'polonav.favoriteDestinations', form = document.getElementById('favorite-form'), list = document.getElementById('favorite-list');
        function read() { try { return JSON.parse(localStorage.getItem(key) || '[]').filter(function (name) { return typeof name === 'string'; }); } catch (e) { return []; } }
        function render() {
            const items = read();
            list.innerHTML = items.length ? items.map(function (name, i) {
                return '<article class="favorite-item"><span class="favorite-pin"><i class="bi bi-geo-alt" aria-hidden="true"></i></span><strong>' +
                    ui.escapeHtml(name) + '</strong><button type="button" class="remove-favorite" data-remove-favorite="' + i +
                    '" aria-label="Remove ' + ui.escapeHtml(name) + '">Remove</button></article>';
            }).join('') : '<p class="empty">No favorite destinations saved yet. Add a place above to get started.</p>';
        }
        form.addEventListener('submit', function (event) {
            event.preventDefault(); const input = form.elements.destination, name = input.value.trim(); if (!name) return;
            const items = read(); if (!items.some(function (item) { return item.toLowerCase() === name.toLowerCase(); })) {
                items.push(name); localStorage.setItem(key, JSON.stringify(items));
            } form.reset(); render();
        });
        list.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-favorite]'); if (!button) return;
            const items = read(); items.splice(Number(button.dataset.removeFavorite), 1); localStorage.setItem(key, JSON.stringify(items)); render();
        });
        render();
    }

    function helpPage() {
        const search = document.getElementById('faq-search'), items = Array.from(document.querySelectorAll('.faq-item'));
        items.forEach(function (item) {
            const button = item.querySelector('.faq-question'), answer = item.querySelector('.faq-answer');
            button.addEventListener('click', function () {
                const expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', String(!expanded)); answer.hidden = expanded;
                button.querySelector('span').textContent = expanded ? '+' : '−';
            });
        });
        search.addEventListener('input', function () {
            const query = search.value.trim().toLowerCase();
            items.forEach(function (item) { item.hidden = !item.textContent.toLowerCase().includes(query); });
        });
    }

    async function start() {
        try {
            if (page === 'dashboard') await home();
            if (page === 'search') await searchPage();
            if (page === 'trips') await tripsPage();
            if (page === 'fare') await farePage();
            if (page === 'complaints') await complaintsPage();
            if (page === 'profile') await profilePage();
            if (page === 'favorites') favoritesPage();
            if (page === 'help') helpPage();
        } catch (error) { ui.toast(error.message); }
    }
    start();
})();
