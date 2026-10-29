@extends('layouts.app')

@section('title', 'Appointments Report')
@section('page-title', 'Appointments Report')
@section('page-subtitle', ($isDoctor ? 'Your appointments between ' : 'Appointments between ')
    . \Illuminate\Support\Carbon::parse($from)->format('d M Y') . ' and ' . \Illuminate\Support\Carbon::parse($to)->format('d M Y'))

@section('content')
    <div id="reportPage" data-report-url="{{ route('reports.appointments') }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
            <a href="{{ route('reports.index') }}" class="btn btn-soft">
                <i class="bi bi-arrow-left me-1"></i> All reports
            </a>
            <button type="button" class="btn btn-teal" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print report
            </button>
        </div>

        <div class="d-none d-print-block mb-3">
            <div class="print-header">
                <p class="print-hospital">{{ config('app.name') }}</p>
                <p class="print-tagline">Appointments Report</p>
                <p class="print-tagline" id="printPeriod">Period: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Total</div>
                            <div class="stat-value" data-summary-key="total">{{ $summary['total'] }}</div>
                            <div class="stat-hint">All appointments</div>
                        </div>
                        <span class="stat-icon navy"><i class="bi bi-calendar2-week"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Upcoming</div>
                            <div class="stat-value" data-summary-key="upcoming">{{ $summary['upcoming'] }}</div>
                            <div class="stat-hint">Scheduled / confirmed</div>
                        </div>
                        <span class="stat-icon indigo"><i class="bi bi-hourglass-split"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Completed</div>
                            <div class="stat-value" data-summary-key="completed">{{ $summary['completed'] }}</div>
                            <div class="stat-hint">Visits completed</div>
                        </div>
                        <span class="stat-icon green"><i class="bi bi-check2-all"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">Cancelled</div>
                            <div class="stat-value" data-summary-key="cancelled">{{ $summary['cancelled'] }}</div>
                            <div class="stat-hint">Cancelled by staff</div>
                        </div>
                        <span class="stat-icon rose"><i class="bi bi-x-circle"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-xl">
                <div class="card">
                    <div class="stat-card">
                        <div>
                            <div class="stat-label">No Show</div>
                            <div class="stat-value" data-summary-key="noshow">{{ $summary['noshow'] }}</div>
                            <div class="stat-hint">Patients who missed</div>
                        </div>
                        <span class="stat-icon amber"><i class="bi bi-person-x"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <form class="filter-bar no-print" id="reportFilterForm">
            <div class="row g-2 align-items-end">
                <div class="{{ $isDoctor ? 'col-md-4' : 'col-md-3' }}">
                    <label class="form-label" for="reportSearch">Search</label>
                    <input type="search" name="q" id="reportSearch" class="form-control"
                           placeholder="Patient or doctor name" value="{{ request('q') }}">
                </div>
                @unless ($isDoctor)
                    <div class="col-md-3">
                        <label class="form-label" for="reportDoctor">Doctor</label>
                        <select name="doctor_id" id="reportDoctor" class="form-select">
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
                    <label class="form-label" for="reportStatus">Status</label>
                    <select name="status" id="reportStatus" class="form-select">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\Appointment::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateFrom">From</label>
                    <input type="date" name="date_from" id="dateFrom" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="dateTo">To</label>
                    <input type="date" name="date_to" id="dateTo" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-soft w-100" id="resetFilters" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </form>

        <div class="table-wrap" id="reportTableArea">
            @include('reports.partials.appointments-table', ['appointments' => $appointments])
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reports.js') }}"></script>
@endpush
