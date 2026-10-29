<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Patient</th>
                @unless ($isDoctor)
                    <th>Doctor</th>
                @endunless
                <th>Date</th>
                <th>Time</th>
                <th>Reason</th>
                <th>Status</th>
                @unless ($isDoctor)
                    <th class="text-end">Actions</th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @forelse ($appointments as $appointment)
                <tr>
                    <td>
                        <a href="{{ route('patients.show', $appointment->patient) }}" class="fw-semibold text-decoration-none">
                            {{ $appointment->patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $appointment->patient->patient_code }}</div>
                    </td>
                    @unless ($isDoctor)
                        <td>
                            {{ $appointment->doctor->doctor_name }}
                            <div class="text-muted small">{{ $appointment->doctor->department?->department_name }}</div>
                        </td>
                    @endunless
                    <td class="fw-semibold">{{ $appointment->appointment_date->format('d M Y') }}</td>
                    <td>{{ $appointment->formatted_time }}</td>
                    <td class="text-muted">{{ $appointment->reason ?: '—' }}</td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-id="{{ $appointment->id }}" style="min-width: 130px">
                            @foreach (\App\Models\Appointment::STATUSES as $status)
                                <option value="{{ $status }}" @selected($appointment->status === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </td>
                    @unless ($isDoctor)
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light border edit-btn"
                                    data-id="{{ $appointment->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if (auth()->user()->isAdmin())
                                <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                        data-id="{{ $appointment->id }}"
                                        data-name="{{ $appointment->patient->patient_name }}"
                                        title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isDoctor ? 5 : 7 }}">
                        <div class="empty-state">
                            <i class="bi bi-calendar2-x"></i>
                            No appointments found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $appointments])
