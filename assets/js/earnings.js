(function () {
    const panel = document.getElementById('driver-earnings');
    if (!panel) return;

    const api = PoloNav.api;
    const ui = PNUI;
    const dateInput = document.getElementById('earnings-date');
    const feeSelect = document.getElementById('earnings-fee-rate');
    const tableBody = document.querySelector('#earnings-records tbody');
    const message = document.getElementById('earnings-message');
    const feeKey = 'polonav.driver.earnings.terminal-fee-rate';
    let recordsReady = false;
    let preferenceWarning = '';

    function localDate() {
        const date = new Date();
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0'),
        ].join('-');
    }

    function money(value) {
        return '₱' + Number(value).toFixed(2);
    }

    function showMessage(text, isError) {
        message.hidden = !text;
        message.textContent = text;
        message.classList.toggle('is-error', Boolean(isError));
    }

    function formatTime(value) {
        const date = value ? new Date(String(value).replace(' ', 'T')) : null;
        return date && !Number.isNaN(date.getTime())
            ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : '—';
    }

    function render(records, gross, passengerCount) {
        const feeRate = Number(feeSelect.value);
        const deduction = Math.round(Number(gross) * feeRate * 100) / 100;
        document.getElementById('earnings-gross').textContent = money(gross);
        document.getElementById('earnings-passenger-count').textContent = String(passengerCount);
        document.getElementById('earnings-deduction').textContent = money(deduction);
        document.getElementById('earnings-net').textContent = money(Number(gross) - deduction);
        document.getElementById('earnings-fee-label').textContent = Math.round(feeRate * 100) + '%';
        document.getElementById('earnings-print-date').textContent = dateInput.value;
        tableBody.innerHTML = records.length
            ? records.map(function (record) {
                return '<tr><td>' + ui.escapeHtml(formatTime(record.boarded_at)) + '</td><td>#' +
                    ui.escapeHtml(record.trip_id) + '</td><td>' + ui.escapeHtml(record.route_name || '—') +
                    '</td><td>' + ui.escapeHtml(record.vehicle_type) +
                    '</td><td>' + ui.escapeHtml(record.passenger_type) +
                    '</td><td>' + ui.escapeHtml(record.origin_name) + '</td><td>' +
                    ui.escapeHtml(record.destination_name) + '</td><td>' +
                    ui.escapeHtml(Number(record.distance_km).toFixed(2)) + ' km</td><td>' +
                    money(record.fare) + '</td></tr>';
            }).join('')
            : '<tr><td colspan="9" class="empty">No passenger fares recorded for this date.</td></tr>';
        recordsReady = true;
    }

    async function loadDay() {
        recordsReady = false;
        showMessage('', false);
        tableBody.innerHTML = '<tr><td colspan="9" class="muted">Loading fare records...</td></tr>';
        try {
            const result = await api('/api/earnings.php?date=' + encodeURIComponent(dateInput.value));
            render(result.records, result.gross_fares, result.passenger_count);
            if (preferenceWarning) showMessage(preferenceWarning, true);
        } catch (error) {
            tableBody.innerHTML = '<tr><td colspan="9" class="empty is-error">' +
                ui.escapeHtml(error.message || 'Unable to load fare records.') + '</td></tr>';
            showMessage(error.message || 'Unable to load fare records.', true);
        }
    }

    async function loadRoute() {
        const profile = await api('/api/drivers.php');
        document.getElementById('earnings-route-name').textContent =
            profile.assigned_route_name || 'No approved route assigned';
    }

    try {
        feeSelect.value = window.localStorage.getItem(feeKey) || '0.02';
    } catch (error) {
        feeSelect.value = '0.02';
        preferenceWarning = 'Terminal fee preference could not be read; using 2% for this view.';
    }
    if (!['0.02', '0.04'].includes(feeSelect.value)) feeSelect.value = '0.02';
    feeSelect.addEventListener('change', function () {
        const previous = feeSelect.dataset.savedRate || '0.02';
        try {
            window.localStorage.setItem(feeKey, feeSelect.value);
            feeSelect.dataset.savedRate = feeSelect.value;
            document.getElementById('earnings-fee-label').textContent =
                Math.round(Number(feeSelect.value) * 100) + '%';
            if (recordsReady) loadDay();
        } catch (error) {
            feeSelect.value = previous;
            showMessage('Could not save the terminal fee preference on this device.', true);
        }
    });
    feeSelect.dataset.savedRate = feeSelect.value;

    dateInput.value = localDate();
    dateInput.addEventListener('change', loadDay);
    document.getElementById('earnings-download-pdf').addEventListener('click', function () {
        if (!recordsReady) {
            showMessage('Wait for the selected day’s fare records to finish loading before exporting.', true);
            return;
        }
        document.title = 'PoloNav Daily Earnings - ' + dateInput.value;
        window.print();
    });
    loadRoute().catch(function (error) {
        document.getElementById('earnings-route-name').textContent = 'Route unavailable';
        showMessage(error.message || 'Unable to load your assigned route.', true);
    });
    loadDay();
})();
