<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Bill #</th>
                <th>Bill Date</th>
                <th>Patient</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bills as $bill)
                <tr>
                    <td>
                        <a href="{{ route('bills.show', $bill) }}" class="fw-semibold text-decoration-none">
                            {{ $bill->bill_number }}
                        </a>
                    </td>
                    <td>{{ $bill->bill_date->format('d M Y') }}</td>
                    <td>
                        {{ $bill->patient->patient_name }}
                        <div class="text-muted small">{{ $bill->patient->patient_code }}</div>
                    </td>
                    <td class="text-end">Rs {{ number_format($bill->total_amount, 2) }}</td>
                    <td class="text-end fw-semibold text-success">Rs {{ number_format($bill->paid_amount, 2) }}</td>
                    <td class="text-end {{ (float) $bill->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                        Rs {{ number_format($bill->balance_amount, 2) }}
                    </td>
                    <td><span class="badge {{ $bill->payment_status_badge_class }}">{{ $bill->payment_status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="bi bi-cash-coin"></i>
                            No payments recorded in this period.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $bills])
