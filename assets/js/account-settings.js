(function () {
    const form = document.getElementById('account-password-form');
    if (!form) return;

    const message = document.getElementById('password-form-message');
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        message.hidden = true;

        const currentPassword = form.elements.current_password.value;
        const newPassword = form.elements.new_password.value;
        const confirmPassword = form.elements.confirm_password.value;

        if (newPassword !== confirmPassword) {
            message.textContent = 'The new passwords do not match.';
            message.hidden = false;
            return;
        }

        try {
            const result = await PoloNav.api('/api/account.php', {
                method: 'POST',
                body: { current_password: currentPassword, new_password: newPassword },
            });
            form.reset();
            message.textContent = result.message || 'Password updated successfully.';
            message.hidden = false;
        } catch (error) {
            message.textContent = error.message;
            message.hidden = false;
        }
    });
})();
