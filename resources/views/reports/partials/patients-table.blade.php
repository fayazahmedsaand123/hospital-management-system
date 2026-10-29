<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Patient Code</th>
                <th>Name</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Phone</th>
                <th>Registered On</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                <tr>
                    <td class="fw-semibold">{{ $patient->patient_code }}</td>
                    <td>
                        <a href="{{ route('patients.show', $patient) }}" class="fw-semibold text-decoration-none">
                            {{ $patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $patient->email ?: 'No email recorded' }}</div>
                    </td>
                    <td>{{ $patient->gender }}</td>
                    <td>{{ $patient->age !== null ? $patient->age . ' yrs' : '—' }}</td>
                    <td>{{ $patient->phone }}</td>
                    <td>{{ $patient->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            No patients registered in this period.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $patients])
