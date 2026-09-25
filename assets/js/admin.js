(function () {
    const api = PoloNav.api;
    const ui = PNUI;
    const page = document.documentElement.dataset.page;

    function bindSearch(inputId, onSearch) {
        const input = document.getElementById(inputId);
        if (!input) return;
        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                onSearch(input.value.trim());
            }, 250);
        });
    }

    function actions(id, extra) {
    return '<button class="btn-edit" data-edit="' + id + '">Edit</button> ' +
        '<button class="btn btn-danger btn-small" data-del="' + id + '">Delete</button>' +
        (extra || '');
}

    async function adminOverview() {
        const data = await api('/api/dashboard.php');
        const stats = data.stats || {};
        const map = {
            total_users: 'stat-users',
            total_passengers: 'stat-passengers',
            total_drivers: 'stat-drivers',
            total_jeepneys: 'stat-jeepneys',
            total_routes: 'stat-routes',
            total_trips: 'stat-trips',
            total_complaints: 'stat-complaints',
            open_complaints: 'stat-open-complaints',
            active_drivers: 'stat-active',
        };
        Object.keys(map).forEach(function (key) {
            const el = document.getElementById(map[key]);
            if (el) el.textContent = stats[key] ?? 0;
        });

        ui.renderRows(document.querySelector('#recent-trips tbody'), data.recent_trips, function (row) {
            return '<tr>' +
                '<td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) + '</td>' +
                '<td>' + ui.escapeHtml(row.passenger_name) + '</td>' +
                '<td>' + ui.escapeHtml(row.driver_name) + '</td>' +
                '<td>' + ui.escapeHtml(row.plate_number) + '</td>' +
                '<td>' + ui.escapeHtml(row.number_of_passengers) + '</td>' +
                '</tr>';
        });
    }

    async function usersPage() {
        const tbody = document.querySelector('#users-table tbody');

        async function load(q) {
            const query = q ? ('?q=' + encodeURIComponent(q)) : '';
            const rows = await api('/api/users.php' + query);
            ui.renderRows(tbody, rows, function (row) {
                return '<tr>' +
                    '<td>' + ui.escapeHtml(row.username) + '</td>' +
                    '<td>' + ui.statusChip(row.role) + '</td>' +
                    '<td>' + ui.escapeHtml(row.created_at) + '</td>' +
                    '<td>' + actions(row.id) + '</td></tr>';
            });
        }

        usersPageLoad = load;

        document.getElementById('add-user').addEventListener('click', function () {
            ui.openModal('Create account', userForm());
            wireUserForm();
        });

        tbody.addEventListener('click', async function (event) {
            const editId = event.target.getAttribute('data-edit');
            const delId = event.target.getAttribute('data-del');
            if (editId) {
                const row = await api('/api/users.php?id=' + editId);
                ui.openModal('Edit account', userForm(row));
                wireUserForm(row);
            }
            if (delId && window.confirm('Delete this account? Linked passenger or driver profiles will also be removed if allowed.')) {
                try {
                    await api('/api/users.php', { method: 'DELETE', body: { id: delId } });
                    ui.toast('Account deleted.');
                    load(document.getElementById('user-search').value);
                } catch (err) {
                    ui.toast(err.message);
                }
            }
        });

        bindSearch('user-search', load);
        await load();
    }

    function userForm(row) {
        row = row || {};
        return '<form id="entity-form" class="stack">' +
            '<input type="hidden" name="id" value="' + ui.escapeHtml(row.id || '') + '">' +
            '<label>Username</label><input name="username" required value="' + ui.escapeHtml(row.username || '') + '">' +
            '<label>Password ' + (row.id ? '(leave blank to keep)' : '') + '</label><input name="password" type="password">' +
            '<label>Role</label><select name="role" id="user-role">' +
            option('admin', row.role) + option('driver', row.role) + option('passenger', row.role) +
            '</select>' +
            '<div id="role-extra"></div>' +
            '<button class="btn" type="submit">Save</button></form>';
    }

    function option(value, selected) {
        return '<option value="' + value + '"' + (selected === value ? ' selected' : '') + '>' + value + '</option>';
    }

    function wireUserForm(existing) {
        const form = document.getElementById('entity-form');
        const role = document.getElementById('user-role');
        const extra = document.getElementById('role-extra');

        function paintExtra() {
            if (existing && existing.id) {
                extra.innerHTML = '<p class="muted">Role changes here do not rebuild passenger or driver profiles. Use the People pages for profile details.</p>';
                return;
            }
            if (role.value === 'passenger') {
                extra.innerHTML = '<label>Full name</label><input name="name" required>' +
                    '<label>Contact number</label><input name="contact_number">' +
                    '<label>Address</label><input name="address">';
            } else if (role.value === 'driver') {
                extra.innerHTML = '<label>Full name</label><input name="full_name" required>' +
                    '<label>License number</label><input name="license_number" required>' +
                    '<label>Contact number</label><input name="contact_number">' +
                    '<label>Status</label><select name="status"><option value="active">active</option><option value="inactive">inactive</option></select>';
            } else {
                extra.innerHTML = '';
            }
        }

        role.addEventListener('change', paintExtra);
        paintExtra();

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(form);
            if (!body.password) delete body.password;
            try {
                await api('/api/users.php', { method: body.id ? 'PUT' : 'POST', body: body });
                ui.closeModal();
                ui.toast('Account saved.');
                document.dispatchEvent(new Event('reload-users'));
            } catch (err) {
                ui.toast(err.message);
            }
        });
        document.addEventListener('reload-users', function reload() {
            usersPageLoad();
        }, { once: true });
    }

    let usersPageLoad = function () {};

    async function crudPage(config) {
        const tbody = document.querySelector(config.table + ' tbody');
        async function load(q) {
            const query = q ? ('?q=' + encodeURIComponent(q)) : '';
            const rows = await api(config.endpoint + query);
            ui.renderRows(tbody, rows, config.row);
        }
        usersPageLoad = load;
        document.querySelector(config.add).addEventListener('click', function () {
            ui.openModal(config.createTitle, config.form());
            bindCrudForm(config, null, load);
        });
        tbody.addEventListener('click', async function (event) {
            const editId = event.target.getAttribute('data-edit');
            const delId = event.target.getAttribute('data-del');
            if (editId) {
                const row = config.findRow
                    ? await config.findRow(editId)
                    : await api(config.endpoint + '?id=' + editId);
                ui.openModal(config.editTitle, config.form(row));
                bindCrudForm(config, row, load);
            }
            if (delId && window.confirm(config.deleteMessage || 'Delete this record?')) {
                try {
                    const body = config.deleteBody ? config.deleteBody(delId) : { id: delId };
                    await api(config.endpoint, { method: 'DELETE', body: body });
                    ui.toast('Deleted.');
                    load(document.querySelector(config.search).value);
                } catch (err) {
                    ui.toast(err.message);
                }
            }
        });
        bindSearch(config.search.replace('#', ''), load);
        await load();
    }

    function bindCrudForm(config, row, load) {
        const form = document.getElementById('entity-form');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const body = ui.formValues(form);
            try {
                await api(config.endpoint, { method: row ? 'PUT' : 'POST', body: body });
                ui.closeModal();
                ui.toast('Saved.');
                load(document.querySelector(config.search).value);
            } catch (err) {
                ui.toast(err.message);
            }
        });
    }

    function passengerForm(row) {
        row = row || {};
        return '<form id="entity-form" class="stack">' +
            '<input type="hidden" name="passengers_id" value="' + ui.escapeHtml(row.passengers_id || '') + '">' +
            (row.passengers_id ? '' : '<label>Username</label><input name="username" required><label>Password</label><input name="password" type="password" required>') +
            '<label>Name</label><input name="name" required value="' + ui.escapeHtml(row.name || '') + '">' +
            '<label>Contact number</label><input name="contact_number" value="' + ui.escapeHtml(row.contact_number || '') + '">' +
            '<label>Address</label><input name="address" value="' + ui.escapeHtml(row.address || '') + '">' +
            '<button class="btn" type="submit">Save</button></form>';
    }

    function driverForm(row) {
        row = row || {};
        return '<form id="entity-form" class="stack">' +
            '<input type="hidden" name="id" value="' + ui.escapeHtml(row.id || '') + '">' +
            (row.id ? '' : '<label>Username</label><input name="username" required><label>Password</label><input name="password" type="password" required>') +
            '<label>Full name</label><input name="full_name" required value="' + ui.escapeHtml(row.full_name || '') + '">' +
            '<label>License number</label><input name="license_number" required value="' + ui.escapeHtml(row.license_number || '') + '">' +
            '<label>Contact number</label><input name="contact_number" value="' + ui.escapeHtml(row.contact_number || '') + '">' +
            '<label>Status</label><select name="status">' + option('active', row.status || 'active') + option('inactive', row.status) + '</select>' +
            '<button class="btn" type="submit">Save</button></form>';
    }

    async function jeepneyPage() {
        const [drivers, routes] = await Promise.all([
            api('/api/drivers.php'),
            api('/api/routes.php'),
        ]);

        function form(row) {
            row = row || {};
            return '<form id="entity-form" class="stack">' +
                '<input type="hidden" name="id" value="' + ui.escapeHtml(row.id || '') + '">' +
                '<label>Plate number</label><input name="plate_number" required value="' + ui.escapeHtml(row.plate_number || '') + '">' +
                '<label>Jeepney number</label><input name="jeepney_number" required value="' + ui.escapeHtml(row.jeepney_number || '') + '">' +
                '<label>Driver</label><select name="drivers_id">' + drivers.map(function (d) {
                    return '<option value="' + d.id + '"' + (String(row.drivers_id) === String(d.id) ? ' selected' : '') + '>' + ui.escapeHtml(d.full_name) + '</option>';
                }).join('') + '</select>' +
                '<label>Route</label><select name="route_id">' + routes.map(function (r) {
                    return '<option value="' + r.id + '"' + (String(row.route_id) === String(r.id) ? ' selected' : '') + '>' + ui.escapeHtml(r.route_name) + '</option>';
                }).join('') + '</select>' +
                '<button class="btn" type="submit">Save</button></form>';
        }

        await crudPage({
            endpoint: '/api/jeepneys.php',
            table: '#data-table',
            add: '#add-btn',
            search: '#search',
            createTitle: 'Assign jeepney',
            editTitle: 'Edit jeepney',
            form: form,
            row: function (row) {
                return '<tr><td>' + ui.escapeHtml(row.plate_number) + '</td><td>' + ui.escapeHtml(row.jeepney_number) +
                    '</td><td>' + ui.escapeHtml(row.driver_name) + '</td><td>' + ui.escapeHtml(row.route_name) +
                    '</td><td>' + actions(row.id) + '</td></tr>';
            },
        });
    }

    function routeForm(row) {
        row = row || {};
        return '<form id="entity-form" class="form-grid">' +
            '<input type="hidden" name="id" value="' + ui.escapeHtml(row.id || '') + '">' +
            '<div class="full"><label>Route name</label><input name="route_name" required value="' + ui.escapeHtml(row.route_name || '') + '"></div>' +
            '<div class="full"><label>Destinations</label><input name="destinations" value="' + ui.escapeHtml(row.destinations || '') + '"></div>' +
            '<div><label>Distance (km)</label><input name="distance" value="' + ui.escapeHtml(row.distance || '') + '"></div>' +
            '<div><label>Stored fare</label><input name="fare" value="' + ui.escapeHtml(row.fare || '0') + '"></div>' +
            '<div><label>Latitude</label><input name="latitude" value="' + ui.escapeHtml(row.latitude || '') + '"></div>' +
            '<div><label>Longitude</label><input name="longitude" value="' + ui.escapeHtml(row.longitude || '') + '"></div>' +
            '<div class="full"><button class="btn" type="submit">Save</button></div></form>';
    }

    async function tripsPage() {
        const [passengers, drivers, jeepneys] = await Promise.all([
            api('/api/passengers.php'),
            api('/api/drivers.php'),
            api('/api/jeepneys.php'),
        ]);

        function form(row) {
            row = row || {};
            return '<form id="entity-form" class="form-grid">' +
                '<input type="hidden" name="id" value="' + ui.escapeHtml(row.id || '') + '">' +
                '<div><label>Pickup</label><input name="pickup" required value="' + ui.escapeHtml(row.pickup || '') + '"></div>' +
                '<div><label>Drop-off</label><input name="drop_off" required value="' + ui.escapeHtml(row.drop_off || '') + '"></div>' +
                '<div><label>Passenger count</label><input name="number_of_passengers" required value="' + ui.escapeHtml(row.number_of_passengers || '1') + '"></div>' +
                '<div><label>Passenger</label><select name="passengers_id">' + passengers.map(function (p) {
                    return '<option value="' + p.passengers_id + '"' + (String(row.passengers_id) === String(p.passengers_id) ? ' selected' : '') + '>' + ui.escapeHtml(p.name) + '</option>';
                }).join('') + '</select></div>' +
                '<div><label>Driver</label><select name="drivers_id" id="trip-driver">' + drivers.map(function (d) {
                    return '<option value="' + d.id + '"' + (String(row.drivers_id) === String(d.id) ? ' selected' : '') + '>' + ui.escapeHtml(d.full_name) + '</option>';
                }).join('') + '</select></div>' +
                '<div><label>Jeepney</label><select name="jeepney_id" id="trip-jeepney">' + jeepneys.map(function (j) {
                    return '<option value="' + j.id + '" data-driver="' + j.drivers_id + '"' + (String(row.jeepney_id) === String(j.id) ? ' selected' : '') + '>' + ui.escapeHtml(j.plate_number) + ' · ' + ui.escapeHtml(j.driver_name) + '</option>';
                }).join('') + '</select></div>' +
                '<div class="full"><button class="btn" type="submit">Save</button></div></form>';
        }

        await crudPage({
            endpoint: '/api/trips.php',
            table: '#data-table',
            add: '#add-btn',
            search: '#search',
            createTitle: 'Create trip',
            editTitle: 'Edit trip',
            form: form,
            row: function (row) {
                return '<tr><td>' + ui.escapeHtml(row.pickup) + ' → ' + ui.escapeHtml(row.drop_off) +
                    '</td><td>' + ui.escapeHtml(row.passenger_name) + '</td><td>' + ui.escapeHtml(row.driver_name) +
                    '</td><td>' + ui.escapeHtml(row.plate_number) + '</td><td>' + ui.escapeHtml(row.number_of_passengers) +
                    '</td><td>' + actions(row.id) + '</td></tr>';
            },
        });
    }

    async function start() {
        try {
            if (page === 'dashboard') await adminOverview();
            if (page === 'users') await usersPage();
            if (page === 'passengers') {
                await crudPage({
                    endpoint: '/api/passengers.php',
                    table: '#data-table',
                    add: '#add-btn',
                    search: '#search',
                    createTitle: 'Add passenger',
                    editTitle: 'Edit passenger',
                    deleteBody: function (id) { return { passengers_id: id }; },
                    findRow: function (id) { return api('/api/passengers.php?id=' + id); },
                    form: passengerForm,
                    row: function (row) {
                        return '<tr><td>' + ui.escapeHtml(row.name) + '</td><td>' + ui.escapeHtml(row.username) +
                            '</td><td>' + ui.escapeHtml(row.contact_number) + '</td><td>' + ui.escapeHtml(row.address) +
                            '</td><td>' + actions(row.passengers_id) + '</td></tr>';
                    },
                });
            }
            if (page === 'drivers') {
                await crudPage({
                    endpoint: '/api/drivers.php',
                    table: '#data-table',
                    add: '#add-btn',
                    search: '#search',
                    createTitle: 'Add driver',
                    editTitle: 'Edit driver',
                    form: driverForm,
                    row: function (row) {
                        return '<tr><td>' + ui.escapeHtml(row.full_name) + '</td><td>' + ui.escapeHtml(row.license_number) +
                            '</td><td>' + ui.escapeHtml(row.contact_number) + '</td><td>' + ui.statusChip(row.status) +
                            '</td><td>' + actions(row.id) + '</td></tr>';
                    },
                });
            }
            if (page === 'jeepneys') await jeepneyPage();
            if (page === 'routes') {
                await crudPage({
                    endpoint: '/api/routes.php',
                    table: '#data-table',
                    add: '#add-btn',
                    search: '#search',
                    createTitle: 'Add route',
                    editTitle: 'Edit route',
                    form: routeForm,
                    row: function (row) {
                        return '<tr><td>' + ui.escapeHtml(row.route_name) + '</td><td>' + ui.escapeHtml(row.destinations) +
                            '</td><td>' + ui.escapeHtml(row.distance) + '</td><td>' + ui.money(row.fare) +
                            '</td><td>' + actions(row.id) + '</td></tr>';
                    },
                });
            }
            if (page === 'trips') await tripsPage();
        } catch (err) {
            ui.toast(err.message);
        }
    }

    start();
})();