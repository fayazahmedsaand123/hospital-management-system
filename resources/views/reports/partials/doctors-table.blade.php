<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Department</th>
                <th>Specialization</th>
                <th class="text-end">Fee</th>
                <th class="text-center">Appointments</th>
                <th class="text-center">Completed</th>
                <th class="text-center">Prescriptions</th>
                <th>Availability</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($doctors as $doctor)
                <tr>
                    <td>
                        <a href="{{ route('doctors.show', $doctor) }}" class="fw-semibold text-decoration-none">
                            {{ $doctor->doctor_name }}
                        </a>
                        <div class="text-muted small">{{ $doctor->phone }}</div>
                    </td>
                    <td>{{ $doctor->department?->department_name ?? '—' }}</td>
                    <td class="text-muted">{{ $doctor->specialization }}</td>
                    <td class="text-end">Rs {{ number_format($doctor->consultation_fee, 0) }}</td>
                    <td class="text-center">{{ $doctor->appointments_count }}</td>
                    <td class="text-center">{{ $doctor->completed_count }}</td>
                    <td class="text-center">{{ $doctor->prescriptions_count }}</td>
                    <td>
                        <span class="badge {{ $doctor->availability_status === 'available' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                            {{ ucfirst($doctor->availability_status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-person-badge"></i>
                            No doctors match the selected filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $doctors])
