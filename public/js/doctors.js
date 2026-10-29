document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('doctorsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const canManage = page.dataset.canManage === '1';
    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const modalElement = document.getElementById('doctorModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const form = document.getElementById('doctorForm');
    const saveButton = document.getElementById('doctorSaveBtn');
    const modalTitle = document.getElementById('doctorModalTitle');

    let editId = null;

    async function loadTable(url = null) {
        tableArea.classList.add('table-loading');

        try {
            const target = url
                ? new URL(url, window.location.origin)
                : new URL(indexUrl, window.location.origin);

            if (!url) {
                App.searchParams(filterForm).forEach((value, key) => target.searchParams.set(key, value));
            }

            target.searchParams.set('ajax', '1');

            const data = await App.get(target.toString());
            tableArea.innerHTML = data.html;
            totalCount.textContent = data.total;
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            tableArea.classList.remove('table-loading');
        }
    }

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadTable();
    });

    filterForm.querySelector('[name="q"]').addEventListener('input', App.debounce(() => loadTable()));
    filterForm.querySelector('[name="department_id"]').addEventListener('change', () => loadTable());
    filterForm.querySelector('[name="availability_status"]').addEventListener('change', () => loadTable());

    document.getElementById('resetFilters').addEventListener('click', () => {
        filterForm.reset();
        loadTable();
    });

    tableArea.addEventListener('click', async (event) => {
        const paginationLink = event.target.closest('.pagination a');
        if (paginationLink) {
            event.preventDefault();
            loadTable(paginationLink.href);
            return;
        }

        const editButton = event.target.closest('.edit-btn');
        if (editButton) {
            try {
                const data = await App.get(`${indexUrl}/${editButton.dataset.id}`);
                const record = data.record;

                editId = editButton.dataset.id;
                form.reset();
                App.clearFormErrors(form);
                form.doctor_name.value = record.doctor_name ?? '';
                form.department_id.value = record.department_id ?? '';
                form.specialization.value = record.specialization ?? '';
                form.qualification.value = record.qualification ?? '';
                form.phone.value = record.phone ?? '';
                form.email.value = record.email ?? '';
                form.consultation_fee.value = record.consultation_fee ?? '';
                form.availability_status.value = record.availability_status ?? 'available';
                modalTitle.textContent = 'Edit Doctor';
                modal.show();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton) {
            const confirmed = await App.confirm(
                `Delete "${deleteButton.dataset.name}"? Doctors with appointment or prescription history cannot be deleted.`,
                { title: 'Delete Doctor', confirmText: 'Delete' }
            );

            if (!confirmed) {
                return;
            }

            try {
                const data = await App.delete(`${indexUrl}/${deleteButton.dataset.id}`);
                App.toast(data.message);
                loadTable();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
        }
    });

    if (!canManage) {
        return;
    }

    document.getElementById('addDoctorBtn').addEventListener('click', () => {
        editId = null;
        form.reset();
        App.clearFormErrors(form);
        form.availability_status.value = 'available';
        modalTitle.textContent = 'Add Doctor';
        modal.show();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        const payload = Object.fromEntries(new FormData(form).entries());
        App.setLoading(saveButton, true);

        try {
            const data = editId
                ? await App.put(`${indexUrl}/${editId}`, payload)
                : await App.post(indexUrl, payload);

            modal.hide();
            App.toast(data.message);
            loadTable();
        } catch (error) {
            if (error.status === 422 && error.data.errors) {
                App.showFormErrors(form, error.data.errors);
                App.toast(error.data.message || 'Please check the highlighted fields.', 'danger');
            } else {
                App.toast(error.message, 'danger');
            }
        } finally {
            App.setLoading(saveButton, false);
        }
    });
});
