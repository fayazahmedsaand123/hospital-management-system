<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Department</th>
                <th>Doctors</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departments as $department)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $department->department_name }}</div>
                        <div class="text-muted small">
                            {{ $department->description ? \Illuminate\Support\Str::limit($department->description, 90) : 'No description' }}
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-soft-primary">{{ $department->doctors_count }} doctor(s)</span>
                    </td>
                    <td>
                        <span class="badge {{ $department->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                            {{ ucfirst($department->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('doctors.index', ['department_id' => $department->id]) }}"
                           class="btn btn-sm btn-soft" title="View doctors">
                            <i class="bi bi-person-lines-fill"></i>
                        </a>
                        @if (auth()->user()->isAdmin())
                            <button type="button" class="btn btn-sm btn-light border edit-btn"
                                    data-id="{{ $department->id }}" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light border text-danger delete-btn"
                                    data-id="{{ $department->id }}"
                                    data-name="{{ $department->department_name }}"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="bi bi-diagram-3"></i>
                            No departments found. Try adjusting your filters.
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['paginator' => $departments])
