(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;
    const base = document.documentElement.dataset.base || '';

    function routeStatus(profile) {
        return String(profile.route_assignment_status || 'pending').toLowerCase();
    }

    function showRouteNotice(profile) {
        const notice = document.getElementById('driver-route-notice');
        if (!notice || routeStatus(profile) === 'approved') return;
        notice.hidden = false;
        notice.classList.toggle('is-rejected', routeStatus(profile) === 'rejected');
        notice.textContent = routeStatus(profile) === 'rejected'
            ? 'Your route request was rejected. Contact PoloNav operations for assistance.'
            : 'Waiting for route assignment. Trip features will be available after an administrator approves your route.';
    }

    async function loadAnnouncements() {
        const profile = await api('/api/drivers.php');
        const headerName = document.getElementById('driver-header-name');
        if (headerName) headerName.textContent = profile.full_name || profile.username || 'Driver';
        showRouteNotice(profile);
    }

    async function loadMyRoute() {
        const profile = await api('/api/drivers.php');
        const headerName = document.getElementById('driver-header-name');
        if (headerName) headerName.textContent = profile.full_name || profile.username || 'Driver';
        const requested = profile.requested_route_name || 'No route request found';
        const status = routeStatus(profile);
        const assigned = profile.assigned_route_name || 'Not assigned';
        const routeBox = document.getElementById('driver-my-route');
        const statusText = status === 'approved' ? 'Approved' : status === 'rejected' ? 'Rejected' : 'Pending';
        routeBox.innerHTML = '<article class="card driver-route-card"><div class="driver-route-card-head"><div><span>Requested route</span><h3>' +
            ui.escapeHtml(requested) + '</h3></div><span class="driver-route-status status-' + ui.escapeHtml(status) + '">' +
            ui.escapeHtml(statusText) + '</span></div><div class="driver-route-card-row"><span>Assigned route</span><strong>' +
            ui.escapeHtml(assigned) + '</strong></div>' +
            (status === 'pending' ? '<p class="driver-route-help">Waiting for route assignment. Trip features will be available once your request is approved.</p>' : '') +
            (status === 'rejected' ? '<p class="driver-route-help">Your route request was rejected. Please contact PoloNav operations.</p>' : '') +
            '</article>';
    }

    async function loadHeader() {
        const header = document.getElementById('driver-header-name');
        if (!header) return;
        const profile = await api('/api/drivers.php');
        header.textContent = profile.full_name || profile.username || 'Driver';
    }

    function bindNavigation() {
        const toggle = document.querySelector('[data-nav-toggle="trips"]');
        const submenu = document.querySelector('[data-nav-submenu="trips"]');
        if (!toggle || !submenu) return;
        toggle.addEventListener('click', function () {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            const isTripsPage = page.indexOf('trips-') === 0 && page !== 'trips-history';
            if (!expanded && !isTripsPage) {
                window.location.href = base + '/trips/history/';
                return;
            }
            toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            submenu.hidden = expanded;
        });
    }

    async function start() {
        bindNavigation();
        try {
            if (page === 'announcements') await loadAnnouncements();
            if (page === 'my-route') await loadMyRoute();
            if (page !== 'announcements' && page !== 'my-route') await loadHeader();
        } catch (error) {
            ui.toast(error.message);
            const list = document.getElementById('portal-announcements');
            const route = document.getElementById('driver-my-route');
            if (list) list.innerHTML = '<article class="card empty">' + ui.escapeHtml(error.message) + '</article>';
            if (route) route.innerHTML = '<article class="card empty">' + ui.escapeHtml(error.message) + '</article>';
        }
    }

    start();
})();
