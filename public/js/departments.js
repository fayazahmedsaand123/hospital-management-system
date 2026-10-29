document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('departmentsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const canManage = page.dataset.canManage === '1';
    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const modalElement = document.getElementById('departmentModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const form = document.getElementById('departmentForm');
    const saveButton = document.getElementById('departmentSaveBtn');
    const modalTitle = document.getElementById('departmentModalTitle');

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
    filterForm.querySelector('[name="status"]').addEventListener('change', () => loadTable());

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

                editId = editButton.dataset.id;
                form.reset();
                App.clearFormErrors(form);
                form.department_name.value = data.record.department_name ?? '';
                form.description.value = data.record.description ?? '';
                form.status.value = data.record.status ?? 'active';
                modalTitle.textContent = 'Edit Department';
                modal.show();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton) {
            const confirmed = await App.confirm(
                `Delete "${deleteButton.dataset.name}"? Departments that still have doctors cannot be deleted.`,
                { title: 'Delete Department', confirmText: 'Delete' }
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

    document.getElementById('addDepartmentBtn').addEventListener('click', () => {
        editId = null;
        form.reset();
        App.clearFormErrors(form);
        form.status.value = 'active';
        modalTitle.textContent = 'Add Department';
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
