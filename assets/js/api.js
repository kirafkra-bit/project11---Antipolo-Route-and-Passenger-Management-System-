(function () {
    const root = document.documentElement;
    const BASE = root.dataset.base || '';
    let csrf = root.dataset.csrf || '';

    async function api(path, options) {
        const opts = options || {};
        const method = (opts.method || 'GET').toUpperCase();
        const headers = {
            Accept: 'application/json',
        };
        const init = {
            method: method,
            credentials: 'same-origin',
            headers: headers,
        };

        if (opts.body) {
            if (csrf) {
                headers['X-CSRF-TOKEN'] = csrf;
            }
            if (opts.body instanceof FormData) {
                init.body = opts.body;
            } else {
                headers['Content-Type'] = 'application/json';
                init.body = JSON.stringify(Object.assign({ csrf_token: csrf }, opts.body));
            }
        }

        const response = await fetch(BASE + path, init);
        let payload = {};
        try {
            payload = await response.json();
        } catch (e) {
            throw new Error('The server did not return valid data.');
        }

        if (payload && payload.data && payload.data.csrf_token) {
            csrf = payload.data.csrf_token;
            root.dataset.csrf = csrf;
        }

        if (!response.ok || payload.success === false) {
            if (response.status === 401) {
                window.location.href = BASE + '/signin.php';
            }
            throw new Error(payload.error || 'Request failed.');
        }

        return payload.data;
    }

    window.PoloNav = {
        base: BASE,
        api: api,
        url: function (path) {
            return BASE + '/' + String(path || '').replace(/^\//, '');
        },
    };
})();
