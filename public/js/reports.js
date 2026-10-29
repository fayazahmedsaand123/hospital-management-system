document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('reportPage');
    if (!page) {
        return;
    }

    const reportUrl = page.dataset.reportUrl;
    const form = document.getElementById('reportFilterForm');
    const tableArea = document.getElementById('reportTableArea');

    async function load(url = null) {
        tableArea.classList.add('table-loading');

        try {
            const target = url
                ? new URL(url, window.location.origin)
                : new URL(reportUrl, window.location.origin);

            if (!url && form) {
                App.searchParams(form).forEach((value, key) => target.searchParams.set(key, value));
            }

            target.searchParams.set('ajax', '1');

            const data = await App.get(target.toString());
            tableArea.innerHTML = data.html;
            updateSummary(data.summary);

            const totalEl = document.getElementById('reportTotal');
            if (totalEl && data.total !== undefined) {
                totalEl.textContent = data.total;
            }
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

        Object.entries(summary).forEach(([key, value]) => {
            const element = document.querySelector(`[data-summary-key="${key}"]`);
            if (!element) {
                return;
            }

            element.textContent = element.dataset.money === '1'
                ? 'Rs ' + App.formatMoney(value)
                : value;
        });
    }

    function formatDate(value) {
        if (!value) {
            return '—';
        }

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(new Date(value + 'T00:00:00'));
    }

    function updatePrintPeriod() {
        const period = document.getElementById('printPeriod');
        if (!period || !form) {
            return;
        }

        const from = form.querySelector('[name="date_from"]')?.value;
        const to = form.querySelector('[name="date_to"]')?.value;

        period.textContent = `Period: ${formatDate(from)} to ${formatDate(to)}`;
    }

    updatePrintPeriod();

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        load();
    });

    form?.querySelector('[name="q"]')?.addEventListener('input', App.debounce(() => load()));
    form?.querySelectorAll('select, input[type="date"]').forEach((element) => {
        element.addEventListener('change', () => {
            updatePrintPeriod();
            load();
        });
    });

    document.getElementById('resetFilters')?.addEventListener('click', () => {
        form.reset();
        updatePrintPeriod();
        load();
    });

    tableArea.addEventListener('click', (event) => {
        const paginationLink = event.target.closest('.pagination a');
        if (paginationLink) {
            event.preventDefault();
            load(paginationLink.href);
        }
    });
});
