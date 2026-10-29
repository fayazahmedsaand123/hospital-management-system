document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('prescriptionsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const appointmentsUrl = page.dataset.appointmentsUrl;
    const isDoctor = page.dataset.isDoctor === '1';
    const doctorId = page.dataset.doctorId;
    const canDelete = page.dataset.canDelete === '1';

    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const modal = new bootstrap.Modal(document.getElementById('prescriptionModal'));
    const form = document.getElementById('prescriptionForm');
    const saveButton = document.getElementById('prescriptionSaveBtn');
    const modalTitle = document.getElementById('prescriptionModalTitle');
    const itemsBody = document.getElementById('itemsBody');
    const patientSelect = document.getElementById('prescription_patient');
    const doctorSelect = document.getElementById('prescription_doctor');
    const appointmentSelect = document.getElementById('prescription_appointment');
    const dateInput = document.getElementById('prescription_date');

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
    filterForm.querySelectorAll('select, input[type="date"]').forEach((element) => {
        element.addEventListener('change', () => loadTable());
    });

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
                itemsBody.innerHTML = '';

                patientSelect.value = record.patient_id;
                if (doctorSelect) {
                    doctorSelect.value = record.doctor_id;
                }
                dateInput.value = (record.prescription_date ?? '').substring(0, 10);
                form.diagnosis.value = record.diagnosis ?? '';
                form.instructions.value = record.instructions ?? '';

                await loadAppointments(record.patient_id, record.appointment_id);
                (record.items || []).forEach(addItemRow);

                if (!record.items || !record.items.length) {
                    addItemRow();
                }

                modalTitle.textContent = 'Edit Prescription';
                modal.show();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton && canDelete) {
            const confirmed = await App.confirm(
                `Delete the prescription for "${deleteButton.dataset.name}"? This is a permanent medical record deletion.`,
                { title: 'Delete Prescription', confirmText: 'Delete' }
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

    function addItemRow(item = {}) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" data-field="medicine_name"
                       value="${App.escapeHtml(item.medicine_name ?? '')}" placeholder="e.g. Panadol"></td>
            <td><input type="text" class="form-control form-control-sm" data-field="dosage"
                       value="${App.escapeHtml(item.dosage ?? '')}" placeholder="500 mg"></td>
            <td><input type="text" class="form-control form-control-sm" data-field="frequency"
                       value="${App.escapeHtml(item.frequency ?? '')}" placeholder="Twice a day"></td>
            <td><input type="text" class="form-control form-control-sm" data-field="duration"
                       value="${App.escapeHtml(item.duration ?? '')}" placeholder="5 days"></td>
            <td><input type="text" class="form-control form-control-sm" data-field="instructions"
                       value="${App.escapeHtml(item.instructions ?? '')}" placeholder="After meals"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light border text-danger remove-item" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        itemsBody.appendChild(row);
    }

    itemsBody.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-item');
        if (!removeButton) {
            return;
        }

        if (itemsBody.querySelectorAll('tr').length === 1) {
            App.toast('A prescription needs at least one medicine.', 'warning');
            return;
        }

        removeButton.closest('tr').remove();
    });

    document.getElementById('addItemBtn').addEventListener('click', () => addItemRow());

    async function loadAppointments(patientId, selectedId = null) {
        if (!patientId) {
            appointmentSelect.innerHTML = '<option value="">Select patient first</option>';
            return;
        }

        appointmentSelect.innerHTML = '<option value="">Loading…</option>';

        try {
            const params = new URLSearchParams({ patient_id: patientId });
            const activeDoctorId = isDoctor ? doctorId : (doctorSelect?.value || '');

            if (activeDoctorId) {
                params.set('doctor_id', activeDoctorId);
            }

            const data = await App.get(`${appointmentsUrl}?${params.toString()}`);
            appointmentSelect.innerHTML = '<option value="">No linked appointment</option>';

            data.appointments.forEach((appointment) => {
                const option = document.createElement('option');
                option.value = appointment.id;
                option.textContent = `${appointment.label} — ${appointment.status}`;
                if (selectedId && String(appointment.id) === String(selectedId)) {
                    option.selected = true;
                }
                appointmentSelect.appendChild(option);
            });
        } catch (error) {
            appointmentSelect.innerHTML = '<option value="">Could not load appointments</option>';
            App.toast(error.message, 'danger');
        }
    }

    patientSelect.addEventListener('change', () => loadAppointments(patientSelect.value));
    doctorSelect?.addEventListener('change', () => loadAppointments(patientSelect.value));

    document.getElementById('addPrescriptionBtn').addEventListener('click', () => {
        editId = null;
        form.reset();
        App.clearFormErrors(form);
        itemsBody.innerHTML = '';
        addItemRow();
        dateInput.value = new Date().toISOString().slice(0, 10);
        appointmentSelect.innerHTML = '<option value="">Select patient first</option>';
        modalTitle.textContent = 'New Prescription';
        modal.show();
    });

    function collectItems() {
        const rows = Array.from(itemsBody.querySelectorAll('tr'));

        return rows.map((row) => {
            const value = (field) => row.querySelector(`[data-field="${field}"]`).value.trim();

            const item = {
                medicine_name: value('medicine_name'),
                dosage: value('dosage'),
                frequency: value('frequency'),
                duration: value('duration'),
                instructions: value('instructions') || null,
            };

            const incomplete = !item.medicine_name || !item.dosage || !item.frequency || !item.duration;

            row.querySelectorAll('[data-field]').forEach((input) => {
                const required = ['medicine_name', 'dosage', 'frequency', 'duration'].includes(input.dataset.field);
                input.classList.toggle('is-invalid', required && !input.value.trim());
            });

            return item;
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        if (!patientSelect.value) {
            App.toast('Please select a patient.', 'danger');
            return;
        }

        if (!isDoctor && !doctorSelect.value) {
            App.toast('Please select a doctor.', 'danger');
            return;
        }

        if (!dateInput.value) {
            App.toast('Please choose the prescription date.', 'danger');
            return;
        }

        const items = collectItems();
        const hasEmptyItem = items.some((item) => !item.medicine_name || !item.dosage || !item.frequency || !item.duration);

        if (!items.length || hasEmptyItem) {
            App.toast('Please complete the medicine details. Medicine, dosage, frequency and duration are required.', 'danger');
            return;
        }

        const payload = {
            patient_id: patientSelect.value,
            doctor_id: isDoctor ? null : doctorSelect.value,
            appointment_id: appointmentSelect.value || null,
            prescription_date: dateInput.value,
            diagnosis: form.diagnosis.value.trim(),
            instructions: form.instructions.value.trim() || null,
            items,
        };

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
                App.toast(error.data.message || 'Please check the doctor, appointment and medicine details.', 'danger');
            } else {
                App.toast(error.message, 'danger');
            }
        } finally {
            App.setLoading(saveButton, false);
        }
    });
});
