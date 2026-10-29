<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Diagnosis</th>
                <th class="text-center">Medicines</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($prescriptions as $prescription)
                <tr>
                    <td class="fw-semibold">{{ $prescription->prescription_date->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('patients.show', $prescription->patient) }}" class="fw-semibold text-decoration-none">
                            {{ $prescription->patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $prescription->patient->patient_code }}</div>
                    </td>
                    <td>{{ $prescription->doctor->doctor_name }}</td>
                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($prescription->diagnosis, 70) }}</td>
                    <td class="text-center"><span class="badge badge-soft-primary">{{ $prescription->items_count }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-sm btn-soft" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-file-medical"></i>
                            No prescriptions match the selected filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $prescriptions])
