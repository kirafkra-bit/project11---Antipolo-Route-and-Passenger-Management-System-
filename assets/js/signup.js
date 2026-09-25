
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signup-form');
    const errorBox = document.getElementById('auth-error');
    if (!form) return;

    const roleInputs = form.querySelectorAll('input[name="role"]');
    const driverAttachments = document.getElementById('driverAttachments');
    const passengerAttachment = document.getElementById('passengerAttachment');
    const submitButton = document.getElementById('signup-submit');
    const documentsModal = document.getElementById('driver-documents-modal');
    const uploadDocumentsButton = document.getElementById('upload-documents');
    const closeDocumentsButton = document.getElementById('close-driver-documents');
    const saveDocumentsButton = document.getElementById('save-driver-documents');

    const license = document.getElementById('license');
    const validId = document.getElementById('valid_id');
    const passengerValidId = document.getElementById('passenger_valid_id');
    const extraDocuments = ['or_cr', 'ctpl', 'mvir', 'emission'].map(function (id) {
        return document.getElementById(id);
    });
    const driverDocuments = [license, validId].concat(extraDocuments);

    function closeDocumentsModal() {
        documentsModal.hidden = true;
    }

    function openDocumentsModal() {
        documentsModal.hidden = false;
        license.focus();
    }

    function allDriverDocumentsUploaded() {
        return driverDocuments.every(function (input) { return input.files[0]; });
    }

    function syncUploadButton() {
        uploadDocumentsButton.textContent = allDriverDocumentsUploaded()
            ? 'Documents Uploaded'
            : 'Upload Documents';
    }

    function syncDriverRequirements() {
        const selectedRole = form.querySelector('input[name="role"]:checked').value;
        form.closest('.signup-page').classList.toggle('is-driver', selectedRole === 'driver');
        if (selectedRole === 'driver') {
            driverAttachments.hidden = false;
            passengerAttachment.hidden = true;
            passengerValidId.required = false;
            license.required = true;
            validId.required = true;
            extraDocuments.forEach(function (input) { input.required = true; });
            submitButton.textContent = 'Submit Driver Application';
        } else {
            driverAttachments.hidden = true;
            passengerAttachment.hidden = false;
            passengerValidId.required = true;
            license.required = false;
            validId.required = false;
            extraDocuments.forEach(function (input) { input.required = false; });
            submitButton.textContent = 'Create passenger account';
            closeDocumentsModal();
        }
        syncUploadButton();
    }

    // Show or hide driver requirements
    form.addEventListener('change', function (event) {
        if (event.target.matches('input[name="role"]')) {
            syncDriverRequirements();

            uploadDocumentsButton.addEventListener('click', openDocumentsModal);
            closeDocumentsButton.addEventListener('click', closeDocumentsModal);
            saveDocumentsButton.addEventListener('click', function () {
                if (!allDriverDocumentsUploaded()) {
                    errorBox.hidden = false;
                    errorBox.textContent = 'Please upload all required driver documents.';
                    return;
                }
                errorBox.hidden = true;
                syncUploadButton();
                closeDocumentsModal();
            });
            documentsModal.addEventListener('click', function (event) {
                if (event.target === documentsModal) {
                    closeDocumentsModal();
                }
            });
            driverDocuments.forEach(function (input) {
                input.addEventListener('change', syncUploadButton);
            });
        }
    });
    syncDriverRequirements();

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        errorBox.hidden = true;

        // Check password confirmation
        if (form.password.value !== form.confirm_password.value) {
            errorBox.hidden = false;
            errorBox.textContent = 'Passwords do not match.';
            return;
        }

        // Create FormData instead of a normal JavaScript object
        const body = new FormData();

        body.append('username', form.username.value.trim());
        body.append('password', form.password.value);
        body.append('name', form.name.value.trim());
        body.append('contact_number', form.contact_number.value.trim());
        body.append('address', form.address.value.trim());
        const selectedRole = form.querySelector('input[name="role"]:checked').value;
        body.append('role', selectedRole);

        // Add driver attachments
        if (selectedRole === 'driver') {
            if (!license.files[0] || !validId.files[0] || extraDocuments.some(function (input) { return !input.files[0]; })) {
                errorBox.hidden = false;
                errorBox.textContent = 'Please upload all required driver documents.';
                return;
            }

            body.append('license', license.files[0]);
            body.append('valid_id', validId.files[0]);
            extraDocuments.forEach(function (input) { body.append(input.name, input.files[0]); });
        } else {
            if (!passengerValidId.files[0]) {
                errorBox.hidden = false;
                errorBox.textContent = 'Please upload a valid ID.';
                return;
            }
            body.append('passenger_valid_id', passengerValidId.files[0]);
        }

        try {
            const data = await PoloNav.api('/api/register.php', {
                method: 'POST',
                body: body
            });

            window.location.href = PoloNav.url(data.dashboard);

        } catch (err) {
            errorBox.hidden = false;
            errorBox.textContent = err.message;
        }
    });
});