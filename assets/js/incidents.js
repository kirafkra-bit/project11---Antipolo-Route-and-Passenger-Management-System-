(function () {
    const list = document.getElementById('incident-list');
    const form = document.getElementById('incident-report-form');
    if (!list || !form) return;

    const api = PoloNav.api;
    const ui = PNUI;
    const formMessage = document.getElementById('incident-form-message');
    const locationMessage = document.getElementById('incident-location-message');
    const labels = {
        flooding: 'Flooding',
        accident: 'Accident',
        road_construction: 'Road construction',
        vehicle_breakdown: 'Vehicle breakdown',
        obstruction: 'Road obstruction',
        other: 'Other',
    };

    function setMessage(element, text, error) {
        element.hidden = !text;
        element.textContent = text;
        element.classList.toggle('is-error', Boolean(error));
    }

    function formatDate(value) {
        const date = value ? new Date(value) : null;
        return date && !Number.isNaN(date.getTime()) ? date.toLocaleString() : 'Date unavailable';
    }

    function render(items) {
        list.innerHTML = items.length
            ? items.map(function (item) {
                const coordinates = item.latitude !== null && item.longitude !== null
                    ? '<small>Coordinates: ' + ui.escapeHtml(Number(item.latitude).toFixed(5)) + ', ' +
                        ui.escapeHtml(Number(item.longitude).toFixed(5)) + '</small>'
                    : '';
                return '<article class="incident-report"><div class="incident-report-heading"><span class="incident-type-badge">' +
                    ui.escapeHtml(labels[item.incident_type] || item.incident_type) + '</span><time>' +
                    ui.escapeHtml(formatDate(item.reported_at)) + '</time></div><h4>' +
                    ui.escapeHtml(item.location_description) + '</h4><p>' +
                    ui.escapeHtml(item.description) + '</p><div class="incident-report-meta"><span>Route: ' +
                    ui.escapeHtml(item.route_name || 'Assigned route') + '</span><span>Reported by ' +
                    ui.escapeHtml(item.driver_name || 'Driver') + '</span></div>' + coordinates + '</article>';
            }).join('')
            : '<p class="incident-empty">No active incident reports have been shared in the last 24 hours.</p>';
    }

    function trafficStatusLabel(status) {
        const labelsByStatus = {
            flowing: 'Traffic is flowing',
            light: 'Light traffic',
            moderate: 'Moderate traffic',
            heavy: 'Heavy traffic',
            closed: 'Road closure reported',
            unknown: 'Traffic status unavailable',
        };
        return labelsByStatus[status] || labelsByStatus.unknown;
    }

    function renderTrafficResult(result) {
        const panel = document.getElementById('live-traffic-result');
        const location = result.location || {};
        const checked = result.checked_at ? new Date(result.checked_at) : null;
        const checkedLabel = checked && !Number.isNaN(checked.getTime())
            ? checked.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : 'just now';
        const delay = Number.isFinite(Number(result.delay_seconds))
            ? Math.round(Number(result.delay_seconds) / 60)
            : null;
        const delayText = delay === null
            ? 'Travel-time comparison unavailable'
            : delay === 0 ? 'No additional delay reported' : 'About ' + delay + ' min extra travel time';
        const confidence = Number.isFinite(Number(result.confidence))
            ? '<span>Provider confidence: ' + Math.round(Number(result.confidence) * 100) + '%</span>'
            : '';
        const speed = Number.isFinite(Number(result.current_speed_kmph)) &&
            Number.isFinite(Number(result.free_flow_speed_kmph))
            ? '<span>Current speed ' + Number(result.current_speed_kmph).toFixed(0) +
                ' km/h · usual free-flow speed ' + Number(result.free_flow_speed_kmph).toFixed(0) + ' km/h</span>'
            : '';

        panel.hidden = false;
        panel.classList.remove('is-error');
        panel.innerHTML = '<div class="live-traffic-result-heading"><span class="live-traffic-status status-' +
            ui.escapeHtml(result.status || 'unknown') + '"><i class="bi bi-circle-fill" aria-hidden="true"></i>' +
            ui.escapeHtml(trafficStatusLabel(result.status)) + '</span><time>Checked ' +
            ui.escapeHtml(checkedLabel) + '</time></div><p>' + ui.escapeHtml(delayText) +
            '</p><div class="live-traffic-result-meta">' + speed + confidence +
            '<span>' + ui.escapeHtml(location.name || 'Selected route checkpoint') + ' on ' +
            ui.escapeHtml(result.route_name || 'your assigned route') + '</span></div><small>Source: TomTom Traffic API</small>';
    }

    async function checkLiveTraffic(pointId, button) {
        const panel = document.getElementById('live-traffic-result');
        panel.hidden = false;
        panel.classList.remove('is-error');
        panel.textContent = 'Checking traffic at the selected route checkpoint...';
        try {
            const result = await api('/api/traffic.php', {
                method: 'POST',
                body: { point_id: pointId },
            });
            renderTrafficResult(result);
        } catch (error) {
            panel.classList.add('is-error');
            panel.textContent = error.message || 'Unable to check traffic for this area.';
        } finally {
            button.disabled = !document.getElementById('live-traffic-point').value;
        }
    }

    async function loadTrafficCheckpoints() {
        const selector = document.getElementById('live-traffic-point');
        const button = document.getElementById('live-traffic-check');
        const panel = document.getElementById('live-traffic-result');
        try {
            const route = await api('/api/traffic.php');
            selector.innerHTML = '<option value="">Choose a route checkpoint...</option>' +
                route.checkpoints.map(function (point) {
                    return '<option value="' + ui.escapeHtml(point.id) + '">' +
                        ui.escapeHtml(point.name) + '</option>';
                }).join('');
            if (!route.checkpoints.length) {
                selector.innerHTML = '<option value="">No checkpoints configured for this route</option>';
                panel.hidden = false;
                panel.classList.add('is-error');
                panel.textContent = 'Ask an administrator to configure pickup and drop-off checkpoints for ' +
                    (route.route_name || 'your assigned route') + '.';
                return;
            }
            selector.disabled = false;
            selector.addEventListener('change', function () {
                button.disabled = !selector.value;
            });
        } catch (error) {
            selector.innerHTML = '<option value="">Route checkpoints unavailable</option>';
            panel.hidden = false;
            panel.classList.add('is-error');
            panel.textContent = error.message || 'Unable to load checkpoints for your assigned route.';
        }
    }

    document.getElementById('live-traffic-check').addEventListener('click', function () {
        const button = this;
        const selector = document.getElementById('live-traffic-point');
        if (!selector.value) {
            return;
        }
        button.disabled = true;
        checkLiveTraffic(selector.value, button);
    });

    async function loadIncidents() {
        list.innerHTML = '<p class="muted">Loading global incident reports...</p>';
        const items = await api('/api/incidents.php');
        render(items);
    }

    document.getElementById('incidents-refresh').addEventListener('click', function () {
        loadIncidents().catch(function (error) {
            list.innerHTML = '<p class="incident-empty is-error">' + ui.escapeHtml(error.message) + '</p>';
        });
    });

    document.getElementById('incident-use-location').addEventListener('click', function () {
        if (!navigator.geolocation) {
            setMessage(locationMessage, 'Location services are not supported by this browser. Enter a landmark instead.', true);
            return;
        }
        setMessage(locationMessage, 'Requesting your device location...', false);
        navigator.geolocation.getCurrentPosition(function (position) {
            document.getElementById('incident-latitude').value = String(position.coords.latitude);
            document.getElementById('incident-longitude').value = String(position.coords.longitude);
            setMessage(locationMessage, 'Coordinates added. Include a road or landmark above so nearby drivers can identify the place.', false);
        }, function (error) {
            const reason = error.code === error.PERMISSION_DENIED
                ? 'Location access was denied. You can still enter a landmark manually.'
                : 'Could not get your location. You can still enter a landmark manually.';
            setMessage(locationMessage, reason, true);
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        setMessage(formMessage, '', false);
        try {
            const values = ui.formValues(form);
            const incident = await api('/api/incidents.php', { method: 'POST', body: values });
            if (!incident || !incident.id) {
                throw new Error('The server did not confirm that the incident report was saved.');
            }
            form.reset();
            document.getElementById('incident-latitude').value = '';
            document.getElementById('incident-longitude').value = '';
            setMessage(formMessage, 'Incident report submitted and shared with all drivers.', false);
            loadIncidents().catch(function (error) {
                list.innerHTML = '<p class="incident-empty is-error">Report submitted, but the global incident list could not refresh: ' +
                    ui.escapeHtml(error.message) + '</p>';
            });
        } catch (error) {
            setMessage(formMessage, error.message || 'Unable to submit this incident report.', true);
        } finally {
            button.disabled = false;
        }
    });

    loadIncidents().catch(function (error) {
        list.innerHTML = '<p class="incident-empty is-error">' + ui.escapeHtml(error.message) + '</p>';
        form.querySelector('[type="submit"]').disabled = true;
    });
    loadTrafficCheckpoints();
})();
