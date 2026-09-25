(function () {
    const toastEl = document.getElementById('toast');
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');

    function toast(message) {
        if (!toastEl) {
            window.alert(message);
            return;
        }
        toastEl.hidden = false;
        toastEl.textContent = message;
        clearTimeout(toastEl._timer);
        toastEl._timer = setTimeout(function () {
            toastEl.hidden = true;
        }, 3200);
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        modalBody.innerHTML = '';
    }

    function openModal(title, html) {
        modalTitle.textContent = title;
        modalBody.innerHTML = html;
        modal.hidden = false;
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('[data-close-modal]')) {
                closeModal();
            }
        });
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formValues(form) {
        const data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = typeof value === 'string' ? value.trim() : value;
        });
        return data;
    }

    function renderRows(tbody, rows, htmlForRow) {
        if (!rows || !rows.length) {
            tbody.innerHTML = '<tr><td colspan="12" class="empty">Nothing to show yet.</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(htmlForRow).join('');
    }

    function money(value) {
        if (value == null || value === '') return '—';
        return '₱' + Number(value).toFixed(2);
    }

    function statusChip(status) {
        var safeStatus = String(status == null ? '' : status);
        return '<span class="chip status-' + safeStatus + '">' +
            escapeHtml(safeStatus.replace(/_/g, ' ')) + '</span>';
    }

    function reviewButton(id, label) {
        return '<button class="btn-review" data-id="' + escapeHtml(id) + '">' +
            escapeHtml(label || 'Review') + '</button>';
    }

    window.PNUI = {
        toast: toast,
        openModal: openModal,
        closeModal: closeModal,
        escapeHtml: escapeHtml,
        formValues: formValues,
        renderRows: renderRows,
        money: money,
        statusChip: statusChip,
        reviewButton: reviewButton,
    };
})();