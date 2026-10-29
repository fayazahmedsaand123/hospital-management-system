<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Bill #</th>
                <th>Date</th>
                <th>Patient</th>
                <th>Items</th>
                <th class="text-end">Total</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Balance</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bills as $bill)
                <tr>
                    <td class="fw-semibold">{{ $bill->bill_number }}</td>
                    <td>{{ $bill->bill_date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('patients.show', $bill->patient) }}" class="fw-semibold text-decoration-none">
                            {{ $bill->patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $bill->patient->patient_code }}</div>
                    </td>
                    <td><span class="badge badge-soft-primary">{{ $bill->items_count }}</span></td>
                    <td class="text-end fw-semibold">Rs {{ number_format($bill->total_amount, 2) }}</td>
                    <td class="text-end">Rs {{ number_format($bill->paid_amount, 2) }}</td>
                    <td class="text-end {{ (float) $bill->balance_amount > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                        Rs {{ number_format($bill->balance_amount, 2) }}
                    </td>
                    <td><span class="badge {{ $bill->payment_status_badge_class }}">{{ $bill->payment_status }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('bills.show', $bill) }}" class="btn btn-sm btn-soft" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('bills.print', $bill) }}" target="_blank" class="btn btn-sm btn-light border" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        @if ((float) $bill->balance_amount > 0)
                            <button type="button" class="btn btn-sm btn-light border text-teal pay-btn"
                                    title="Record payment"
                                    data-id="{{ $bill->id }}"
                                    data-number="{{ $bill->bill_number }}"
                                    data-patient="{{ $bill->patient->patient_name }}"
                                    data-total="{{ number_format($bill->total_amount, 2, '.', '') }}"
                                    data-paid="{{ number_format($bill->paid_amount, 2, '.', '') }}"
                                    data-balance="{{ number_format($bill->balance_amount, 2, '.', '') }}">
                                <i class="bi bi-cash-coin"></i>
                            </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-light border edit-btn" data-id="{{ $bill->id }}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if (auth()->user()->isAdmin() && (float) $bill->paid_amount <= 0)
                            <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                    data-id="{{ $bill->id }}" data-name="{{ $bill->bill_number }}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="bi bi-receipt-cutoff"></i>
                            No bills found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $bills])
