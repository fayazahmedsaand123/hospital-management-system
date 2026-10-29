<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Reason</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $appointment)
                <tr>
                    <td class="fw-semibold">{{ $appointment->appointment_date->format('d M Y') }}</td>
                    <td>{{ $appointment->formatted_time }}</td>
                    <td>
                        <a href="{{ route('patients.show', $appointment->patient) }}" class="fw-semibold text-decoration-none">
                            {{ $appointment->patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $appointment->patient->patient_code }}</div>
                    </td>
                    <td>
                        {{ $appointment->doctor->doctor_name }}
                        <div class="text-muted small">{{ $appointment->doctor->specialization }}</div>
                    </td>
                    <td class="text-muted">{{ $appointment->reason ?: '—' }}</td>
                    <td><span class="badge {{ $appointment->status_badge_class }}">{{ $appointment->status }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            No appointments match the selected filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $appointments])
