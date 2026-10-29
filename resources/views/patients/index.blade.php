@extends('layouts.app')

@section('title', 'Patients')
@section('page-title', 'Patients')
@section('page-subtitle', 'Register patients and review their medical history')

@section('content')
    <div id="patientsPage"
         data-index-url="{{ route('patients.index') }}"
         data-can-delete="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $patients->total() }}</span> patients</div>
            <button type="button" class="btn btn-primary" id="addPatientBtn">
                <i class="bi bi-person-plus-fill me-1"></i> Register Patient
            </button>
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label" for="patientSearch">Search</label>
                    <input type="search" name="q" id="patientSearch" class="form-control"
                           placeholder="Name, patient code, phone or email" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="patientGender">Gender</label>
                    <select name="gender" id="patientGender" class="form-select">
                        <option value="">All genders</option>
                        <option value="Male" @selected(request('gender') === 'Male')>Male</option>
                        <option value="Female" @selected(request('gender') === 'Female')>Female</option>
                        <option value="Other" @selected(request('gender') === 'Other')>Other</option>
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
            @include('patients.partials.table', ['patients' => $patients])
        </div>
    </div>

    <div class="modal fade" id="patientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="patientForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="patientModalTitle">Register Patient</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="patient_name">Full Name</label>
                                <input type="text" name="patient_name" id="patient_name" class="form-control" required maxlength="120">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="gender">Gender</label>
                                <select name="gender" id="gender" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date_of_birth">Date of Birth</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control" required
                                       max="{{ today()->subDay()->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control" required maxlength="20"
                                       placeholder="03xx-xxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email <span class="text-muted">(optional)</span></label>
                                <input type="email" name="email" id="email" class="form-control" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="blood_group">Blood Group</label>
                                <select name="blood_group" id="blood_group" class="form-select">
                                    <option value="">Unknown</option>
                                    @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                        <option value="{{ $group }}">{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="emergency_contact">Emergency Contact</label>
                                <input type="text" name="emergency_contact" id="emergency_contact" class="form-control"
                                       maxlength="100" placeholder="Name / phone number">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="address">Address</label>
                                <textarea name="address" id="address" rows="2" class="form-control" maxlength="500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="patientSaveBtn">Save Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/patients.js') }}"></script>
@endpush
