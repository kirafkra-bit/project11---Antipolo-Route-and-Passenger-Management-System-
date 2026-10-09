(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;
    const base = document.documentElement.dataset.base || '';
    const stepUrls = {
        'trips-start': '/trips/start/',
        'trips-passengers': '/trips/passengers/',
        'trips-finish': '/trips/finish/',
        'trips-summary': '/trips/summary/',
        'trips-confirm': '/trips/confirm/',
    };

    function messageMarkup(profile) {
        const status = String(profile.route_assignment_status || 'pending').toLowerCase();
        const text = status === 'rejected'
            ? 'Your route request was rejected. Contact PoloNav operations for assistance.'
            : 'Waiting for route assignment. Trip features will be available after an administrator approves your route.';
        return '<section class="driver-trip-message"><i class="bi bi-hourglass-split" aria-hidden="true"></i><h3>' +
            ui.escapeHtml(status === 'rejected' ? 'Route request rejected' : 'Waiting for route assignment') +
            '</h3><p>' + ui.escapeHtml(text) + '</p><a class="btn" href="' + ui.escapeHtml(base + '/driver/my-route.php') +
            '">View My Route</a></section>';
    }

    async function requireRoute() {
        const profile = await api('/api/drivers.php');
        const header = document.getElementById('driver-header-name');
        if (header) header.textContent = profile.full_name || profile.username || 'Driver';
        if (profile.route_assignment_status === 'approved' && profile.route_id) return profile;
        const pageContent = document.getElementById('driver-trip-page');
        if (pageContent) pageContent.innerHTML = messageMarkup(profile);
        return null;
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(value.replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? '—' : date.toLocaleString();
    }

    function statusChip(status) {
        const safe = String(status || 'Pending');
        const className = safe.toLowerCase().replace(/\s+/g, '-');
        return '<span class="driver-trip-status status-' + ui.escapeHtml(className) + '">' + ui.escapeHtml(safe) + '</span>';
    }

    function bindTripsToggle() {
        const toggle = document.querySelector('[data-nav-toggle="trips"]');
        const submenu = document.querySelector('[data-nav-submenu="trips"]');
        if (!toggle || !submenu) return;
        toggle.addEventListener('click', function () {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            const isProcessPage = page.indexOf('trips-') === 0 && page !== 'trips-history';
            if (!expanded && !isProcessPage) {
                window.location.href = base + '/trips/start/';
                return;
            }
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            submenu.hidden = expanded;
        });
    }

    function canOpenStep(workflow, step) {
        return Boolean(workflow[{
            'trips-start': 'can_start',
            'trips-passengers': 'can_passengers',
            'trips-finish': 'can_finish',
            'trips-summary': 'can_summary',
            'trips-confirm': 'can_confirm',
        }[step]]);
    }

    function syncStepLinks(workflow) {
        document.querySelectorAll('[data-trip-step]').forEach(function (link) {
            const allowed = canOpenStep(workflow, link.dataset.tripStep);
            link.classList.toggle('is-locked', !allowed);
            link.setAttribute('aria-disabled', allowed ? 'false' : 'true');
            link.tabIndex = allowed ? 0 : -1;
            if (!allowed) link.title = 'Complete the previous trip step first.';
            else link.removeAttribute('title');
        });
        document.querySelectorAll('.driver-step-navigation a.is-locked').forEach(function (link) {
            link.setAttribute('aria-disabled', 'true');
            link.tabIndex = -1;
        });
        document.querySelectorAll('[data-trip-step], .driver-step-navigation a.is-locked').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (link.getAttribute('aria-disabled') === 'true') {
                    event.preventDefault();
                    ui.toast('Complete the current trip step before continuing.');
                }
            });
        });
    }

    function requiredStep(workflow) {
        if (workflow.active) return 'trips-passengers';
        if (workflow.pending_trips.length) return 'trips-summary';
        return 'trips-start';
    }

    function redirectToRequiredStep(workflow) {
        if (workflow.active && ['trips-start', 'trips-passengers'].includes(page)) return false;
        if (workflow.active && page === 'trips-finish' && Number(workflow.active.boarded_count) > 0) return false;
        if (!workflow.active && workflow.pending_trips.length && Object.prototype.hasOwnProperty.call(stepUrls, page)) return false;
        const target = requiredStep(workflow);
        if (page !== target) {
            window.location.replace(base + stepUrls[target]);
            return true;
        }
        return false;
    }

    async function historyPage() {
        const rows = await api('/api/driver-trips.php?view=history');
        const tbody = document.querySelector('#driver-trip-history tbody');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">No trips yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (trip) {
            return '<tr><td>#' + ui.escapeHtml(trip.id) + '</td><td>' + ui.escapeHtml(formatDate(trip.recorded_at)) +
                '</td><td>' + ui.escapeHtml(trip.route_name || 'Route unavailable') + '</td><td>' +
                ui.escapeHtml(trip.boarded_count || trip.number_of_passengers || 0) + '</td><td>' + statusChip(trip.status) + '</td></tr>';
        }).join('');
    }

    async function startPage(workflow) {
        const options = await api('/api/driver-trips.php?view=options');
        const form = document.getElementById('driver-start-trip');
        const routeInput = document.getElementById('driver-trip-route');
        const vehicleSelect = document.getElementById('driver-trip-jeepney');
        const message = document.getElementById('driver-trip-form-message');
        routeInput.value = options.route ? options.route.route_name : '';
        vehicleSelect.innerHTML = '<option value="">Select a vehicle...</option>' + options.jeepneys.map(function (jeepney) {
            return '<option value="' + ui.escapeHtml(jeepney.id) + '">' +
                ui.escapeHtml(jeepney.plate_number + ' · Unit ' + jeepney.jeepney_number) + '</option>';
        }).join('');
        const button = document.querySelector('[form="driver-start-trip"]');
        if (workflow.active || workflow.pending_trips.length) {
            const readOnlyTrip = workflow.active || workflow.pending_trips[0];
            routeInput.value = readOnlyTrip.route_name || routeInput.value;
            vehicleSelect.value = String(readOnlyTrip.jeepney_id);
            document.getElementById('driver-trip-vehicle-type').value = readOnlyTrip.vehicle_type || 'traditional';
            document.getElementById('driver-start-odometer').value = readOnlyTrip.starting_odometer || '';
            form.querySelectorAll('input, select').forEach(function (field) { field.disabled = true; });
            button.disabled = true;
            message.textContent = 'Trip details are read-only while a trip is in progress or awaiting confirmation.';
            message.hidden = false;
            return;
        } else if (!options.jeepneys.length) {
            button.disabled = true;
            message.textContent = 'No vehicle is assigned to your approved route. Contact PoloNav operations.';
            message.hidden = false;
        }
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            button.disabled = true;
            message.hidden = true;
            try {
                await api('/api/driver-trips.php', {
                    method: 'POST',
                    body: Object.assign({ action: 'start' }, ui.formValues(form)),
                });
                ui.toast('Trip started.');
                window.location.href = base + '/trips/passengers/';
            } catch (error) {
                message.textContent = error.message;
                message.hidden = false;
                button.disabled = false;
            }
        });
    }

    async function passengersPage(workflow) {
        const section = document.getElementById('driver-trip-page');
        const tbody = document.querySelector('#driver-trip-fares tbody');
        if (!workflow.active) {
            const pendingMessage = workflow.pending_trips.length
                ? 'The last trip is awaiting confirmation; it has no live passenger roster.'
                : 'Start a trip before passengers can join your jeepney.';
            section.innerHTML = '<section class="driver-trip-message"><h3>No active trip</h3><p>' +
                ui.escapeHtml(pendingMessage) + '</p><a class="btn" href="' +
                ui.escapeHtml(base + (workflow.pending_trips.length ? '/trips/confirm/' : '/trips/start/')) +
                '">' + (workflow.pending_trips.length ? 'Confirm trip' : 'Start Trip') + '</a></section>';
            return;
        }
        document.getElementById('driver-current-trip-label').textContent =
            'Trip #' + trip.id + ' · ' + (trip.plate_number || '') + ' · ' + (trip.route_name || '');

        async function loadRoster() {
            try {
                const result = await api('/api/driver-trips.php?view=passengers');
                if (!result.trip) {
                    tbody.innerHTML = '<tr><td colspan="7" class="empty">This trip is no longer active.</td></tr>';
                    return;
                }
                const passengers = result.passengers || [];
                document.getElementById('driver-boarded-count').textContent = result.trip.boarded_count;
                document.getElementById('driver-dropped-count').textContent = result.trip.dropped_off_count;
                document.getElementById('driver-onboard-count').textContent = passengers.length;
                const next = document.getElementById('driver-step-next');
                const canContinue = Number(result.trip.boarded_count) > 0;
                next.classList.toggle('is-locked', !canContinue);
                next.setAttribute('aria-disabled', canContinue ? 'false' : 'true');
                next.tabIndex = canContinue ? 0 : -1;
                tbody.innerHTML = passengers.length ? passengers.map(function (passenger) {
                    return '<tr><td>' + ui.escapeHtml(passenger.passenger_name) + '</td><td>' +
                        ui.escapeHtml(passenger.origin_name) + '</td><td>' +
                        ui.escapeHtml(passenger.destination_name) + '</td><td>' +
                        ui.escapeHtml(passenger.passenger_type) + '</td><td>' +
                        ui.escapeHtml(Number(passenger.distance_km).toFixed(2)) + ' km</td><td>' +
                        ui.money(passenger.fare) + '</td><td>' +
                        ui.escapeHtml(formatDate(passenger.boarded_at)) + '</td></tr>';
                }).join('') : '<tr><td colspan="7" class="empty">No passengers have joined this trip yet.</td></tr>';
            } catch (error) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty is-error">' +
                    ui.escapeHtml(error.message || 'Unable to load the passenger roster.') + '</td></tr>';
            }
        }
        await loadRoster();
        const refreshTimer = window.setInterval(loadRoster, 10000);
        window.addEventListener('pagehide', function () { window.clearInterval(refreshTimer); }, { once: true });
    }

    async function finishPage(workflow) {
        const trip = workflow.active || workflow.pending_trips[0];
        const detail = document.getElementById('driver-finish-trip-details');
        const finish = document.getElementById('driver-finish-trip');
        const message = document.getElementById('driver-finish-trip-message');
        function renderFinishState(currentWorkflow) {
            const active = currentWorkflow.active;
            detail.innerHTML = '<div><span>Trip</span><strong>#' + ui.escapeHtml(active.id) + '</strong></div>' +
                '<div><span>Route</span><strong>' + ui.escapeHtml(active.route_name || 'Route unavailable') + '</strong></div>' +
                '<div><span>Vehicle</span><strong>' + ui.escapeHtml(active.plate_number || 'Not available') + '</strong></div>' +
                '<div><span>Passengers boarded</span><strong>' + ui.escapeHtml(active.boarded_count) + '</strong></div>' +
                '<div><span>Dropped off</span><strong>' + ui.escapeHtml(active.dropped_off_count) + '</strong></div>' +
                '<div><span>On board</span><strong>' + ui.escapeHtml(active.on_board_passenger_count) + '</strong></div>';
            finish.disabled = !currentWorkflow.can_finish_active;
            message.hidden = currentWorkflow.can_finish_active;
            if (!currentWorkflow.can_finish_active) {
                message.textContent = Number(active.boarded_count) < 1
                    ? 'At least one passenger must join before finishing.'
                    : 'Wait for all passengers to mark themselves dropped off before finishing.';
            }
        }
        detail.innerHTML = '<div><span>Trip</span><strong>#' + ui.escapeHtml(trip.id) + '</strong></div>' +
            '<div><span>Route</span><strong>' + ui.escapeHtml(trip.route_name || 'Route unavailable') + '</strong></div>' +
            '<div><span>Vehicle</span><strong>' + ui.escapeHtml(trip.plate_number || 'Not available') + '</strong></div>' +
            '<div><span>Passengers boarded</span><strong>' + ui.escapeHtml(trip.boarded_count) + '</strong></div>' +
            '<div><span>Dropped off</span><strong>' + ui.escapeHtml(trip.dropped_off_count) + '</strong></div>' +
            '<div><span>On board</span><strong>' + ui.escapeHtml(Number(trip.boarded_count) - Number(trip.dropped_off_count)) + '</strong></div>';
        if (!workflow.active) {
            finish.hidden = true;
            message.hidden = false;
            message.textContent = 'This trip has been finished and is awaiting confirmation.';
            const next = document.getElementById('driver-step-next');
            next.classList.remove('is-locked');
            next.setAttribute('aria-disabled', 'false');
            next.tabIndex = 0;
            return;
        }
        renderFinishState(workflow);
        let refreshing = false;
        const refreshTimer = window.setInterval(async function () {
            if (refreshing) return;
            refreshing = true;
            try {
                const currentWorkflow = await api('/api/driver-trips.php?view=workflow');
                if (!currentWorkflow.active) {
                    window.location.replace(base + '/trips/summary/');
                    return;
                }
                renderFinishState(currentWorkflow);
            } catch (error) {
                message.hidden = false;
                message.textContent = error.message || 'Unable to refresh passenger drop-off status.';
            } finally {
                refreshing = false;
            }
        }, 10000);
        window.addEventListener('pagehide', function () { window.clearInterval(refreshTimer); }, { once: true });
        finish.addEventListener('click', async function () {
            finish.disabled = true;
            try {
                await api('/api/driver-trips.php', { method: 'PATCH', body: { action: 'finish' } });
                ui.toast('Trip finished. Review its summary next.');
                window.location.href = base + '/trips/summary/';
            } catch (error) {
                ui.toast(error.message);
                finish.disabled = false;
            }
        });
    }

    async function summaryPage(workflow) {
        const summary = await api('/api/driver-trips.php?view=summary');
        document.getElementById('driver-total-trips').textContent = summary.total_trips;
        document.getElementById('driver-total-passengers').textContent = summary.total_passengers;
        document.getElementById('driver-awaiting-confirmation').textContent = summary.awaiting_confirmation;
        const trip = workflow.pending_trips[0];
        const review = document.getElementById('driver-trip-review');
        if (trip && review) {
            review.hidden = false;
            document.getElementById('driver-trip-review-details').innerHTML =
                '<div><span>Trip</span><strong>#' + ui.escapeHtml(trip.id) + '</strong></div>' +
                '<div><span>Date</span><strong>' + ui.escapeHtml(formatDate(trip.recorded_at)) + '</strong></div>' +
                '<div><span>Route</span><strong>' + ui.escapeHtml(trip.route_name || 'Route unavailable') + '</strong></div>' +
                '<div><span>Vehicle</span><strong>' + ui.escapeHtml(trip.plate_number || 'Not available') + '</strong></div>' +
                '<div><span>Passengers boarded</span><strong>' + ui.escapeHtml(trip.boarded_count) + '</strong></div>' +
                '<div><span>Dropped off</span><strong>' + ui.escapeHtml(trip.dropped_off_count) + '</strong></div>' +
                '<div><span>Starting odometer</span><strong>' + ui.escapeHtml(trip.starting_odometer || '—') + '</strong></div>';
        }
    }

    async function confirmPage() {
        const trips = await api('/api/driver-trips.php?view=pending');
        const form = document.getElementById('driver-confirm-trip');
        const empty = document.getElementById('driver-no-pending');
        const select = document.getElementById('driver-pending-trip');
        if (!trips.length) {
            empty.hidden = false;
            return;
        }
        select.innerHTML = trips.map(function (trip) {
            return '<option value="' + ui.escapeHtml(trip.id) + '">Trip #' + ui.escapeHtml(trip.id) + ' · ' +
                ui.escapeHtml(trip.route_name || 'Route unavailable') + ' · ' +
                ui.escapeHtml(trip.boarded_count || trip.number_of_passengers || 0) + ' passengers · ' +
                ui.escapeHtml(formatDate(trip.recorded_at)) + '</option>';
        }).join('');
        form.hidden = false;
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const submit = form.querySelector('button[type="submit"]');
            submit.disabled = true;
            try {
                await api('/api/driver-trips.php', {
                    method: 'PATCH',
                    body: Object.assign({ action: 'confirm' }, ui.formValues(form)),
                });
                ui.toast('Trip confirmed and locked.');
                const remaining = await api('/api/driver-trips.php?view=pending');
                window.location.href = base + (remaining.length ? '/trips/summary/' : '/trips/history/');
            } catch (error) {
                ui.toast(error.message);
                submit.disabled = false;
            }
        });
        document.getElementById('driver-cancel-confirm').addEventListener('click', function () {
            form.reset();
            select.selectedIndex = 0;
        });
    }

    async function start() {
        bindTripsToggle();
        try {
            const profile = await api('/api/drivers.php');
            const header = document.getElementById('driver-header-name');
            if (header) header.textContent = profile.full_name || profile.username || 'Driver';
            if (profile.route_assignment_status !== 'approved' || !profile.route_id) {
                document.getElementById('driver-trip-page').innerHTML =
                    '<section class="driver-trip-message"><i class="bi bi-hourglass-split" aria-hidden="true"></i><h3>Waiting for route assignment</h3>' +
                    '<p>Trip features are unavailable until an administrator approves your route.</p><a class="btn" href="' +
                    ui.escapeHtml((document.documentElement.dataset.base || '') + '/driver/my-route.php') + '">View My Route</a></section>';
                return;
            }
            const workflow = await api('/api/driver-trips.php?view=workflow');
            syncStepLinks(workflow);
            if (page !== 'trips-history' && redirectToRequiredStep(workflow)) return;
            if (page === 'trips-history') await historyPage();
            if (page === 'trips-start') await startPage(workflow);
            if (page === 'trips-passengers') await passengersPage(workflow);
            if (page === 'trips-finish') await finishPage(workflow);
            if (page === 'trips-summary') await summaryPage(workflow);
            if (page === 'trips-confirm') await confirmPage();
        } catch (error) {
            ui.toast(error.message);
            const section = document.getElementById('driver-trip-page');
            if (section) section.innerHTML = '<p class="driver-trip-message" role="alert">' + ui.escapeHtml(error.message) + '</p>';
        }
    }

    start();
})();
