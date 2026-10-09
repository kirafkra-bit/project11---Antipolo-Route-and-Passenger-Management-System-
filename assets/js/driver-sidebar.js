(function () {
    const card = document.getElementById('driver-sidebar-card');
    if (!card) return;

    const api = PoloNav.api;
    const ui = PNUI;
    const userId = card.dataset.userId || 'current';
    const shiftKey = 'polonav.driver.' + userId + '.shift-status';
    const preferencePrefix = 'polonav.driver.' + userId + '.preference.';
    const shiftButtons = Array.from(card.querySelectorAll('[data-shift-status]'));

    function readStorage(key, fallback) {
        try {
            return window.localStorage.getItem(key) || fallback;
        } catch (error) {
            ui.toast('Driver display preferences are unavailable in this browser.');
            return fallback;
        }
    }

    function writeStorage(key, value) {
        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (error) {
            ui.toast('Could not save this display preference on this device.');
            return false;
        }
    }

    function setShiftStatus(status, persist) {
        shiftButtons.forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.dataset.shiftStatus === status));
        });
        if (persist) writeStorage(shiftKey, status);
    }

    function setupShiftStatus() {
        const valid = ['On Duty', 'On Break', 'Off Duty'];
        const stored = readStorage(shiftKey, 'Off Duty');
        setShiftStatus(valid.indexOf(stored) === -1 ? 'Off Duty' : stored, false);
        shiftButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setShiftStatus(button.dataset.shiftStatus, true);
            });
        });
    }

    function setupQuickPreferences() {
        document.querySelectorAll('[data-driver-preference]').forEach(function (button) {
            const key = preferencePrefix + button.dataset.driverPreference;
            button.setAttribute('aria-checked', readStorage(key, 'false') === 'true' ? 'true' : 'false');
            button.addEventListener('click', function () {
                const enabled = button.getAttribute('aria-checked') !== 'true';
                if (writeStorage(key, String(enabled))) {
                    button.setAttribute('aria-checked', String(enabled));
                }
            });
        });
    }

    function setupActions() {
        document.querySelectorAll('[data-sidebar-action="sos"]').forEach(function (button) {
            button.addEventListener('click', function () {
                ui.toast('Emergency reporting is not connected yet. For immediate danger, contact local emergency services.');
            });
        });
    }

    async function loadDriverCard() {
        const [profile, jeepneys] = await Promise.all([
            api('/api/drivers.php'),
            api('/api/jeepneys.php'),
        ]);
        const name = profile.full_name || profile.username || 'Driver';
        const nameElement = document.getElementById('driver-sidebar-name');
        const avatar = document.getElementById('driver-sidebar-avatar');
        const vehicle = document.getElementById('driver-sidebar-vehicle');
        const headerName = document.getElementById('driver-header-name');
        if (nameElement) nameElement.textContent = name;
        if (headerName) headerName.textContent = name;
        if (avatar) {
            avatar.textContent = name.trim().split(/\s+/).map(function (part) {
                return part.charAt(0);
            }).slice(0, 2).join('').toUpperCase() || 'D';
        }
        if (vehicle) {
            const assigned = jeepneys[0];
            const text = assigned
                ? 'Jeepney #' + (assigned.jeepney_number || '—') + ' · Plate ' + (assigned.plate_number || '—')
                : 'No assigned vehicle';
            vehicle.querySelector('span').textContent = text;
        }
    }

    setupShiftStatus();
    setupQuickPreferences();
    setupActions();
    loadDriverCard().catch(function (error) {
        ui.toast('Could not load driver sidebar details: ' + error.message);
        const vehicle = document.getElementById('driver-sidebar-vehicle');
        if (vehicle) vehicle.querySelector('span').textContent = 'Vehicle details unavailable';
    });
})();
