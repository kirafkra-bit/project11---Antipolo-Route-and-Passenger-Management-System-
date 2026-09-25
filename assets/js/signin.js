document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signin-form');
    const errorBox = document.getElementById('auth-error');
    if (!form) return;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        errorBox.hidden = true;
        const body = {
            username: form.username.value.trim(),
            password: form.password.value,
        };
        try {
            const data = await PoloNav.api('/api/login.php', { method: 'POST', body: body });
            window.location.href = PoloNav.url(data.dashboard);
        } catch (err) {
            errorBox.hidden = false;
            errorBox.textContent = err.message;
        }
    });
});
