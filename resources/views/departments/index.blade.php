@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')
@section('page-subtitle', 'Manage hospital departments and their doctors')

@section('content')
    <div id="departmentsPage"
         data-index-url="{{ route('departments.index') }}"
         data-can-manage="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $departments->total() }}</span> departments</div>
            @if (auth()->user()->isAdmin())
                <button type="button" class="btn btn-primary" id="addDepartmentBtn">
                    <i class="bi bi-plus-lg me-1"></i> Add Department
                </button>
            @endif
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="departmentSearch">Search</label>
                    <input type="search" name="q" id="departmentSearch" class="form-control"
                           placeholder="Search by name or description" value="{{ request('q') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="departmentStatus">Status</label>
                    <select name="status" id="departmentStatus" class="form-select">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="tableArea">
            @include('departments.partials.table', ['departments' => $departments])
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="departmentForm" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="departmentModalTitle">Add Department</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="department_name">Department Name</label>
                                <input type="text" name="department_name" id="department_name" class="form-control" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="description">Description</label>
                                <textarea name="description" id="description" rows="3" class="form-control" maxlength="1000"
                                          placeholder="Short description of the department"></textarea>
                            </div>
                            <div>
                                <label class="form-label" for="status">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="departmentSaveBtn">Save Department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/departments.js') }}"></script>
@endpush
