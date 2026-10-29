<div class="print-sheet">
    <div class="print-header d-flex justify-content-between align-items-start">
        <div>
            <p class="print-hospital"><i class="bi bi-hospital me-2"></i>{{ config('app.name') }}</p>
            <p class="print-tagline">Main Road, Latifabad, Hyderabad, Sindh, Pakistan</p>
            <p class="print-tagline">Phone: +92 22 111 222 333 &middot; care@hospital.com</p>
        </div>
        <div class="text-end">
            <div class="print-section-title mb-1">Prescription</div>
            <div class="fw-bold">#RX-{{ str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div class="text-muted small">{{ $prescription->prescription_date->format('d M Y') }}</div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-7">
            <div class="print-section-title">Patient</div>
            <div class="fw-bold fs-6">{{ $prescription->patient->patient_name }}</div>
            <div class="small text-muted">
                {{ $prescription->patient->patient_code }}
                &middot; {{ ucfirst($prescription->patient->gender) }}
                @if ($prescription->patient->age !== null)
                    &middot; {{ $prescription->patient->age }} years
                @endif
                @if ($prescription->patient->blood_group)
                    &middot; Blood group {{ $prescription->patient->blood_group }}
                @endif
            </div>
            <div class="small text-muted">Phone: {{ $prescription->patient->phone ?: '—' }}</div>
            <div class="small text-muted">Address: {{ $prescription->patient->address ?: '—' }}</div>
        </div>
        <div class="col-sm-5">
            <div class="print-section-title">Prescribed by</div>
            <div class="fw-bold fs-6">{{ $prescription->doctor->doctor_name }}</div>
            <div class="small text-muted">{{ $prescription->doctor->specialization }}</div>
            <div class="small text-muted">{{ $prescription->doctor->department?->department_name ?? 'General' }}</div>
            @if ($prescription->doctor->qualification)
                <div class="small text-muted">{{ $prescription->doctor->qualification }}</div>
            @endif
        </div>
    </div>

    <div class="mb-4">
        <div class="print-section-title">Diagnosis</div>
        <div>{{ $prescription->diagnosis }}</div>
    </div>

    <table class="table table-bordered align-middle mb-4">
        <thead class="table-light">
            <tr>
                <th style="width: 45px;">#</th>
                <th>Medicine</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($prescription->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="fw-semibold">{{ $item->medicine_name }}</td>
                    <td>{{ $item->dosage }}</td>
                    <td>{{ $item->frequency }}</td>
                    <td>{{ $item->duration }}</td>
                    <td>{{ $item->instructions ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($prescription->instructions)
        <div class="mb-4">
            <div class="print-section-title">Instructions</div>
            <div>{{ $prescription->instructions }}</div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-end mt-5 pt-4">
        <div class="small text-muted">
            This prescription is valid for 30 days from the date of issue.
        </div>
        <div class="text-center">
            <div style="border-top: 1px solid var(--text); width: 220px;"></div>
            <div class="small text-muted mt-1">Doctor's signature</div>
        </div>
    </div>

    <div class="text-center small text-muted mt-4 pt-3" style="border-top: 1px dashed var(--border);">
        Generated on {{ now()->format('d M Y, h:i A') }} &middot; {{ config('app.name') }}
    </div>
</div>
