<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Diagnosis</th>
                <th>Items</th>
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
                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($prescription->diagnosis, 60) }}</td>
                    <td><span class="badge badge-soft-primary">{{ $prescription->items_count }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-sm btn-soft" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('prescriptions.print', $prescription) }}" target="_blank"
                           class="btn btn-sm btn-light border" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-light border edit-btn"
                                data-id="{{ $prescription->id }}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if (auth()->user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                    data-id="{{ $prescription->id }}"
                                    data-name="{{ $prescription->patient->patient_name }}"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-clipboard2-pulse"></i>
                            No prescriptions found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $prescriptions])
