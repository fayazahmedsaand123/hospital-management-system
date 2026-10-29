document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('billsPage');
    if (!page) {
        return;
    }

    const indexUrl = page.dataset.indexUrl;
    const appointmentsUrl = page.dataset.appointmentsUrl;
    const canDelete = page.dataset.canDelete === '1';

    const tableArea = document.getElementById('tableArea');
    const filterForm = document.getElementById('filterForm');
    const totalCount = document.getElementById('totalCount');

    const billModal = new bootstrap.Modal(document.getElementById('billModal'));
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));

    const billForm = document.getElementById('billForm');
    const billSaveBtn = document.getElementById('billSaveBtn');
    const billModalTitle = document.getElementById('billModalTitle');
    const itemsBody = document.getElementById('itemsBody');
    const patientSelect = document.getElementById('bill_patient');
    const appointmentSelect = document.getElementById('bill_appointment');
    const billDateInput = document.getElementById('bill_date');
    const discountInput = document.getElementById('discount');
    const paidInput = document.getElementById('paid_amount');
    const paidWrapper = document.getElementById('paidAmountLabel').closest('.col-md-2');
    const subtotalText = document.getElementById('subtotalText');
    const totalText = document.getElementById('totalText');

    const paymentForm = document.getElementById('paymentForm');
    const paymentSaveBtn = document.getElementById('paymentSaveBtn');
    const paymentAmountInput = document.getElementById('payment_amount');

    let editId = null;
    let paymentBillId = null;

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
            updateSummary(data.summary);
        } catch (error) {
            App.toast(error.message, 'danger');
        } finally {
            tableArea.classList.remove('table-loading');
        }
    }

    function updateSummary(summary) {
        if (!summary) {
            return;
        }

        document.getElementById('summaryTotal').textContent = summary.total;
        document.getElementById('summaryUnpaid').textContent = summary.unpaid;
        document.getElementById('summaryOutstanding').textContent = 'Rs ' + App.formatMoney(summary.outstanding);
        document.getElementById('summaryCollected').textContent = 'Rs ' + App.formatMoney(summary.collected);
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

        const payButton = event.target.closest('.pay-btn');
        if (payButton) {
            openPaymentModal(payButton);
            return;
        }

        const editButton = event.target.closest('.edit-btn');
        if (editButton) {
            try {
                const data = await App.get(`${indexUrl}/${editButton.dataset.id}`);
                openEditModal(data.record);
            } catch (error) {
                App.toast(error.message, 'danger');
            }
            return;
        }

        const deleteButton = event.target.closest('.delete-btn');
        if (deleteButton && canDelete) {
            const confirmed = await App.confirm(
                `Delete bill ${deleteButton.dataset.name}? Only bills with no payments can be deleted.`,
                { title: 'Delete Bill', confirmText: 'Delete' }
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

    // ---------------------------------------------------------------
    // Create / edit
    // ---------------------------------------------------------------
    document.getElementById('addBillBtn').addEventListener('click', () => {
        editId = null;
        billForm.reset();
        App.clearFormErrors(billForm);
        itemsBody.innerHTML = '';
        addItemRow();
        billDateInput.value = new Date().toISOString().slice(0, 10);
        discountInput.value = '0';
        paidInput.value = '0';
        paidInput.disabled = false;
        paidWrapper.classList.remove('d-none');
        appointmentSelect.innerHTML = '<option value="">No linked appointment</option>';
        billModalTitle.textContent = 'New Bill';
        recalculateTotals();
        billModal.show();
    });

    function openEditModal(record) {
        editId = record.id;
        billForm.reset();
        App.clearFormErrors(billForm);
        itemsBody.innerHTML = '';

        patientSelect.value = record.patient_id;
        billDateInput.value = (record.bill_date ?? '').substring(0, 10);
        discountInput.value = Number(record.discount ?? 0);
        paidInput.value = Number(record.paid_amount ?? 0);
        paidInput.disabled = true;
        paidWrapper.classList.add('d-none');

        (record.items || []).forEach(addItemRow);

        if (!record.items || !record.items.length) {
            addItemRow();
        }

        loadAppointments(record.patient_id, record.appointment_id, record.appointment);

        billModalTitle.textContent = `Edit Bill ${record.bill_number}`;
        recalculateTotals();
        billModal.show();
    }

    function addItemRow(item = {}) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" data-field="item_description"
                       value="${App.escapeHtml(item.item_description ?? '')}" placeholder="e.g. Consultation fee"></td>
            <td><input type="number" class="form-control form-control-sm text-center" data-field="quantity"
                       min="1" step="1" value="${Number(item.quantity ?? 1)}"></td>
            <td><input type="number" class="form-control form-control-sm text-end" data-field="unit_price"
                       min="0" step="0.01" value="${Number(item.unit_price ?? 0).toFixed(2)}"></td>
            <td class="text-end fw-semibold line-total">Rs 0.00</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-light border text-danger remove-item" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        itemsBody.appendChild(row);
        updateRowTotal(row);
    }

    function updateRowTotal(row) {
        const quantity = Math.max(0, Number(row.querySelector('[data-field="quantity"]').value) || 0);
        const price = Math.max(0, Number(row.querySelector('[data-field="unit_price"]').value) || 0);
        const lineTotal = quantity * price;

        row.querySelector('.line-total').textContent = 'Rs ' + App.formatMoney(lineTotal);

        return lineTotal;
    }

    function recalculateTotals() {
        let subtotal = 0;

        itemsBody.querySelectorAll('tr').forEach((row) => {
            subtotal += updateRowTotal(row);
        });

        const discount = Math.max(0, Number(discountInput.value) || 0);
        const total = Math.max(0, subtotal - discount);

        subtotalText.textContent = 'Rs ' + App.formatMoney(subtotal);
        totalText.textContent = 'Rs ' + App.formatMoney(total);

        return { subtotal, discount, total };
    }

    itemsBody.addEventListener('input', (event) => {
        if (event.target.matches('[data-field="quantity"], [data-field="unit_price"]')) {
            recalculateTotals();
        }
    });

    discountInput.addEventListener('input', recalculateTotals);

    itemsBody.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-item');
        if (!removeButton) {
            return;
        }

        if (itemsBody.querySelectorAll('tr').length === 1) {
            App.toast('A bill needs at least one charge line.', 'warning');
            return;
        }

        removeButton.closest('tr').remove();
        recalculateTotals();
    });

    document.getElementById('addItemBtn').addEventListener('click', () => addItemRow());

    patientSelect.addEventListener('change', () => loadAppointments(patientSelect.value));

    async function loadAppointments(patientId, selectedId = null, selectedRecord = null) {
        appointmentSelect.innerHTML = '<option value="">No linked appointment</option>';

        if (!patientId) {
            return;
        }

        try {
            const data = await App.get(`${appointmentsUrl}?patient_id=${encodeURIComponent(patientId)}`);
            let found = false;

            data.appointments.forEach((appointment) => {
                const option = document.createElement('option');
                option.value = appointment.id;
                option.textContent = `${appointment.label} — ${appointment.status}`;

                if (selectedId && String(appointment.id) === String(selectedId)) {
                    option.selected = true;
                    found = true;
                }

                appointmentSelect.appendChild(option);
            });

            if (selectedId && !found && selectedRecord) {
                const option = document.createElement('option');
                option.value = selectedRecord.id;
                option.textContent = `${(selectedRecord.appointment_date ?? '').substring(0, 10)} ${(selectedRecord.appointment_time ?? '').substring(0, 5)} — ${selectedRecord.doctor?.doctor_name ?? 'Appointment'}`;
                option.selected = true;
                appointmentSelect.appendChild(option);
            }
        } catch (error) {
            App.toast(error.message, 'danger');
        }
    }

    function collectItems() {
        return Array.from(itemsBody.querySelectorAll('tr')).map((row) => {
            const value = (field) => row.querySelector(`[data-field="${field}"]`);

            const descriptionInput = value('item_description');
            const quantityInput = value('quantity');
            const priceInput = value('unit_price');

            const item = {
                item_description: descriptionInput.value.trim(),
                quantity: Number(quantityInput.value),
                unit_price: Number(priceInput.value),
            };

            descriptionInput.classList.toggle('is-invalid', !item.item_description);

            const invalidQuantity = !Number.isInteger(item.quantity) || item.quantity < 1;
            const invalidPrice = Number.isNaN(item.unit_price) || item.unit_price < 0;

            quantityInput.classList.toggle('is-invalid', invalidQuantity);
            priceInput.classList.toggle('is-invalid', invalidPrice);

            return item;
        });
    }

    billForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(billForm);

        if (!patientSelect.value) {
            App.toast('Please select a patient.', 'danger');
            return;
        }

        if (!billDateInput.value) {
            App.toast('Please choose the bill date.', 'danger');
            return;
        }

        const items = collectItems();
        const hasEmptyLine = items.some((item) => !item.item_description || !Number.isInteger(item.quantity)
            || item.quantity < 1 || Number.isNaN(item.unit_price) || item.unit_price < 0);

        if (!items.length || hasEmptyLine) {
            App.toast('Please complete every charge line. Description, quantity and unit price are required.', 'danger');
            return;
        }

        const { subtotal, discount, total } = recalculateTotals();

        if (discount > subtotal) {
            App.toast('The discount cannot be greater than the subtotal.', 'danger');
            return;
        }

        const payload = {
            patient_id: patientSelect.value,
            appointment_id: appointmentSelect.value || null,
            bill_date: billDateInput.value,
            discount,
            items,
        };

        if (!editId) {
            const paid = Number(paidInput.value) || 0;

            if (paid > total) {
                App.toast('The paid amount cannot be greater than the bill total.', 'danger');
                return;
            }

            payload.paid_amount = paid;
        }

        App.setLoading(billSaveBtn, true);

        try {
            const data = editId
                ? await App.put(`${indexUrl}/${editId}`, payload)
                : await App.post(indexUrl, payload);

            billModal.hide();
            App.toast(data.message);
            loadTable();
        } catch (error) {
            if (error.status === 422 && error.data.errors) {
                App.showFormErrors(billForm, error.data.errors);
                App.toast(error.data.message || 'Please check the bill details.', 'danger');
            } else {
                App.toast(error.message, 'danger');
            }
        } finally {
            App.setLoading(billSaveBtn, false);
        }
    });

    // ---------------------------------------------------------------
    // Payments
    // ---------------------------------------------------------------
    function openPaymentModal(button) {
        paymentBillId = button.dataset.id;

        document.getElementById('paymentBillNumber').textContent = button.dataset.number;
        document.getElementById('paymentPatientName').textContent = button.dataset.patient;
        document.getElementById('paymentTotal').textContent = 'Rs ' + App.formatMoney(button.dataset.total);
        document.getElementById('paymentPaid').textContent = 'Rs ' + App.formatMoney(button.dataset.paid);
        document.getElementById('paymentBalance').textContent = 'Rs ' + App.formatMoney(button.dataset.balance);

        App.clearFormErrors(paymentForm);
        paymentAmountInput.value = Number(button.dataset.balance).toFixed(2);
        paymentAmountInput.max = button.dataset.balance;

        paymentModal.show();
    }

    paymentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(paymentForm);

        const amount = Number(paymentAmountInput.value);

        if (!amount || amount <= 0) {
            App.toast('Enter a payment amount greater than zero.', 'danger');
            return;
        }

        App.setLoading(paymentSaveBtn, true, 'Recording...');

        try {
            const data = await App.post(`${indexUrl}/${paymentBillId}/payments`, { amount });
            paymentModal.hide();
            App.toast(data.message);
            loadTable();
        } catch (error) {
            if (error.status === 422 && error.data.errors) {
                App.showFormErrors(paymentForm, error.data.errors);
                App.toast(error.data.message || 'The payment could not be recorded.', 'danger');
            } else {
                App.toast(error.message, 'danger');
            }
        } finally {
            App.setLoading(paymentSaveBtn, false);
        }
    });
});
