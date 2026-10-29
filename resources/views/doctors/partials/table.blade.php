<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Doctor</th>
                <th>Department</th>
                <th>Phone</th>
                <th>Fee</th>
                <th>Availability</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($doctors as $doctor)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $doctor->doctor_name }}</div>
                        <div class="text-muted small">{{ $doctor->specialization }}</div>
                    </td>
                    <td>
                        <span class="badge badge-soft-primary">{{ $doctor->department?->department_name ?? '—' }}</span>
                    </td>
                    <td>{{ $doctor->phone }}</td>
                    <td class="fw-semibold">Rs {{ number_format($doctor->consultation_fee, 0) }}</td>
                    <td>
                        <span class="badge {{ $doctor->availability_status === 'available' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                            {{ ucfirst($doctor->availability_status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-sm btn-soft" title="View profile">
                            <i class="bi bi-eye"></i>
                        </a>
                        @if (auth()->user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-light border edit-btn"
                                    data-id="{{ $doctor->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                    data-id="{{ $doctor->id }}"
                                    data-name="{{ $doctor->doctor_name }}"
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
                            <i class="bi bi-person-badge"></i>
                            No doctors found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $doctors])
