@extends('layouts.app')

@section('title', 'Doctors')
@section('page-title', 'Doctors')
@section('page-subtitle', 'Manage doctors, specializations and availability')

@section('content')
    <div id="doctorsPage"
         data-index-url="{{ route('doctors.index') }}"
         data-can-manage="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $doctors->total() }}</span> doctors</div>
            @if (auth()->user()->isAdmin())
                <button type="button" class="btn btn-primary" id="addDoctorBtn">
                    <i class="bi bi-plus-lg me-1"></i> Add Doctor
                </button>
            @endif
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="doctorSearch">Search</label>
                    <input type="search" name="q" id="doctorSearch" class="form-control"
                           placeholder="Name, specialization, phone or email" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="doctorDepartment">Department</label>
                    <select name="department_id" id="doctorDepartment" class="form-select">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                {{ $department->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="doctorAvailability">Availability</label>
                    <select name="availability_status" id="doctorAvailability" class="form-select">
                        <option value="">All</option>
                        <option value="available" @selected(request('availability_status') === 'available')>Available</option>
                        <option value="unavailable" @selected(request('availability_status') === 'unavailable')>Unavailable</option>
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
            @include('doctors.partials.table', ['doctors' => $doctors])
        </div>
    </div>

    @if (auth()->user()->isAdmin())
        <div class="modal fade" id="doctorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="doctorForm" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="doctorModalTitle">Add Doctor</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="doctor_name">Full Name</label>
                                    <input type="text" name="doctor_name" id="doctor_name" class="form-control" required maxlength="120">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="department_id">Department</label>
                                    <select name="department_id" id="department_id" class="form-select" required>
                                        <option value="">Select department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="specialization">Specialization</label>
                                    <input type="text" name="specialization" id="specialization" class="form-control" required maxlength="120">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="qualification">Qualification</label>
                                    <input type="text" name="qualification" id="qualification" class="form-control" maxlength="150"
                                           placeholder="e.g. MBBS, FCPS">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone</label>
                                    <input type="text" name="phone" id="phone" class="form-control" required maxlength="20">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control" required maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="consultation_fee">Consultation Fee (Rs)</label>
                                    <input type="number" name="consultation_fee" id="consultation_fee" class="form-control"
                                           min="0" step="0.01" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="availability_status">Availability</label>
                                    <select name="availability_status" id="availability_status" class="form-select">
                                        <option value="available">Available</option>
                                        <option value="unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="doctorSaveBtn">Save Doctor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/doctors.js') }}"></script>
@endpush
