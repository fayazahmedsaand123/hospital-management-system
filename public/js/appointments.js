document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('appointmentsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const doctorsUrl = page.dataset.doctorsUrl;
    const slotsUrl = page.dataset.slotsUrl;
    const canManage = page.dataset.canManage === '1';
    const canDelete = page.dataset.canDelete === '1';

    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const modalElement = document.getElementById('appointmentModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const form = document.getElementById('appointmentForm');
    const saveButton = document.getElementById('appointmentSaveBtn');
    const modalTitle = document.getElementById('appointmentModalTitle');

    const departmentSelect = document.getElementById('modalDepartment');
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const timeSelect = document.getElementById('appointment_time');
    const doctorInfo = document.getElementById('doctorInfo');

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

        if (!canManage) {
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

                form.patient_id.value = record.patient_id;
                form.status.value = record.status;
                form.reason.value = record.reason ?? '';
                form.notes.value = record.notes ?? '';
                departmentSelect.value = record.doctor?.department_id ?? '';
                dateInput.value = (record.appointment_date ?? '').substring(0, 10);

                await loadDoctors(departmentSelect.value, record.doctor_id);
                await loadSlots(record.doctor_id, dateInput.value, editId);
                timeSelect.value = (record.appointment_time ?? '').substring(0, 5);

                modalTitle.textContent = 'Edit Appointment';
                modal.show();
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton && canDelete) {
            const confirmed = await App.confirm(
                `Delete the appointment for "${deleteButton.dataset.name}"? Appointments with prescriptions or bills cannot be deleted.`,
                { title: 'Delete Appointment', confirmText: 'Delete' }
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

    // Inline status change.
    tableArea.addEventListener('change', async (event) => {
        const select = event.target.closest('.status-select');
        if (!select) {
            return;
        }

        try {
            const data = await App.patch(`${indexUrl}/${select.dataset.id}/status`, { status: select.value });
            App.toast(data.message);
            loadTable();
        } catch (error) {
            App.toast(error.message, 'danger');
            loadTable();
        }
    });

    if (!canManage) {
        return;
    }

    async function loadDoctors(departmentId, selectedId = null) {
        doctorSelect.innerHTML = '<option value="">Loading…</option>';

        if (!departmentId) {
            doctorSelect.innerHTML = '<option value="">Select department first</option>';
            doctorInfo.textContent = '';
            return;
        }

        try {
            const data = await App.get(`${doctorsUrl}?department_id=${encodeURIComponent(departmentId)}`);
            doctorSelect.innerHTML = '<option value="">Select doctor</option>';

            data.doctors.forEach((doctor) => {
                const option = document.createElement('option');
                option.value = doctor.id;
                option.textContent = `${doctor.name} — ${doctor.specialization}` + (doctor.available ? '' : ' (unavailable)');
                option.dataset.fee = doctor.fee;

                if (!doctor.available && String(doctor.id) !== String(selectedId)) {
                    option.disabled = true;
                }

                if (selectedId && String(doctor.id) === String(selectedId)) {
                    option.selected = true;
                }

                doctorSelect.appendChild(option);
            });

            updateDoctorInfo();
        } catch (error) {
            doctorSelect.innerHTML = '<option value="">Could not load doctors</option>';
            App.toast(error.message, 'danger');
        }
    }

    function updateDoctorInfo() {
        const option = doctorSelect.options[doctorSelect.selectedIndex];
        const fee = option?.dataset?.fee;

        doctorInfo.textContent = fee
            ? `Consultation fee: Rs ${App.formatMoney(fee)}`
            : '';
    }

    async function loadSlots(doctorId, date, excludeId = null) {
        if (!doctorId || !date) {
            timeSelect.innerHTML = '<option value="">Select doctor and date first</option>';
            return;
        }

        timeSelect.innerHTML = '<option value="">Loading slots…</option>';

        try {
            const params = new URLSearchParams({ doctor_id: doctorId, date });
            if (excludeId) {
                params.set('exclude', excludeId);
            }

            const data = await App.get(`${slotsUrl}?${params.toString()}`);
            timeSelect.innerHTML = '<option value="">Select time</option>';

            if (!data.slots.length) {
                timeSelect.innerHTML = '<option value="">No slots available</option>';
                return;
            }

            data.slots.forEach((slot) => {
                const option = document.createElement('option');
                option.value = slot.value;
                option.textContent = slot.label;
                timeSelect.appendChild(option);
            });
        } catch (error) {
            timeSelect.innerHTML = '<option value="">Could not load slots</option>';
            App.toast(error.message, 'danger');
        }
    }

    departmentSelect.addEventListener('change', () => {
        loadDoctors(departmentSelect.value);
        timeSelect.innerHTML = '<option value="">Select doctor and date first</option>';
    });

    doctorSelect.addEventListener('change', () => {
        updateDoctorInfo();
        loadSlots(doctorSelect.value, dateInput.value, editId);
    });

    dateInput.addEventListener('change', () => {
        loadSlots(doctorSelect.value, dateInput.value, editId);
    });

    document.getElementById('addAppointmentBtn').addEventListener('click', () => {
        editId = null;
        form.reset();
        App.clearFormErrors(form);
        form.status.value = 'Scheduled';
        dateInput.value = new Date().toISOString().slice(0, 10);
        departmentSelect.value = '';
        doctorSelect.innerHTML = '<option value="">Select department first</option>';
        timeSelect.innerHTML = '<option value="">Select doctor and date first</option>';
        doctorInfo.textContent = '';
        modalTitle.textContent = 'Book Appointment';
        modal.show();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        const payload = {
            patient_id: form.patient_id.value,
            doctor_id: form.doctor_id.value,
            appointment_date: form.appointment_date.value,
            appointment_time: form.appointment_time.value,
            status: form.status.value,
            reason: form.reason.value || null,
            notes: form.notes.value || null,
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
                App.toast(error.data.message || 'Please check the highlighted fields.', 'danger');
            } else {
                App.toast(error.message, 'danger');
            }
        } finally {
            App.setLoading(saveButton, false);
        }
    });
});
