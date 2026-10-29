@extends('layouts.app')

@section('title', 'Appointments')
@section('page-title', 'Appointments')
@section('page-subtitle', $isDoctor ? 'Your consultation schedule' : 'Book, reschedule and track patient visits')

@section('content')
    <div id="appointmentsPage"
         data-index-url="{{ route('appointments.index') }}"
         data-doctors-url="{{ route('lookups.doctors') }}"
         data-slots-url="{{ route('lookups.slots') }}"
         data-can-manage="{{ auth()->user()->isAdminOrReceptionist() ? '1' : '0' }}"
         data-can-delete="{{ auth()->user()->isAdmin() ? '1' : '0' }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="text-muted small">Total: <span class="fw-semibold" id="totalCount">{{ $appointments->total() }}</span> appointments</div>
            @if (auth()->user()->isAdminOrReceptionist())
                <button type="button" class="btn btn-primary" id="addAppointmentBtn">
                    <i class="bi bi-calendar2-plus me-1"></i> Book Appointment
                </button>
            @endif
        </div>

        <form class="filter-bar" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="{{ $isDoctor ? 'col-md-6' : 'col-md-3' }}">
                    <label class="form-label" for="appointmentSearch">Search</label>
                    <input type="search" name="q" id="appointmentSearch" class="form-control"
                           placeholder="Patient name, code or doctor" value="{{ request('q') }}">
                </div>

                @unless ($isDoctor)
                    <div class="col-md-2">
                        <label class="form-label" for="filterDepartment">Department</label>
                        <select name="department_id" id="filterDepartment" class="form-select">
                            <option value="">All departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                                    {{ $department->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
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
                    <label class="form-label" for="filterDate">Date</label>
                    <input type="date" name="date" id="filterDate" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select name="status" id="filterStatus" class="form-select">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Appointment::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $isDoctor ? 'col-md-2' : 'col-md-1' }}">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="tableArea">
            @include('appointments.partials.table', ['appointments' => $appointments, 'isDoctor' => $isDoctor])
        </div>
    </div>

    @if (auth()->user()->isAdminOrReceptionist())
        <div class="modal fade" id="appointmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="appointmentForm" novalidate>
                        <div class="modal-header">
                            <h5 class="modal-title" id="appointmentModalTitle">Book Appointment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="patient_id">Patient</label>
                                    <select name="patient_id" id="patient_id" class="form-select" required>
                                        <option value="">Select patient</option>
                                        @foreach ($patients as $patient)
                                            <option value="{{ $patient->id }}">
                                                {{ $patient->patient_code }} — {{ $patient->patient_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="modalDepartment">Department</label>
                                    <select id="modalDepartment" class="form-select">
                                        <option value="">Select department</option>
                                        @foreach ($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="doctor_id">Doctor</label>
                                    <select name="doctor_id" id="doctor_id" class="form-select" required>
                                        <option value="">Select department first</option>
                                    </select>
                                    <div class="form-text" id="doctorInfo"></div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="appointment_date">Date</label>
                                    <input type="date" name="appointment_date" id="appointment_date" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="appointment_time">Time</label>
                                    <select name="appointment_time" id="appointment_time" class="form-select" required>
                                        <option value="">Select doctor and date first</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="status">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        @foreach (\App\Models\Appointment::STATUSES as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="reason">Reason</label>
                                    <input type="text" name="reason" id="reason" class="form-control" maxlength="255"
                                           placeholder="Reason for visit">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="notes">Notes <span class="text-muted">(optional)</span></label>
                                    <textarea name="notes" id="notes" rows="2" class="form-control" maxlength="2000"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="appointmentSaveBtn">Save Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="{{ asset('js/appointments.js') }}"></script>
@endpush
