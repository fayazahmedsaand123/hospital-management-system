@extends('layouts.app')

@section('title', 'Prescriptions')
@section('page-title', 'Prescriptions')
@section('page-subtitle', 'Clinical records, medicine instructions and printable prescriptions')

@section('content')
    <div id="prescriptionsPage"
         data-index-url="{{ route('prescriptions.index') }}"
         data-appointments-url="{{ route('lookups.appointments') }}"
         data-is-doctor="{{ $isDoctor ? '1' : '0' }}"
         data-doctor-id="{{ auth()->user()->doctorProfile?->id }}"
         data-can-delete="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $prescriptions->total() }}</span> prescriptions</div>
            <button type="button" class="btn btn-primary" id="addPrescriptionBtn">
                <i class="bi bi-file-medical-fill me-1"></i> New Prescription
            </button>
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="{{ $isDoctor ? 'col-md-6' : 'col-md-4' }}">
                    <label class="form-label" for="prescriptionSearch">Search</label>
                    <input type="search" name="q" id="prescriptionSearch" class="form-control"
                           placeholder="Patient name, code or diagnosis" value="{{ request('q') }}">
                </div>

                @unless ($isDoctor)
                    <div class="col-md-3">
                        <label class="form-label" for="filterDoctor">Doctor</label>
                        <select name="doctor_id" id="filterDoctor" class="form-select">
                            <option value="">All doctors</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected((string) request('doctor_id') === (string) $doctor->id)>
                                    {{ $doctor->doctor_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="col-md-2">
                    <label class="form-label" for="dateFrom">From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateTo">To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="tableArea">
            @include('prescriptions.partials.table', ['prescriptions' => $prescriptions, 'isDoctor' => $isDoctor])
        </div>
    </div>

    <div class="modal fade" id="prescriptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="prescriptionForm" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="prescriptionModalTitle">New Prescription</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label" for="prescription_patient">Patient</label>
                                <select name="patient_id" id="prescription_patient" class="form-select" required>
                                    <option value="">Select patient</option>
                                    @foreach ($patients as $patient)
                                        <option value="{{ $patient->id }}">
                                            {{ $patient->patient_code }} — {{ $patient->patient_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @unless ($isDoctor)
                                <div class="col-md-4">
                                    <label class="form-label" for="prescription_doctor">Doctor</label>
                                    <select name="doctor_id" id="prescription_doctor" class="form-select" required>
                                        <option value="">Select doctor</option>
                                        @foreach ($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endunless

                            <div class="col-md-3">
                                <label class="form-label" for="prescription_date">Date</label>
                                <input type="date" name="prescription_date" id="prescription_date" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="prescription_appointment">
                                    Appointment <span class="text-muted">(optional)</span>
                                </label>
                                <select name="appointment_id" id="prescription_appointment" class="form-select">
                                    <option value="">No linked appointment</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="diagnosis">Diagnosis</label>
                                <input type="text" name="diagnosis" id="diagnosis" class="form-control" maxlength="2000" required
                                       placeholder="Recorded diagnosis">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="instructions">General Instructions</label>
                                <textarea name="instructions" id="instructions" rows="2" class="form-control" maxlength="2000"
                                          placeholder="Advice given to the patient"></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0">Medicines</h6>
                            <button type="button" class="btn btn-sm btn-soft" id="addItemBtn">
                                <i class="bi bi-plus-lg me-1"></i> Add Medicine
                            </button>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="min-width: 160px">Medicine</th>
                                        <th style="min-width: 100px">Dosage</th>
                                        <th style="min-width: 130px">Frequency</th>
                                        <th style="min-width: 100px">Duration</th>
                                        <th style="min-width: 140px">Instructions</th>
                                        <th style="width: 44px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="prescriptionSaveBtn">Save Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/prescriptions.js') }}"></script>
@endpush
