<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Patient</th>
                <th>Gender / Age</th>
                <th>Phone</th>
                <th>Email</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($patients as $patient)
                <tr>
                    <td>
                        <a href="{{ route('patients.show', $patient) }}" class="fw-semibold text-decoration-none">
                            {{ $patient->patient_name }}
                        </a>
                        <div class="text-muted small">{{ $patient->patient_code }}</div>
                    </td>
                    <td>
                        {{ $patient->gender }}
                        <div class="text-muted small">{{ $patient->age }} years</div>
                    </td>
                    <td>{{ $patient->phone }}</td>
                    <td class="text-muted">{{ $patient->email ?: '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-sm btn-soft" title="View profile">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-light border edit-btn"
                                data-id="{{ $patient->id }}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @if (auth()->user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                    data-id="{{ $patient->id }}"
                                    data-name="{{ $patient->patient_name }}"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            No patients found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $patients])
