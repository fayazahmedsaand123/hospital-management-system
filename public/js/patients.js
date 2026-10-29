document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('patientsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const canDelete = page.dataset.canDelete === '1';
    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const modal = new bootstrap.Modal(document.getElementById('patientModal'));
    const form = document.getElementById('patientForm');
    const saveButton = document.getElementById('patientSaveBtn');
    const modalTitle = document.getElementById('patientModalTitle');

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
    filterForm.querySelector('[name="gender"]').addEventListener('change', () => loadTable());

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
                form.patient_name.value = record.patient_name ?? '';
                form.gender.value = record.gender ?? '';
                form.date_of_birth.value = (record.date_of_birth ?? '').substring(0, 10);
                form.phone.value = record.phone ?? '';
                form.email.value = record.email ?? '';
                form.blood_group.value = record.blood_group ?? '';
                form.emergency_contact.value = record.emergency_contact ?? '';
                form.address.value = record.address ?? '';
                modalTitle.textContent = 'Edit Patient';
                modal.show();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton && canDelete) {
            const confirmed = await App.confirm(
                `Delete "${deleteButton.dataset.name}"? Patients with medical or billing history cannot be deleted.`,
                { title: 'Delete Patient', confirmText: 'Delete' }
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

    document.getElementById('addPatientBtn').addEventListener('click', () => {
        editId = null;
        form.reset();
        App.clearFormErrors(form);
        modalTitle.textContent = 'Register Patient';
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
