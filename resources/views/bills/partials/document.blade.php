<div class="print-sheet">
    <div class="print-header d-flex justify-content-between align-items-start">
        <div>
            <p class="print-hospital"><i class="bi bi-hospital me-2"></i>{{ config('app.name') }}</p>
            <p class="print-tagline">Main Road, Latifabad, Hyderabad, Sindh, Pakistan</p>
            <p class="print-tagline">Phone: +92 22 111 222 333 &middot; billing@hospital.com</p>
        </div>
        <div class="text-end">
            <div class="print-section-title mb-1">Invoice</div>
            <div class="fw-bold">{{ $bill->bill_number }}</div>
            <div class="text-muted small">Date: {{ $bill->bill_date->format('d M Y') }}</div>
            <span class="badge {{ $bill->payment_status_badge_class }} mt-2">{{ $bill->payment_status }}</span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-7">
            <div class="print-section-title">Billed to</div>
            <div class="fw-bold fs-6">{{ $bill->patient->patient_name }}</div>
            <div class="small text-muted">
                {{ $bill->patient->patient_code }}
                &middot; {{ ucfirst($bill->patient->gender) }}
                @if ($bill->patient->age !== null)
                    &middot; {{ $bill->patient->age }} years
                @endif
            </div>
            <div class="small text-muted">Phone: {{ $bill->patient->phone ?: '—' }}</div>
            <div class="small text-muted">Address: {{ $bill->patient->address ?: '—' }}</div>
        </div>
        <div class="col-sm-5">
            <div class="print-section-title">Appointment</div>
            @if ($bill->appointment)
                <div class="fw-semibold">{{ $bill->appointment->appointment_date->format('d M Y') }} at {{ $bill->appointment->formatted_time }}</div>
                <div class="small text-muted">{{ $bill->appointment->doctor?->doctor_name ?? 'Doctor removed' }}</div>
                <div class="small text-muted">{{ $bill->appointment->reason ?: 'General consultation' }}</div>
            @else
                <div class="text-muted small">Walk-in / no linked appointment</div>
            @endif
        </div>
    </div>

    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th style="width: 45px;">#</th>
                <th>Description</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th class="text-end" style="width: 130px;">Unit price</th>
                <th class="text-end" style="width: 140px;">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bill->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->item_description }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">Rs {{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-end fw-semibold">Rs {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row justify-content-end">
        <div class="col-sm-6 col-md-5">
            <div class="summary-line">
                <span>Subtotal</span>
                <strong>Rs {{ number_format($bill->subtotal, 2) }}</strong>
            </div>
            <div class="summary-line">
                <span>Discount</span>
                <strong>Rs {{ number_format($bill->discount, 2) }}</strong>
            </div>
            <div class="summary-line">
                <span>Total</span>
                <strong>Rs {{ number_format($bill->total_amount, 2) }}</strong>
            </div>
            <div class="summary-line">
                <span>Paid</span>
                <strong>Rs {{ number_format($bill->paid_amount, 2) }}</strong>
            </div>
            <div class="summary-line">
                <span>Balance</span>
                <strong class="{{ (float) $bill->balance_amount > 0 ? 'text-danger' : 'text-success' }}">
                    Rs {{ number_format($bill->balance_amount, 2) }}
                </strong>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end mt-5 pt-4">
        <div class="small text-muted" style="max-width: 340px;">
            Please keep this invoice for your records. Payments are non-refundable unless approved by the hospital administration.
        </div>
        <div class="text-center">
            <div style="border-top: 1px solid var(--text); width: 220px;"></div>
            <div class="small text-muted mt-1">Received by</div>
        </div>
    </div>

    <div class="text-center small text-muted mt-4 pt-3" style="border-top: 1px dashed var(--border);">
        Generated on {{ now()->format('d M Y, h:i A') }} &middot; {{ config('app.name') }}
    </div>
</div>
